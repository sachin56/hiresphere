@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Total Interviews', $totalInterviews, 'text-primary'],
        ['Avg Score', $averageScore ? number_format($averageScore,1).'/10' : '—', 'text-primary'],
        ['Notifications', $unreadNotifications, 'text-primary'],
    ] as $stat)
    <div class="col-sm-4">
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

    {{-- Upcoming Interviews --}}
    <div class="col-lg-8">
        <div class="card stat-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-semibold mb-0">Upcoming Interviews</h6>
                    <a href="{{ route('bookings.index') }}" class="small text-decoration-none">View all</a>
                </div>
                @forelse($upcomingBookings as $booking)
                <div class="d-flex align-items-center gap-3 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                         style="width:40px;height:40px;background:#4f46e5;font-size:.8rem">
                        {{ strtoupper(substr($booking->interviewer->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <p class="mb-0 fw-medium small">{{ $booking->interviewer->name ?? 'Unknown' }}</p>
                        <p class="mb-0 text-muted" style="font-size:.75rem">
                            {{ ucwords(str_replace('_', ' ', $booking->interview_type)) }} ·
                            {{ $booking->scheduled_at->format('M d, Y H:i') }}
                        </p>
                    </div>
                    <span class="badge rounded-pill {{ $booking->status === 'accepted' ? 'bg-success' : 'bg-warning text-dark' }}">
                        {{ ucfirst($booking->status) }}
                    </span>
                    @if($booking->status === 'accepted')
                    <a href="{{ route('bookings.join', $booking->id) }}" class="btn btn-primary btn-sm">Join</a>
                    @endif
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <p class="mb-1">No upcoming interviews.</p>
                    <a href="{{ route('interviewers.index') }}" class="small text-decoration-none">Book one now →</a>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Evaluations --}}
    <div class="col-lg-4">
        <div class="card stat-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-semibold mb-0">Recent Reports</h6>
                    <a href="{{ route('evaluations.index') }}" class="small text-decoration-none">All</a>
                </div>
                @forelse($recentEvaluations as $report)
                <a href="{{ route('evaluations.show', $report->id) }}"
                   class="d-flex align-items-center justify-content-between py-3 text-decoration-none text-dark {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div>
                        <p class="mb-0 fw-medium small">{{ $report->interviewer->name }}</p>
                        <p class="mb-0 text-muted" style="font-size:.72rem">{{ $report->created_at->format('M d, Y') }}</p>
                    </div>
                    <span class="fw-bold text-primary">{{ $report->overall_score }}<small class="text-muted fw-normal">/10</small></span>
                </a>
                @empty
                <p class="text-muted small text-center py-4">No evaluations yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Top Interviewers --}}
<div class="card stat-card">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">Top Interviewers</h6>
            <a href="{{ route('interviewers.index') }}" class="small text-decoration-none">Browse all →</a>
        </div>
        <div class="row g-3">
            @foreach($featuredInterviewers as $profile)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('interviewers.show', $profile->id) }}"
                   class="d-flex align-items-center gap-3 p-3 border rounded-3 text-decoration-none text-dark h-100 hover-shadow"
                   style="transition:.15s">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                         style="width:40px;height:40px;background:#eef2ff;color:#4f46e5">
                        {{ strtoupper(substr($profile->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="mb-0 fw-semibold small text-truncate">{{ $profile->user->name }}</p>
                        <p class="mb-0 text-muted text-truncate" style="font-size:.72rem">{{ $profile->current_title }}</p>
                        <p class="mb-0 text-muted" style="font-size:.72rem">
                            ⭐ {{ number_format($profile->average_rating, 1) }} · ${{ number_format($profile->hourly_rate) }}/hr
                        </p>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
