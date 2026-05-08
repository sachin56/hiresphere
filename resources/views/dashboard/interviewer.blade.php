@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Interviewer Dashboard')

@section('content')

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Total Sessions',   $totalSessions,                                                              'text-primary'],
        ['Total Earnings',   '$'.number_format($totalEarnings, 0),                                        'text-success'],
        ['Rating',           $profile?->average_rating ? number_format($profile->average_rating,1).' ★' : '—', 'text-warning'],
        ['Notifications',    $unreadNotifications,                                                        'text-primary'],
    ] as $stat)
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body p-4">
                <p class="text-muted small text-uppercase fw-semibold mb-1" style="font-size:.7rem;letter-spacing:.05em">{{ $stat[0] }}</p>
                <p class="display-6 fw-bold {{ $stat[2] }} mb-0">{{ $stat[1] }}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">

    {{-- Pending Requests --}}
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-semibold mb-0">
                        Pending Requests
                        @if($pendingBookings->count() > 0)
                        <span class="badge bg-danger ms-1">{{ $pendingBookings->count() }}</span>
                        @endif
                    </h6>
                    <a href="{{ route('bookings.index', ['status' => 'pending']) }}" class="small text-decoration-none">View all</a>
                </div>

                @forelse($pendingBookings as $booking)
                <div class="d-flex align-items-start gap-3 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                         style="width:38px;height:38px;background:#7c3aed;font-size:.8rem;flex-shrink:0">
                        {{ strtoupper(substr($booking->candidate->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <p class="mb-0 fw-medium small">{{ $booking->candidate->name }}</p>
                        <p class="mb-0 text-muted" style="font-size:.75rem">
                            {{ ucwords(str_replace('_', ' ', $booking->interview_type)) }} ·
                            {{ $booking->scheduled_at->format('M d, Y H:i') }}
                        </p>
                        @if($booking->candidate_notes)
                        <p class="mb-1 text-muted fst-italic text-truncate" style="font-size:.72rem">"{{ $booking->candidate_notes }}"</p>
                        @endif
                        <div class="d-flex gap-2 mt-2">
                            <form method="POST" action="{{ route('bookings.accept', $booking->id) }}">
                                @csrf
                                <button class="btn btn-success btn-sm">Accept</button>
                            </form>
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#rejectModal{{ $booking->id }}">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Reject Modal --}}
                <div class="modal fade" id="rejectModal{{ $booking->id }}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <h6 class="modal-title fw-semibold">Reject booking?</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="{{ route('bookings.reject', $booking->id) }}">
                                @csrf
                                <div class="modal-body pt-2">
                                    <p class="text-muted small mb-3">
                                        Booking from <strong>{{ $booking->candidate->name }}</strong> on
                                        {{ $booking->scheduled_at->format('M d, Y H:i') }}
                                    </p>
                                    <label for="reason{{ $booking->id }}" class="form-label small fw-medium">Reason <span class="text-muted fw-normal">(optional)</span></label>
                                    <textarea id="reason{{ $booking->id }}" name="reason" class="form-control" rows="3"
                                              placeholder="Let the candidate know why…"></textarea>
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger btn-sm">Confirm Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-muted small text-center py-4 mb-0">No pending requests.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Upcoming Sessions --}}
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-semibold mb-0">Upcoming Sessions</h6>
                    <a href="{{ route('bookings.index', ['status' => 'accepted']) }}" class="small text-decoration-none">View all</a>
                </div>
                @forelse($upcomingBookings as $booking)
                <div class="d-flex align-items-center gap-3 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                         style="width:38px;height:38px;background:#4f46e5;font-size:.8rem;flex-shrink:0">
                        {{ strtoupper(substr($booking->candidate->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <p class="mb-0 fw-medium small">{{ $booking->candidate->name }}</p>
                        <p class="mb-0 text-muted" style="font-size:.75rem">{{ $booking->scheduled_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if($booking->status === 'accepted')
                        <a href="{{ route('bookings.join', $booking->id) }}" class="btn btn-primary btn-sm">Join</a>
                        @endif
                        <a href="{{ route('evaluations.create', $booking->id) }}" class="btn btn-outline-secondary btn-sm">Evaluate</a>
                    </div>
                </div>
                @empty
                <p class="text-muted small text-center py-4 mb-0">No upcoming sessions.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row g-3">
    @foreach([
        ['availability.index', '#4f46e5', '📅', 'Manage Availability', true],
        ['profile.edit',       null,      '👤', 'Edit Profile',        false],
        ['evaluations.index',  null,      '📋', 'Evaluations',         false],
        ['messages.index',     null,      '💬', 'Messages',            false],
    ] as [$route, $bg, $icon, $label, $primary])
    <div class="col-6 col-sm-3">
        <a href="{{ route($route) }}"
           class="card stat-card text-decoration-none text-center p-4 h-100 {{ $primary ? 'text-white' : 'text-dark' }}"
           style="{{ $primary ? 'background:'.$bg.'!important' : '' }}">
            <div class="fs-2 mb-1">{{ $icon }}</div>
            <p class="small fw-medium mb-0">{{ $label }}</p>
        </a>
    </div>
    @endforeach
</div>

@endsection
