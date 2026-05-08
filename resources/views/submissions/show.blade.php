@extends('layouts.app')
@section('title', $submission->title)
@section('page-title', 'Submission')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-9">

    <div class="card stat-card mb-4">
        <div class="card-body p-4">
            @php
                $statusCls = match($submission->status) {
                    'reviewed'     => 'bg-success text-white',
                    'under_review' => 'bg-info text-dark',
                    default        => 'bg-warning text-dark',
                };
            @endphp
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <h5 class="fw-bold mb-0">{{ $submission->title }}</h5>
                    @if($submission->description)
                        <p class="text-muted small mb-0">{{ $submission->description }}</p>
                    @endif
                </div>
                <span class="badge {{ $statusCls }} flex-shrink-0">{{ ucwords(str_replace('_', ' ', $submission->status)) }}</span>
            </div>

            <div class="row g-3 py-3 border-top border-bottom mb-4">
                <div class="col-6 col-sm-3">
                    <p class="text-muted mb-1" style="font-size:.72rem">Type</p>
                    <p class="fw-medium small mb-0">{{ ucwords(str_replace('_', ' ', $submission->submission_type)) }}</p>
                </div>
                @if($submission->language)
                <div class="col-6 col-sm-3">
                    <p class="text-muted mb-1" style="font-size:.72rem">Language</p>
                    <code class="fw-medium small">{{ $submission->language }}</code>
                </div>
                @endif
                <div class="col-6 col-sm-3">
                    <p class="text-muted mb-1" style="font-size:.72rem">Submitted</p>
                    <p class="fw-medium small mb-0">{{ $submission->created_at->format('M d, Y') }}</p>
                </div>
                @if($submission->reviewed_at)
                <div class="col-6 col-sm-3">
                    <p class="text-muted mb-1" style="font-size:.72rem">Reviewed</p>
                    <p class="fw-medium small mb-0">{{ $submission->reviewed_at->format('M d, Y') }}</p>
                </div>
                @endif
            </div>

            {{-- File download --}}
            @if($submission->submission_type === 'file_upload' && $downloadUrl)
            <div class="d-flex align-items-center gap-3 bg-light rounded-3 p-3 mb-3">
                <span class="fs-4 flex-shrink-0">📄</span>
                <div class="flex-grow-1 min-w-0">
                    <p class="fw-medium small mb-0 text-truncate">{{ $submission->file_name }}</p>
                    <p class="text-muted mb-0" style="font-size:.72rem">{{ $submission->file_size ? number_format($submission->file_size / 1024, 1).' KB' : '' }}</p>
                </div>
                <a href="{{ $downloadUrl }}" class="btn btn-primary btn-sm" target="_blank">Download</a>
            </div>
            @endif

            {{-- GitHub link --}}
            @if($submission->github_url)
            <div class="d-flex align-items-center gap-3 bg-light rounded-3 p-3 mb-3">
                <span class="fs-4 flex-shrink-0">🔗</span>
                <div class="flex-grow-1 min-w-0">
                    <p class="fw-medium small mb-0 text-truncate">{{ $submission->github_url }}</p>
                    @if($submission->github_branch)
                        <p class="text-muted mb-0" style="font-size:.72rem">Branch: {{ $submission->github_branch }}</p>
                    @endif
                </div>
                <a href="{{ $submission->github_url }}" target="_blank" class="btn btn-dark btn-sm">View on GitHub</a>
            </div>
            @endif

            {{-- Inline code --}}
            @if($submission->inline_code)
            <div>
                <p class="fw-medium small mb-2">Code</p>
                <pre class="rounded-3 p-4 small font-monospace overflow-auto" style="background:#1e1b4b;color:#e0e7ff;max-height:380px"><code>{{ $submission->inline_code }}</code></pre>
            </div>
            @endif
        </div>
    </div>

    {{-- Interviewer annotation --}}
    @if($submission->interviewer_annotation)
    <div class="card mb-4" style="border:1px solid #c7d2fe;background:#eef2ff">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="fs-5">💬</span>
                <h6 class="fw-semibold mb-0" style="color:#3730a3">Interviewer Feedback</h6>
                @if($submission->reviewer)
                    <span class="text-muted small">by {{ $submission->reviewer->name }}</span>
                @endif
            </div>
            <p class="small lh-base mb-0" style="color:#3730a3;white-space:pre-line">{{ $submission->interviewer_annotation }}</p>
        </div>
    </div>
    @endif

    {{-- Add annotation --}}
    @if($authUser->isInterviewer() && $submission->booking && $authUser->id === $submission->booking->interviewer_id && !$submission->interviewer_annotation)
    <div class="card stat-card mb-4">
        <div class="card-body p-4">
            <h6 class="fw-semibold mb-3">Add Annotation</h6>
            <form method="POST" action="{{ route('submissions.annotate', $submission->id) }}">
                @csrf
                <div class="mb-3">
                    <label for="annotation" class="form-label small fw-medium">Feedback</label>
                    <textarea id="annotation" name="annotation" rows="5" required class="form-control"
                              placeholder="Provide detailed feedback on the solution quality, approach, optimizations…"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Submit Annotation</button>
            </form>
        </div>
    </div>
    @endif

</div>
</div>
@endsection
