@extends('layouts.app')
@section('title', 'Find Interviewers')
@section('page-title', 'Find Interviewers')

@section('content')

{{-- Filters --}}
<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('interviewers.index') }}" class="row g-3 align-items-end">
            <div class="col-6 col-lg-2">
                <label for="filterDomain" class="form-label small fw-medium mb-1">Domain</label>
                <select id="filterDomain" name="domain" class="form-select form-select-sm">
                    <option value="">All Domains</option>
                    @foreach(['backend','frontend','devops','aiml','mobile','fullstack','security'] as $d)
                        <option value="{{ $d }}" @selected(request('domain') === $d)>{{ ucfirst($d === 'aiml' ? 'AI/ML' : $d) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="filterType" class="form-label small fw-medium mb-1">Interview Type</label>
                <select id="filterType" name="interview_type" class="form-select form-select-sm">
                    <option value="">Any Type</option>
                    <option value="dsa" @selected(request('interview_type') === 'dsa')>DSA</option>
                    <option value="system_design" @selected(request('interview_type') === 'system_design')>System Design</option>
                    <option value="behavioral" @selected(request('interview_type') === 'behavioral')>Behavioral</option>
                    <option value="mixed" @selected(request('interview_type') === 'mixed')>Mixed</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="filterLevel" class="form-label small fw-medium mb-1">Level</label>
                <select id="filterLevel" name="experience_level" class="form-select form-select-sm">
                    <option value="">Any Level</option>
                    @foreach(['senior','staff','principal','distinguished'] as $l)
                        <option value="{{ $l }}" @selected(request('experience_level') === $l)>{{ ucfirst($l) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="filterRating" class="form-label small fw-medium mb-1">Min Rating</label>
                <input id="filterRating" type="number" name="min_rating" value="{{ request('min_rating') }}"
                       min="1" max="5" step="0.5" class="form-control form-control-sm" placeholder="0" />
            </div>
            <div class="col-6 col-lg-2">
                <label for="filterRate" class="form-label small fw-medium mb-1">Max Rate ($/hr)</label>
                <input id="filterRate" type="number" name="max_rate" value="{{ request('max_rate') }}"
                       min="0" class="form-control form-control-sm" placeholder="Any" />
            </div>
            <div class="col-6 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="{{ route('interviewers.index') }}" class="btn btn-outline-secondary btn-sm flex-grow-1">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Results --}}
<div class="row g-4 mb-4">
    @forelse($interviewers as $profile)
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('interviewers.show', $profile->id) }}" class="card stat-card h-100 text-decoration-none text-dark">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                         style="width:48px;height:48px;background:#4f46e5;font-size:1.1rem">
                        {{ strtoupper(substr($profile->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="mb-0 fw-semibold text-truncate">{{ $profile->user->name }}</p>
                        <p class="mb-0 text-muted small text-truncate">{{ $profile->current_title }}</p>
                        @if($profile->current_company)
                            <p class="mb-0 text-truncate" style="font-size:.72rem;color:#9ca3af">@ {{ $profile->current_company }}</p>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-1 mb-2">
                    @foreach(array_slice($profile->domains ?? [], 0, 3) as $domain)
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary" style="font-weight:500">{{ ucfirst($domain) }}</span>
                    @endforeach
                    <span class="badge rounded-pill bg-light text-secondary">{{ ucfirst($profile->experience_level) }}</span>
                </div>

                <div class="d-flex flex-wrap gap-1 mb-3">
                    @foreach($profile->interview_types ?? [] as $type)
                        <span class="badge rounded-pill" style="background:#f5f3ff;color:#7c3aed">{{ ucwords(str_replace('_', ' ', $type)) }}</span>
                    @endforeach
                </div>

                <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                    <div class="d-flex align-items-center gap-1">
                        <span class="text-warning">★</span>
                        <span class="fw-semibold small">{{ number_format($profile->average_rating, 1) }}</span>
                        <span class="text-muted" style="font-size:.75rem">({{ $profile->total_reviews }})</span>
                    </div>
                    <span class="fw-bold small text-primary">${{ number_format($profile->hourly_rate) }}<span class="fw-normal text-muted">/hr</span></span>
                </div>
            </div>
        </a>
    </div>
    @empty
    <div class="col-12 text-center py-5 text-muted">
        <div class="fs-1 mb-3">🔍</div>
        <p class="fw-medium">No interviewers found</p>
        <p class="small">Try adjusting your filters</p>
    </div>
    @endforelse
</div>

{{ $interviewers->links() }}
@endsection
