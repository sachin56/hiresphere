@extends('layouts.app')
@section('title', 'Interview Details')
@section('page-title', 'Interview Details')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-9">

    {{-- Header card --}}
    <div class="card stat-card mb-4">
        <div class="card-body p-4">
            @php
                $other = $authUser->isCandidate() ? $booking->interviewer : $booking->candidate;
                $statusCls = match($booking->status) {
                    'pending'   => 'bg-warning text-dark',
                    'accepted'  => 'bg-success text-white',
                    'completed' => 'bg-primary text-white',
                    'cancelled', 'rejected' => 'bg-danger text-white',
                    default     => 'bg-secondary text-white',
                };
                $paymentCls = $booking->payment_status === 'paid' ? 'bg-success text-white' : 'bg-warning text-dark';
            @endphp
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-4 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                         style="width:56px;height:56px;background:#4f46e5;font-size:1.4rem">
                        {{ strtoupper(substr($other?->name ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <p class="fw-bold fs-5 mb-0">{{ $other?->name }}</p>
                        <p class="text-muted small mb-0">{{ $booking->scheduled_at->format('D, M d Y \a\t H:i') }} ({{ $booking->timezone }})</p>
                        <p class="text-muted small mb-0">{{ $booking->duration_minutes }} min · {{ ucwords(str_replace('_', ' ', $booking->interview_type)) }}</p>
                    </div>
                </div>
                <div class="d-flex flex-row flex-sm-column align-items-start align-items-sm-end gap-2">
                    <span class="badge {{ $statusCls }}">{{ ucfirst($booking->status) }}</span>
                    <span class="badge {{ $paymentCls }}" style="font-size:.7rem">Payment: {{ ucfirst($booking->payment_status) }}</span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                @if($authUser->isCandidate() && $booking->payment_status !== 'paid' && !in_array($booking->status, ['cancelled','rejected']))
                    <form method="POST" action="{{ route('payments.checkout', $booking->id) }}">
                        @csrf
                        <button class="btn btn-primary fw-semibold">
                            Pay ${{ number_format($booking->amount, 2) }}
                        </button>
                    </form>
                @endif
                @if($booking->status === 'accepted')
                    <a href="{{ route('bookings.join', $booking->id) }}" class="btn btn-primary fw-semibold">🎥 Join Session</a>
                @endif
                @if($authUser->isInterviewer() && $booking->status === 'pending')
                    <form method="POST" action="{{ route('bookings.accept', $booking->id) }}">
                        @csrf
                        <button class="btn btn-success fw-semibold">Accept</button>
                    </form>
                @endif
                @if($authUser->isInterviewer() && $booking->status === 'accepted' && !$booking->evaluationReport)
                    <a href="{{ route('evaluations.create', $booking->id) }}" class="btn fw-semibold text-white" style="background:#7c3aed">Submit Evaluation</a>
                @endif
                @if(!$booking->isCompleted() && !in_array($booking->status, ['cancelled','rejected']))
                    <form method="POST" action="{{ route('bookings.cancel', $booking->id) }}"
                          onsubmit="return confirm('Cancel this booking?')">
                        @csrf
                        <button class="btn btn-outline-danger fw-medium">Cancel</button>
                    </form>
                @endif
                <a href="{{ route('messages.conversation', $other?->id) }}" class="btn btn-outline-secondary fw-medium">Message</a>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    @if($booking->candidate_notes || $booking->interviewer_notes)
    <div class="card stat-card mb-4">
        <div class="card-body p-4">
            <h6 class="fw-semibold mb-3">Notes</h6>
            @if($booking->candidate_notes)
                <p class="text-muted small fw-medium mb-1">Candidate notes</p>
                <p class="small mb-3">{{ $booking->candidate_notes }}</p>
            @endif
            @if($booking->interviewer_notes)
                <p class="text-muted small fw-medium mb-1">Interviewer notes</p>
                <p class="small mb-0">{{ $booking->interviewer_notes }}</p>
            @endif
        </div>
    </div>
    @endif

    {{-- Evaluation Report --}}
    @if($booking->evaluationReport && ($authUser->id === $booking->interviewer_id || $booking->evaluationReport->is_shared_with_candidate))
    <div class="card stat-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-semibold mb-0">Evaluation Report</h6>
                <a href="{{ route('evaluations.show', $booking->evaluationReport->id) }}" class="small text-primary text-decoration-none">View full report</a>
            </div>
            <div class="d-flex align-items-center gap-4">
                <div class="text-center">
                    <p class="display-6 fw-bold text-primary mb-0">{{ $booking->evaluationReport->overall_score }}</p>
                    <p class="text-muted mb-0" style="font-size:.72rem">/ 10</p>
                </div>
                <div class="flex-grow-1">
                    <p class="fw-medium small mb-1">{{ ucwords(str_replace('_', ' ', $booking->evaluationReport->hire_recommendation ?? '—')) }}</p>
                    <p class="text-muted small mb-0" style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
                        {{ $booking->evaluationReport->detailed_feedback }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Submissions --}}
    @if($booking->submissions->count() > 0)
    <div class="card stat-card mb-4">
        <div class="card-body p-4">
            <h6 class="fw-semibold mb-3">Submissions</h6>
            @foreach($booking->submissions as $submission)
                @php
                    $subCls = match($submission->status) {
                        'reviewed'     => 'bg-success text-white',
                        'under_review' => 'bg-info text-dark',
                        default        => 'bg-warning text-dark',
                    };
                @endphp
                <a href="{{ route('submissions.show', $submission->id) }}"
                   class="d-flex align-items-center gap-3 py-2 text-decoration-none text-dark {{ !$loop->last ? 'border-bottom' : '' }}">
                    <span class="fs-5 flex-shrink-0">📄</span>
                    <div class="flex-grow-1 min-w-0">
                        <p class="fw-medium small mb-0 text-truncate">{{ $submission->title }}</p>
                        <p class="text-muted mb-0" style="font-size:.73rem">{{ ucwords(str_replace('_', ' ', $submission->submission_type)) }} · {{ $submission->created_at->format('M d') }}</p>
                    </div>
                    <span class="badge {{ $subCls }} flex-shrink-0">{{ ucfirst($submission->status) }}</span>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Leave a review --}}
    @if($booking->isCompleted())
        @php $hasReviewed = $booking->reviews->where('reviewer_id', $authUser->id)->isNotEmpty(); @endphp
        @if(!$hasReviewed)
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">Leave a Review</h6>
                <form method="POST" action="{{ route('bookings.review', $booking->id) }}">
                    @csrf
                    <div class="mb-3">
                        <fieldset>
                        <legend class="form-label small fw-medium mb-2">Rating</legend>
                        <div class="d-flex gap-1" id="starRating">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" class="star-btn border-0 bg-transparent text-secondary"
                                        style="font-size:2rem;line-height:1;cursor:pointer"
                                        data-value="{{ $i }}" onclick="setRating({{ $i }})">★</button>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" required />
                        </fieldset>
                    </div>
                    <div class="mb-3">
                        <label for="reviewComment" class="form-label small fw-medium">Comment</label>
                        <textarea id="reviewComment" name="comment" rows="3" class="form-control"
                                  placeholder="Share your experience…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm fw-medium">Submit Review</button>
                </form>
            </div>
        </div>
        @else
            <div class="alert alert-success small">✅ You've already reviewed this session.</div>
        @endif
    @endif

</div>
</div>

<script>
function setRating(val) {
    document.getElementById('ratingInput').value = val;
    document.querySelectorAll('.star-btn').forEach(btn => {
        const v = parseInt(btn.dataset.value);
        btn.classList.toggle('text-warning', v <= val);
        btn.classList.toggle('text-secondary', v > val);
    });
}
</script>
@endsection
