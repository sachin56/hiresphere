@extends('layouts.app')
@section('title', 'Submit Evaluation')
@section('page-title', 'Submit Evaluation Report')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">

    {{-- Candidate info --}}
    <div class="card stat-card mb-4">
        <div class="card-body p-3 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                 style="width:40px;height:40px;background:#4f46e5;font-size:.9rem">
                {{ strtoupper(substr($booking->candidate->name ?? '?', 0, 1)) }}
            </div>
            <div>
                <p class="fw-semibold mb-0">{{ $booking->candidate->name }}</p>
                <p class="text-muted small mb-0">
                    {{ $booking->scheduled_at->format('M d, Y H:i') }} · {{ ucwords(str_replace('_', ' ', $booking->interview_type)) }}
                </p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('evaluations.store') }}">
        @csrf
        <input type="hidden" name="booking_id" value="{{ $booking->id }}" />

        {{-- Scores --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">Scores (1–10)</h6>
                <div class="row g-3">
                    @foreach(['overall_score' => 'Overall *', 'technical_score' => 'Technical', 'communication_score' => 'Communication', 'problem_solving_score' => 'Problem Solving', 'system_design_score' => 'System Design', 'behavioral_score' => 'Behavioral'] as $name => $label)
                    <div class="col-6">
                        <label for="{{ $name }}" class="form-label small fw-medium">{{ $label }}</label>
                        <input id="{{ $name }}" type="number" name="{{ $name }}" value="{{ old($name) }}"
                               min="1" max="10" {{ str_contains($label, '*') ? 'required' : '' }}
                               class="form-control form-control-sm"
                               placeholder="{{ str_contains($label, '*') ? '1–10' : 'Optional' }}" />
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Hire recommendation --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">Hire Recommendation</h6>
                <input type="hidden" name="hire_recommendation" id="hireRecInput" value="{{ old('hire_recommendation') }}" />
                <div class="row g-3" id="hireRecGrid">
                    @foreach([
                        'strong_hire'    => ['#16a34a', 'Strong Hire'],
                        'hire'           => ['#4ade80', 'Hire'],
                        'no_hire'        => ['#f87171', 'No Hire'],
                        'strong_no_hire' => ['#dc2626', 'Strong No Hire'],
                    ] as $val => [$color, $label])
                    <div class="col-6">
                        <button type="button" class="pick-card p-3 text-center {{ old('hire_recommendation') === $val ? 'selected' : '' }}"
                                onclick="selectRec(this, '{{ $val }}')">
                            <div class="rounded-circle mx-auto mb-2" style="width:12px;height:12px;background:{{ $color }}"></div>
                            <p class="mb-0 small fw-medium text-secondary">{{ $label }}</p>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Topics --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <label for="topicsCovered" class="form-label fw-semibold small">Topics Covered</label>
                <input id="topicsCovered" type="text" name="topics_covered[]" value="{{ old('topics_covered.0') }}"
                       class="form-control"
                       placeholder="e.g. Arrays, Hash Maps, Binary Search, Recursion (comma-separated)" />
                <p class="text-muted small mt-1 mb-0">Separate topics with commas</p>
            </div>
        </div>

        {{-- Strengths --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <label for="strengths" class="form-label fw-semibold small">
                    Strengths * <span class="text-muted fw-normal">(min 50 chars)</span>
                </label>
                <textarea id="strengths" name="strengths" rows="4" required minlength="50" class="form-control"
                          placeholder="What did the candidate do well? Be specific and encouraging…">{{ old('strengths') }}</textarea>
            </div>
        </div>

        {{-- Areas for improvement --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <label for="areasImprove" class="form-label fw-semibold small">
                    Areas for Improvement * <span class="text-muted fw-normal">(min 50 chars)</span>
                </label>
                <textarea id="areasImprove" name="areas_for_improvement" rows="4" required minlength="50" class="form-control"
                          placeholder="What should the candidate focus on improving? Provide actionable advice…">{{ old('areas_for_improvement') }}</textarea>
            </div>
        </div>

        {{-- Detailed feedback --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <label for="detailedFeedback" class="form-label fw-semibold small">
                    Detailed Feedback * <span class="text-muted fw-normal">(min 100 chars)</span>
                </label>
                <textarea id="detailedFeedback" name="detailed_feedback" rows="6" required minlength="100" class="form-control"
                          placeholder="Provide a comprehensive evaluation of the candidate's performance, approach, problem-solving skills, and overall readiness…">{{ old('detailed_feedback') }}</textarea>
            </div>
        </div>

        {{-- Share toggle --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4 bg-light rounded-3">
                <div class="form-check">
                    <input type="checkbox" name="is_shared_with_candidate" value="1" id="shareReport"
                           class="form-check-input" checked />
                    <label for="shareReport" class="form-check-label small fw-medium">Share report with candidate</label>
                    <p class="text-muted mb-0" style="font-size:.73rem">The candidate will be notified and can view this report.</p>
                </div>
            </div>
        </div>

        <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold py-2">Submit Evaluation</button>
            <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-outline-secondary px-4">Cancel</a>
        </div>
    </form>

</div>
</div>

<script>
function selectRec(card, val) {
    document.querySelectorAll('#hireRecGrid .pick-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('hireRecInput').value = val;
}
</script>
@endsection
