@extends('layouts.app')
@section('title', 'Submit Solution')
@section('page-title', 'Submit Solution')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">
    <form method="POST" action="{{ route('submissions.store') }}" enctype="multipart/form-data" id="subForm">
        @csrf

        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="subTitle" class="form-label fw-semibold small">Title *</label>
                    <input id="subTitle" type="text" name="title" value="{{ old('title') }}" required
                           class="form-control" placeholder="e.g. Two Sum — O(n) solution" />
                </div>
                <div>
                    <label for="subDesc" class="form-label fw-semibold small">Description</label>
                    <textarea id="subDesc" name="description" rows="2" class="form-control"
                              placeholder="Brief description of your approach…">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        @if($bookings->isNotEmpty())
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <label for="bookingId" class="form-label fw-semibold small">Link to Interview (optional)</label>
                <select id="bookingId" name="booking_id" class="form-select">
                    <option value="">— Not linked —</option>
                    @foreach($bookings as $booking)
                        <option value="{{ $booking->id }}" @selected($selectedBookingId === $booking->id || old('booking_id') == $booking->id)>
                            {{ $booking->interviewer->name }} · {{ $booking->scheduled_at->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif

        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <label for="subLang" class="form-label fw-semibold small">Language</label>
                <select id="subLang" name="language" class="form-select">
                    <option value="">— Select language —</option>
                    @foreach(['Python','JavaScript','TypeScript','Java','C++','C','Go','Rust','Swift','Kotlin','Ruby','PHP','SQL','Other'] as $lang)
                        <option value="{{ $lang }}" @selected(old('language') === $lang)>{{ $lang }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Submission type --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <p class="fw-semibold small mb-3">Submission Method *</p>
                <input type="hidden" name="submission_type" id="subTypeInput" value="{{ old('submission_type', 'file_upload') }}" />
                <div class="row g-3 mb-4" id="subTypeGrid">
                    @foreach(['file_upload' => ['📄', 'File Upload'], 'github_link' => ['🔗', 'GitHub Link'], 'inline_code' => ['💻', 'Inline Code']] as $val => [$icon, $label])
                    <div class="col-4">
                        <button type="button" class="pick-card p-3 text-center {{ old('submission_type', 'file_upload') === $val ? 'selected' : '' }}"
                                onclick="selectSubType('{{ $val }}')">
                            <div class="fs-3 mb-1">{{ $icon }}</div>
                            <p class="mb-0 small fw-medium">{{ $label }}</p>
                        </button>
                    </div>
                    @endforeach
                </div>

                {{-- File upload panel --}}
                <div id="panel-file_upload" class="{{ old('submission_type', 'file_upload') !== 'file_upload' ? 'd-none' : '' }}">
                    <label for="subFile" class="form-label small fw-medium">File (max 50 MB)</label>
                    <input id="subFile" type="file" name="file" class="form-control form-control-sm" />
                </div>

                {{-- GitHub panel --}}
                <div id="panel-github_link" class="{{ old('submission_type') !== 'github_link' ? 'd-none' : '' }}">
                    <div class="mb-3">
                        <label for="githubUrl" class="form-label small fw-medium">GitHub Repository URL</label>
                        <input id="githubUrl" type="url" name="github_url" value="{{ old('github_url') }}"
                               class="form-control" placeholder="https://github.com/username/repo" />
                    </div>
                    <div>
                        <label for="githubBranch" class="form-label small fw-medium">Branch (optional)</label>
                        <input id="githubBranch" type="text" name="github_branch" value="{{ old('github_branch', 'main') }}"
                               class="form-control" placeholder="main" />
                    </div>
                </div>

                {{-- Inline code panel --}}
                <div id="panel-inline_code" class="{{ old('submission_type') !== 'inline_code' ? 'd-none' : '' }}">
                    <label for="inlineCode" class="form-label small fw-medium">Code</label>
                    <textarea id="inlineCode" name="inline_code" rows="12" class="form-control font-monospace"
                              placeholder="Paste your solution here…">{{ old('inline_code') }}</textarea>
                </div>
            </div>
        </div>

        <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold py-2">Submit Solution</button>
            <a href="{{ route('submissions.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
        </div>
    </form>
</div>
</div>

<script>
const panels = ['file_upload', 'github_link', 'inline_code'];

function selectSubType(val) {
    document.getElementById('subTypeInput').value = val;
    document.querySelectorAll('#subTypeGrid .pick-card').forEach(c => c.classList.remove('selected'));
    const cards = document.querySelectorAll('#subTypeGrid .pick-card');
    const idx = panels.indexOf(val);
    if (idx >= 0) cards[idx].classList.add('selected');
    panels.forEach(t => {
        const panel = document.getElementById('panel-' + t);
        if (panel) panel.classList.toggle('d-none', t !== val);
    });
}
</script>
@endsection
