<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\EvaluationReport;
use App\Models\Review;
use App\Services\NotificationService;
use App\Services\SQSService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly SQSService $sqs,
    ) {}

    public function index(): View
    {
        $user  = auth()->user();
        $query = $user->isCandidate()
            ? EvaluationReport::where('candidate_id', $user->id)->where('is_shared_with_candidate', true)
            : EvaluationReport::where('interviewer_id', $user->id);

        $reports = $query->with(['booking', 'interviewer:id,name,profile_picture'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('evaluations.index', compact('reports'));
    }

    public function show(string $id): View
    {
        $user   = auth()->user();
        $report = EvaluationReport::with(['booking', 'interviewer:id,name,profile_picture', 'candidate:id,name'])->findOrFail($id);

        $canView = $user->id === $report->interviewer_id
            || ($user->id === $report->candidate_id && $report->is_shared_with_candidate);
        abort_unless($canView, 403);

        return view('evaluations.show', compact('report'));
    }

    public function create(string $bookingId): View
    {
        $user    = auth()->user();
        $booking = Booking::where('interviewer_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->with('candidate:id,name,profile_picture')
            ->findOrFail($bookingId);

        abort_if(EvaluationReport::where('booking_id', $bookingId)->exists(), 422, 'Evaluation already submitted.');

        return view('evaluations.create', compact('booking'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isInterviewer(), 403);

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
            return back()->with('error', 'Evaluation already submitted for this booking.');
        }

        $report = EvaluationReport::create([...$data, 'candidate_id' => $booking->candidate_id, 'interviewer_id' => $user->id]);
        $booking->update(['status' => 'completed']);

        $candidate = \App\Models\User::find($booking->candidate_id);
        if ($data['is_shared_with_candidate'] ?? true) {
            $this->notifications->sendEvaluationReady($candidate, $report->toArray());
        }
        $this->sqs->dispatchEvaluationReady($report->toArray());

        return redirect()->route('evaluations.show', $report->id)
            ->with('success', 'Evaluation report submitted successfully!');
    }

    public function submitReview(Request $request, string $bookingId): RedirectResponse
    {
        $user    = auth()->user();
        $booking = Booking::where(fn($q) => $q->where('candidate_id', $user->id)->orWhere('interviewer_id', $user->id))
            ->findOrFail($bookingId);

        abort_unless($booking->isCompleted(), 422);

        if (Review::where('booking_id', $bookingId)->where('reviewer_id', $user->id)->exists()) {
            return back()->with('error', 'You have already reviewed this session.');
        }

        $data = $request->validate([
            'rating'    => 'required|integer|min:1|max:5',
            'comment'   => 'nullable|string|max:1000',
            'is_public' => 'boolean',
        ]);

        $revieweeId = $user->id === $booking->candidate_id ? $booking->interviewer_id : $booking->candidate_id;

        Review::create(['booking_id' => $bookingId, 'reviewer_id' => $user->id, 'reviewee_id' => $revieweeId, ...$data]);

        if ($revieweeId === $booking->interviewer_id) {
            \App\Models\User::with('interviewerProfile')->find($revieweeId)?->interviewerProfile?->updateRating();
        }

        return back()->with('success', 'Thank you for your review!');
    }
}
