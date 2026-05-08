@extends('layouts.app')
@section('title', $profile->user->name)
@section('page-title', 'Interviewer Profile')

@section('content')
<div class="row g-4">

    {{-- Left: Profile card --}}
    <div class="col-lg-4">
        <div class="card stat-card mb-4">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white mx-auto mb-3"
                     style="width:72px;height:72px;background:#4f46e5;font-size:1.8rem">
                    {{ strtoupper(substr($profile->user->name ?? '?', 0, 1)) }}
                </div>
                <h5 class="fw-bold mb-0">{{ $profile->user->name }}</h5>
                <p class="text-muted small mb-1">{{ $profile->current_title }}</p>
                @if($profile->current_company)
                    <p class="text-muted small mb-2">@ {{ $profile->current_company }}</p>
                @endif
                <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                    <span class="text-warning">★</span>
                    <span class="fw-semibold">{{ number_format($profile->average_rating, 1) }}</span>
                    <span class="text-muted small">({{ $profile->total_reviews }} reviews)</span>
                </div>

                <div class="text-start border-top pt-3">
                    <div class="d-flex justify-content-between py-1 small">
                        <span class="text-muted">Experience</span>
                        <span class="fw-medium">{{ $profile->years_of_experience }}+ years</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 small">
                        <span class="text-muted">Level</span>
                        <span class="fw-medium text-capitalize">{{ $profile->experience_level }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 small">
                        <span class="text-muted">Sessions</span>
                        <span class="fw-medium">{{ $profile->total_sessions }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 small">
                        <span class="text-muted">Duration</span>
                        <span class="fw-medium">{{ $profile->session_duration_minutes }} min</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 mt-1 border-top">
                        <span class="text-muted small">Rate</span>
                        <span class="fw-bold fs-5 text-primary">${{ number_format($profile->hourly_rate) }}<span class="fw-normal text-muted small">/hr</span></span>
                    </div>
                </div>

                @if($authUser->isCandidate())
                    <a href="{{ route('bookings.create', $profile->id) }}"
                       class="btn btn-primary w-100 fw-semibold mt-3">Book a Session</a>
                    <a href="{{ route('messages.conversation', $profile->user_id) }}"
                       class="btn btn-outline-secondary w-100 btn-sm mt-2">Send Message</a>
                @endif
            </div>
        </div>

        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">Domains</h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($profile->domains ?? [] as $domain)
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary">{{ ucfirst($domain) }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card stat-card">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">Interview Types</h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($profile->interview_types ?? [] as $type)
                        <span class="badge rounded-pill" style="background:#f5f3ff;color:#7c3aed">{{ ucwords(str_replace('_', ' ', $type)) }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Slots + Reviews --}}
    <div class="col-lg-8">

        @if($profile->interview_approach)
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-2">Interview Approach</h6>
                <p class="text-muted small mb-0 lh-base">{{ $profile->interview_approach }}</p>
            </div>
        </div>
        @endif

        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">Available Slots</h6>
                @forelse($profile->availableSlots as $slot)
                <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div>
                        <p class="mb-0 fw-medium small">{{ $slot->start_time->format('D, M d Y') }}</p>
                        <p class="mb-0 text-muted" style="font-size:.75rem">{{ $slot->start_time->format('H:i') }} – {{ $slot->end_time->format('H:i') }} {{ $slot->timezone }}</p>
                    </div>
                    @if($authUser->isCandidate())
                        <a href="{{ route('bookings.create', $profile->id) }}?slot_id={{ $slot->id }}"
                           class="btn btn-sm" style="background:#eef2ff;color:#4f46e5;font-size:.75rem">Book</a>
                    @endif
                </div>
                @empty
                <p class="text-muted small text-center py-3 mb-0">No available slots right now.</p>
                @endforelse
            </div>
        </div>

        <div class="card stat-card">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">Reviews ({{ $reviews->total() }})</h6>
                @forelse($reviews as $review)
                <div class="py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-secondary bg-light flex-shrink-0"
                                 style="width:28px;height:28px;font-size:.75rem">
                                {{ strtoupper(substr($review->reviewer->name ?? '?', 0, 1)) }}
                            </div>
                            <span class="fw-medium small">{{ $review->reviewer->name }}</span>
                        </div>
                        <div>
                            @for($i = 1; $i <= 5; $i++)
                                <span class="{{ $i <= $review->rating ? 'text-warning' : 'text-secondary opacity-25' }} small">★</span>
                            @endfor
                        </div>
                    </div>
                    @if($review->comment)
                        <p class="text-muted small mb-1 ms-4">{{ $review->comment }}</p>
                    @endif
                    <p class="text-muted ms-4 mb-0" style="font-size:.72rem">{{ $review->created_at->format('M d, Y') }}</p>
                </div>
                @empty
                <p class="text-muted small text-center py-3 mb-0">No reviews yet.</p>
                @endforelse
                {{ $reviews->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
