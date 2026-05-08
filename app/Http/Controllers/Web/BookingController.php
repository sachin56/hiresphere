<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\InterviewerProfile;
use App\Services\NotificationService;
use App\Services\SQSService;
use App\Services\WebRTCService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly SQSService $sqs,
        private readonly WebRTCService $webrtc,
    ) {}

    public function index(Request $request): View
    {
        $user  = auth()->user();
        $query = $user->isCandidate()
            ? Booking::where('candidate_id', $user->id)->with('interviewer:id,name,profile_picture')
            : Booking::where('interviewer_id', $user->id)->with('candidate:id,name,profile_picture');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderByDesc('scheduled_at')->paginate(10)->withQueryString();

        return view('bookings.index', compact('bookings'));
    }

    public function create(string $interviewerId): View
    {
        $profile = InterviewerProfile::with(['user', 'availableSlots' => function ($q) {
            $q->where('status', 'available')->where('start_time', '>', now())->orderBy('start_time');
        }])->findOrFail($interviewerId);

        return view('bookings.create', compact('profile'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isCandidate()) {
            return back()->with('error', 'Only candidates can book interviews.');
        }

        $data = $request->validate([
            'interviewer_id'    => 'required|uuid|exists:users,id',
            'slot_id'           => 'required|uuid|exists:availability_slots,id',
            'interview_type'    => 'required|in:dsa,system_design,behavioral,mixed',
            'interview_domain'  => 'nullable|string',
            'candidate_notes'   => 'nullable|string|max:1000',
            'recording_consent' => 'boolean',
        ]);

        $slot        = AvailabilitySlot::findOrFail($data['slot_id']);
        $interviewer = \App\Models\User::with('interviewerProfile')->findOrFail($data['interviewer_id']);
        $profile     = $interviewer->interviewerProfile;

        if ($slot->status !== 'available') {
            return back()->with('error', 'This slot is no longer available. Please choose another.');
        }

        $booking = DB::transaction(function () use ($data, $user, $slot, $profile) {
            $slot->update(['status' => 'booked']);
            return Booking::create([
                'candidate_id'     => $user->id,
                'interviewer_id'   => $data['interviewer_id'],
                'slot_id'          => $slot->id,
                'interview_type'   => $data['interview_type'],
                'interview_domain' => $data['interview_domain'] ?? null,
                'status'           => 'pending',
                'scheduled_at'     => $slot->start_time,
                'duration_minutes' => $profile->session_duration_minutes,
                'timezone'         => $slot->timezone,
                'candidate_notes'  => $data['candidate_notes'] ?? null,
                'amount'           => $profile->hourly_rate * ($profile->session_duration_minutes / 60),
                'currency'         => $profile->currency,
                'recording_consent' => $data['recording_consent'] ?? false,
            ]);
        });

        $this->sqs->dispatchBookingCreated($booking->toArray());
        $this->notifications->sendBookingConfirmation($user, $interviewer, $booking->toArray());

        return redirect()->route('bookings.show', $booking->id)
            ->with('success', 'Booking request sent! Waiting for the interviewer to confirm.');
    }

    public function show(string $id): View
    {
        $user    = auth()->user();
        $booking = Booking::with(['candidate', 'interviewer', 'slot', 'evaluationReport', 'submissions'])
            ->where(fn($q) => $q->where('candidate_id', $user->id)->orWhere('interviewer_id', $user->id))
            ->findOrFail($id);

        return view('bookings.show', compact('booking'));
    }

    public function accept(string $id): RedirectResponse
    {
        $user    = auth()->user();
        $booking = Booking::where('interviewer_id', $user->id)->findOrFail($id);

        if (!$booking->isPending()) {
            return back()->with('error', 'This booking is not in a pending state.');
        }

        try {
            $room = $this->webrtc->createRoom($booking->id, $booking->scheduled_at->toDateTime(), $booking->duration_minutes);
            $booking->update(['status' => 'accepted', 'webrtc_room_id' => $room['room_id'], 'webrtc_room_url' => $room['room_url']]);
        } catch (\Exception) {
            $booking->update(['status' => 'accepted']);
        }

        $candidate   = \App\Models\User::find($booking->candidate_id);
        $interviewer = \App\Models\User::find($booking->interviewer_id);
        $this->notifications->sendBookingAccepted($candidate, $interviewer, $booking->toArray());
        $this->sqs->dispatchBookingAccepted($booking->toArray());

        return back()->with('success', 'Booking accepted! The candidate has been notified.');
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $user    = auth()->user();
        $booking = Booking::where('interviewer_id', $user->id)->findOrFail($id);

        if (!$booking->isPending()) {
            return back()->with('error', 'This booking is not in a pending state.');
        }

        $data = $request->validate(['reason' => 'nullable|string|max:500']);

        DB::transaction(function () use ($booking, $data) {
            $booking->update(['status' => 'rejected', 'interviewer_notes' => $data['reason'] ?? null]);
            if ($booking->slot_id) {
                AvailabilitySlot::where('id', $booking->slot_id)->update(['status' => 'available']);
            }
        });

        $candidate   = \App\Models\User::find($booking->candidate_id);
        $interviewer = \App\Models\User::find($booking->interviewer_id);
        $this->notifications->sendBookingRejected($candidate, $interviewer, $booking->toArray());

        return back()->with('success', 'Booking rejected and slot released.');
    }

    public function cancel(string $id): RedirectResponse
    {
        $user    = auth()->user();
        $booking = Booking::where(fn($q) => $q->where('candidate_id', $user->id)->orWhere('interviewer_id', $user->id))
            ->findOrFail($id);

        if ($booking->isCompleted()) {
            return back()->with('error', 'Cannot cancel a completed booking.');
        }

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);
            if ($booking->slot_id) {
                AvailabilitySlot::where('id', $booking->slot_id)->update(['status' => 'available']);
            }
        });

        return redirect()->route('bookings.index')->with('success', 'Booking cancelled.');
    }

    public function join(string $id)
    {
        $user    = auth()->user();
        $booking = Booking::where(fn($q) => $q->where('candidate_id', $user->id)->orWhere('interviewer_id', $user->id))
            ->findOrFail($id);

        if (!$booking->isAccepted()) {
            return back()->with('error', 'Session is not ready to join yet.');
        }

        try {
            if (!$booking->webrtc_room_url) {
                $room = $this->webrtc->createRoom($booking->id, $booking->scheduled_at->toDateTime(), $booking->duration_minutes);
                $booking->update([
                    'webrtc_room_id' => $room['room_id'],
                    'webrtc_room_url' => $room['room_url'],
                ]);
            }

            $isOwner = $user->id === $booking->interviewer_id;
            $token   = $this->webrtc->createMeetingToken($booking->webrtc_room_id, $user->id, $isOwner);
            return redirect($token ? $booking->webrtc_room_url . '?t=' . $token : $booking->webrtc_room_url);
        } catch (\Exception $e) {
            Log::warning('Failed to join interview room', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            if ($booking->webrtc_room_url) {
                return redirect($booking->webrtc_room_url);
            }

            return back()->with('error', 'Could not create the interview room. Check DAILY_API_KEY and try again.');
        }
    }
}
