<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SQSService;
use App\Services\WebRTCService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @OA\Tag(name="Bookings", description="Interview session booking management")
 */
class BookingController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly SQSService $sqs,
        private readonly WebRTCService $webrtc,
    ) {}

    /**
     * @OA\Get(path="/api/bookings", tags={"Bookings"}, summary="Get all bookings for current user", security={{"bearerAuth":{}}})
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->isCandidate()
            ? Booking::where('candidate_id', $user->id)->with('interviewer:id,name,profile_picture')
            : Booking::where('interviewer_id', $user->id)->with('candidate:id,name,profile_picture');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderByDesc('scheduled_at')->paginate(10);

        return response()->json($bookings);
    }

    /**
     * @OA\Post(
     *   path="/api/bookings",
     *   tags={"Bookings"},
     *   summary="Create a new booking request",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isCandidate()) {
            return response()->json(['message' => 'Only candidates can book interviews.'], 403);
        }

        $data = $request->validate([
            'interviewer_id'     => 'required|uuid|exists:users,id',
            'slot_id'            => 'required|uuid|exists:availability_slots,id',
            'interview_type'     => 'required|in:dsa,system_design,behavioral,mixed',
            'interview_domain'   => 'nullable|string',
            'candidate_notes'    => 'nullable|string|max:1000',
            'recording_consent'  => 'boolean',
        ]);

        $slot = AvailabilitySlot::findOrFail($data['slot_id']);

        if ($slot->status !== 'available') {
            return response()->json(['message' => 'This slot is no longer available.'], 422);
        }

        $interviewer = User::with('interviewerProfile')->findOrFail($data['interviewer_id']);
        $profile     = $interviewer->interviewerProfile;

        if (!$profile || !$profile->is_approved) {
            return response()->json(['message' => 'Interviewer is not accepting bookings.'], 422);
        }

        $booking = DB::transaction(function () use ($data, $user, $slot, $profile) {
            $slot->update(['status' => 'booked']);

            return Booking::create([
                'candidate_id'      => $user->id,
                'interviewer_id'    => $data['interviewer_id'],
                'slot_id'           => $slot->id,
                'interview_type'    => $data['interview_type'],
                'interview_domain'  => $data['interview_domain'] ?? null,
                'status'            => 'pending',
                'scheduled_at'      => $slot->start_time,
                'duration_minutes'  => $profile->session_duration_minutes,
                'timezone'          => $slot->timezone,
                'candidate_notes'   => $data['candidate_notes'] ?? null,
                'amount'            => $profile->hourly_rate * ($profile->session_duration_minutes / 60),
                'currency'          => $profile->currency,
                'recording_consent' => $data['recording_consent'] ?? false,
            ]);
        });

        // Async notifications via SQS
        $this->sqs->dispatchBookingCreated($booking->toArray());

        $interviewer = User::find($data['interviewer_id']);
        $this->notifications->sendBookingConfirmation($user, $interviewer, $booking->toArray());

        return response()->json($booking->load(['candidate', 'interviewer', 'slot']), 201);
    }

    /**
     * @OA\Get(path="/api/bookings/{id}", tags={"Bookings"}, summary="Get booking details", security={{"bearerAuth":{}}})
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user    = $request->user();
        $booking = Booking::with(['candidate', 'interviewer', 'slot', 'evaluationReport', 'submissions'])
            ->where(function ($q) use ($user) {
                $q->where('candidate_id', $user->id)->orWhere('interviewer_id', $user->id);
            })
            ->findOrFail($id);

        return response()->json($booking);
    }

    /**
     * @OA\Put(path="/api/bookings/{id}/accept", tags={"Bookings"}, summary="Accept a booking", security={{"bearerAuth":{}}})
     */
    public function accept(Request $request, string $id): JsonResponse
    {
        $user    = $request->user();
        $booking = Booking::where('interviewer_id', $user->id)->findOrFail($id);

        if (!$booking->isPending()) {
            return response()->json(['message' => 'Booking is not in pending state.'], 422);
        }

        // Create WebRTC room
        try {
            $room = $this->webrtc->createRoom($booking->id, $booking->scheduled_at->toDateTime(), $booking->duration_minutes);
            $booking->update([
                'status'        => 'accepted',
                'webrtc_room_id'  => $room['room_id'],
                'webrtc_room_url' => $room['room_url'],
            ]);
        } catch (\Exception $e) {
            $booking->update(['status' => 'accepted']);
            \Log::warning('WebRTC room creation failed: ' . $e->getMessage());
        }

        $candidate   = User::find($booking->candidate_id);
        $interviewer = User::find($booking->interviewer_id);
        $this->notifications->sendBookingAccepted($candidate, $interviewer, $booking->toArray());
        $this->sqs->dispatchBookingAccepted($booking->toArray());

        return response()->json($booking->fresh());
    }

    /**
     * @OA\Put(path="/api/bookings/{id}/reject", tags={"Bookings"}, summary="Reject a booking", security={{"bearerAuth":{}}})
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $user    = $request->user();
        $booking = Booking::where('interviewer_id', $user->id)->findOrFail($id);

        if (!$booking->isPending()) {
            return response()->json(['message' => 'Booking is not in pending state.'], 422);
        }

        $data = $request->validate(['reason' => 'nullable|string|max:500']);

        DB::transaction(function () use ($booking, $data) {
            $booking->update([
                'status'             => 'rejected',
                'interviewer_notes'  => $data['reason'] ?? null,
            ]);
            // Release the slot
            if ($booking->slot_id) {
                AvailabilitySlot::where('id', $booking->slot_id)->update(['status' => 'available']);
            }
        });

        $candidate   = User::find($booking->candidate_id);
        $interviewer = User::find($booking->interviewer_id);
        $this->notifications->sendBookingRejected($candidate, $interviewer, $booking->toArray());
        $this->sqs->dispatchBookingRejected($booking->toArray());

        return response()->json($booking->fresh());
    }

    /**
     * @OA\Put(path="/api/bookings/{id}/cancel", tags={"Bookings"}, summary="Cancel a booking", security={{"bearerAuth":{}}})
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $user    = $request->user();
        $booking = Booking::where(function ($q) use ($user) {
            $q->where('candidate_id', $user->id)->orWhere('interviewer_id', $user->id);
        })->findOrFail($id);

        if ($booking->isCompleted()) {
            return response()->json(['message' => 'Cannot cancel a completed booking.'], 422);
        }

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);
            if ($booking->slot_id) {
                AvailabilitySlot::where('id', $booking->slot_id)->update(['status' => 'available']);
            }
        });

        return response()->json(['message' => 'Booking cancelled successfully.']);
    }

    /**
     * @OA\Post(
     *   path="/api/bookings/{id}/join",
     *   tags={"Bookings"},
     *   summary="Get WebRTC join token for a session",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function joinSession(Request $request, string $id): JsonResponse
    {
        $user    = $request->user();
        $booking = Booking::where(function ($q) use ($user) {
            $q->where('candidate_id', $user->id)->orWhere('interviewer_id', $user->id);
        })->findOrFail($id);

        if (!$booking->isAccepted()) {
            return response()->json(['message' => 'Session is not ready to join.'], 422);
        }

        if (!$booking->webrtc_room_id) {
            return response()->json(['message' => 'Session room not configured.'], 422);
        }

        $isOwner = $user->id === $booking->interviewer_id;
        $token   = $this->webrtc->createMeetingToken($booking->webrtc_room_id, $user->id, $isOwner);

        return response()->json([
            'room_url'     => $booking->webrtc_room_url,
            'meeting_token' => $token,
        ]);
    }
}
