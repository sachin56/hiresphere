<?php

namespace App\Http\Controllers\Evaluation;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\EvaluationReport;
use App\Models\Review;
use App\Services\NotificationService;
use App\Services\SQSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Evaluations", description="Interview evaluation reports and reviews")
 */
class EvaluationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly SQSService $sqs,
    ) {}

    /**
     * @OA\Get(
     *   path="/api/evaluations",
     *   tags={"Evaluations"},
     *   summary="Get all evaluation reports for current user",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->isCandidate()
            ? EvaluationReport::where('candidate_id', $user->id)
                ->where('is_shared_with_candidate', true)
            : EvaluationReport::where('interviewer_id', $user->id);

        $reports = $query->with(['booking', 'interviewer:id,name,profile_picture'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($reports);
    }

    /**
     * @OA\Post(
     *   path="/api/evaluations",
     *   tags={"Evaluations"},
     *   summary="Submit an evaluation report (interviewer only)",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isInterviewer()) {
            return response()->json(['message' => 'Only interviewers can submit evaluations.'], 403);
        }

        $data = $request->validate([
            'booking_id'               => 'required|uuid|exists:bookings,id',
            'overall_score'            => 'required|integer|min:1|max:10',
            'technical_score'          => 'nullable|integer|min:1|max:10',
            'communication_score'      => 'nullable|integer|min:1|max:10',
            'problem_solving_score'    => 'nullable|integer|min:1|max:10',
            'system_design_score'      => 'nullable|integer|min:1|max:10',
            'behavioral_score'         => 'nullable|integer|min:1|max:10',
            'strengths'                => 'required|string|min:50',
            'areas_for_improvement'    => 'required|string|min:50',
            'detailed_feedback'        => 'required|string|min:100',
            'topics_covered'           => 'nullable|array',
            'hire_recommendation'      => 'nullable|in:strong_hire,hire,no_hire,strong_no_hire',
            'is_shared_with_candidate' => 'boolean',
        ]);

        $booking = Booking::where('interviewer_id', $user->id)->findOrFail($data['booking_id']);

        if (EvaluationReport::where('booking_id', $booking->id)->exists()) {
            return response()->json(['message' => 'Evaluation already submitted for this booking.'], 422);
        }

        $report = EvaluationReport::create([
            ...$data,
            'candidate_id'   => $booking->candidate_id,
            'interviewer_id' => $user->id,
        ]);

        // Mark booking as completed
        $booking->update(['status' => 'completed']);

        // Notify candidate
        $candidate = \App\Models\User::find($booking->candidate_id);
        if ($data['is_shared_with_candidate'] ?? true) {
            $this->notifications->sendEvaluationReady($candidate, $report->toArray());
        }

        $this->sqs->dispatchEvaluationReady($report->toArray());

        return response()->json($report, 201);
    }

    /**
     * @OA\Get(
     *   path="/api/evaluations/{id}",
     *   tags={"Evaluations"},
     *   summary="Get a specific evaluation report",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user   = $request->user();
        $report = EvaluationReport::with(['booking', 'interviewer:id,name,profile_picture'])->findOrFail($id);

        $canView = $user->id === $report->interviewer_id
            || ($user->id === $report->candidate_id && $report->is_shared_with_candidate);

        if (!$canView) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($report);
    }

    /**
     * @OA\Post(
     *   path="/api/bookings/{bookingId}/review",
     *   tags={"Evaluations"},
     *   summary="Submit a rating and review",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function submitReview(Request $request, string $bookingId): JsonResponse
    {
        $user    = $request->user();
        $booking = Booking::where(function ($q) use ($user) {
            $q->where('candidate_id', $user->id)->orWhere('interviewer_id', $user->id);
        })->findOrFail($bookingId);

        if (!$booking->isCompleted()) {
            return response()->json(['message' => 'Can only review completed sessions.'], 422);
        }

        if (Review::where('booking_id', $bookingId)->where('reviewer_id', $user->id)->exists()) {
            return response()->json(['message' => 'You have already reviewed this session.'], 422);
        }

        $data = $request->validate([
            'rating'    => 'required|integer|min:1|max:5',
            'comment'   => 'nullable|string|max:1000',
            'is_public' => 'boolean',
        ]);

        $revieweeId = $user->id === $booking->candidate_id
            ? $booking->interviewer_id
            : $booking->candidate_id;

        $review = Review::create([
            'booking_id'  => $bookingId,
            'reviewer_id' => $user->id,
            'reviewee_id' => $revieweeId,
            'rating'      => $data['rating'],
            'comment'     => $data['comment'] ?? null,
            'is_public'   => $data['is_public'] ?? true,
        ]);

        // Update interviewer profile rating
        if ($revieweeId === $booking->interviewer_id) {
            $interviewer = \App\Models\User::with('interviewerProfile')->find($revieweeId);
            $interviewer->interviewerProfile?->updateRating();
        }

        return response()->json($review, 201);
    }
}
