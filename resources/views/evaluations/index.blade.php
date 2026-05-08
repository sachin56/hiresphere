@extends('layouts.app')
@section('title', 'Evaluations')
@section('page-title', 'Evaluation Reports')

@section('content')
<div class="card stat-card">
    @forelse($reports as $report)
        <a href="{{ route('evaluations.show', $report->id) }}"
           class="d-flex align-items-center gap-4 p-4 text-decoration-none text-dark {{ !$loop->last ? 'border-bottom' : '' }}">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                 style="width:44px;height:44px;background:#4f46e5;font-size:.9rem">
                {{ strtoupper(substr($report->interviewer->name ?? '?', 0, 1)) }}
            </div>
            <div class="flex-grow-1 min-w-0">
                <p class="mb-0 fw-semibold">
                    {{ $authUser->isCandidate() ? $report->interviewer->name : \App\Models\User::find($report->candidate_id)?->name }}
                </p>
                <p class="mb-0 text-muted small">{{ $report->created_at->format('M d, Y') }}</p>
                @if($report->hire_recommendation)
                    <p class="mb-0 text-muted" style="font-size:.75rem">
                        {{ ucwords(str_replace('_', ' ', $report->hire_recommendation)) }}
                    </p>
                @endif
            </div>
            <div class="text-center flex-shrink-0">
                <p class="display-6 fw-bold text-primary mb-0">{{ $report->overall_score }}</p>
                <p class="text-muted mb-0" style="font-size:.72rem">/ 10</p>
            </div>
        </a>
    @empty
        <div class="py-5 text-center text-muted">
            <div class="fs-1 mb-3">📋</div>
            <p class="fw-medium">No evaluation reports yet</p>
            <p class="small">Complete an interview session to receive your first report.</p>
        </div>
    @endforelse
</div>
<div class="mt-3">{{ $reports->links() }}</div>
@endsection
