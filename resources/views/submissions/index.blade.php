@extends('layouts.app')
@section('title', 'Submissions')
@section('page-title', 'Code Submissions')

@section('content')

<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <div class="d-flex flex-wrap gap-2">
        @foreach(['all' => 'All', 'submitted' => 'Submitted', 'under_review' => 'Under Review', 'reviewed' => 'Reviewed'] as $val => $label)
            @php $active = ($val === 'all' && !request('status')) || request('status') === $val; @endphp
            <a href="{{ route('submissions.index', $val !== 'all' ? ['status' => $val] : []) }}"
               class="btn btn-sm rounded-pill {{ $active ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
    @if($authUser->isCandidate())
        <a href="{{ route('submissions.create') }}" class="btn btn-primary btn-sm rounded-pill ms-auto">+ New Submission</a>
    @endif
</div>

<div class="card stat-card">
    @forelse($submissions as $submission)
        @php
            $statusCls = match($submission->status) {
                'reviewed'     => 'bg-success text-white',
                'under_review' => 'bg-info text-dark',
                default        => 'bg-warning text-dark',
            };
            $typeIcon = match($submission->submission_type) {
                'github_link' => '🔗',
                'inline_code' => '💻',
                default       => '📄',
            };
        @endphp
        <a href="{{ route('submissions.show', $submission->id) }}"
           class="d-flex align-items-start gap-3 p-4 text-decoration-none text-dark {{ !$loop->last ? 'border-bottom' : '' }}">
            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-4"
                 style="width:44px;height:44px;background:#eef2ff">
                {{ $typeIcon }}
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <p class="mb-0 fw-semibold text-truncate">{{ $submission->title }}</p>
                    <span class="badge {{ $statusCls }} flex-shrink-0">{{ ucwords(str_replace('_', ' ', $submission->status)) }}</span>
                </div>
                @if($submission->description)
                    <p class="text-muted small mb-1 text-truncate">{{ $submission->description }}</p>
                @endif
                <div class="d-flex align-items-center gap-3 text-muted" style="font-size:.75rem">
                    @if($submission->language)
                        <code class="bg-light px-2 py-0 rounded">{{ $submission->language }}</code>
                    @endif
                    <span>{{ $submission->created_at->format('M d, Y') }}</span>
                    @if($submission->booking)
                        <span>Linked to booking</span>
                    @endif
                </div>
                @if($submission->interviewer_annotation && $submission->status === 'reviewed')
                    <div class="mt-2 small text-primary bg-primary bg-opacity-10 rounded-3 px-3 py-1">
                        💬 {{ Str::limit($submission->interviewer_annotation, 120) }}
                    </div>
                @endif
            </div>
        </a>
    @empty
        <div class="py-5 text-center text-muted">
            <div class="fs-1 mb-3">💻</div>
            <p class="fw-medium">No submissions yet</p>
            @if($authUser->isCandidate())
                <a href="{{ route('submissions.create') }}" class="btn btn-primary btn-sm mt-2">Submit Your First Solution</a>
            @endif
        </div>
    @endforelse
</div>
<div class="mt-3">{{ $submissions->links() }}</div>
@endsection
