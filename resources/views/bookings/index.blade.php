@extends('layouts.app')
@section('title', 'My Interviews')
@section('page-title', 'My Interviews')

@section('content')

{{-- Status filter --}}
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    @foreach(['all' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label)
        @php $active = ($val === 'all' && !request('status')) || request('status') === $val; @endphp
        <a href="{{ route('bookings.index', $val !== 'all' ? ['status' => $val] : []) }}"
           class="btn btn-sm rounded-pill {{ $active ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }}
        </a>
    @endforeach
    @if($authUser->isCandidate())
        <a href="{{ route('interviewers.index') }}" class="btn btn-primary btn-sm rounded-pill ms-auto">
            + Book Interview
        </a>
    @endif
</div>

{{-- Bookings list --}}
<div class="card stat-card">
    @forelse($bookings as $booking)
        @php
            $other = $authUser->isCandidate() ? $booking->interviewer : $booking->candidate;
            $badgeCls = match($booking->status) {
                'pending'   => 'bg-warning text-dark',
                'accepted'  => 'bg-success text-white',
                'completed' => 'bg-primary text-white',
                'cancelled', 'rejected' => 'bg-danger text-white',
                default     => 'bg-secondary text-white',
            };
        @endphp
        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                 style="width:40px;height:40px;background:#4f46e5;font-size:.85rem">
                {{ strtoupper(substr($other?->name ?? '?', 0, 1)) }}
            </div>
            <div class="flex-grow-1 min-w-0">
                <p class="mb-0 fw-semibold">{{ $other?->name ?? 'Unknown' }}</p>
                <div class="d-flex flex-wrap gap-2 mt-1">
                    <span class="text-muted small">{{ ucwords(str_replace('_', ' ', $booking->interview_type)) }}</span>
                    <span class="text-muted">·</span>
                    <span class="text-muted small">{{ $booking->scheduled_at->format('M d, Y H:i') }}</span>
                    <span class="text-muted">·</span>
                    <span class="text-muted small">{{ $booking->duration_minutes }} min</span>
                    <span class="text-muted">·</span>
                    <span class="small fw-semibold text-primary">${{ number_format($booking->amount, 2) }}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 flex-shrink-0">
                <span class="badge {{ $badgeCls }}">{{ ucfirst($booking->status) }}</span>
                <a href="{{ route('bookings.show', $booking->id) }}" class="small text-primary text-decoration-none">Details</a>
                @if($booking->status === 'accepted')
                    <a href="{{ route('bookings.join', $booking->id) }}" class="btn btn-primary btn-sm">Join</a>
                @endif
            </div>
        </div>
    @empty
        <div class="py-5 text-center text-muted">
            <div class="fs-1 mb-3">📅</div>
            <p class="fw-medium">No interviews yet</p>
            @if($authUser->isCandidate())
                <p class="small">Browse interviewers and book your first session.</p>
                <a href="{{ route('interviewers.index') }}" class="btn btn-primary btn-sm mt-2">Find Interviewers</a>
            @endif
        </div>
    @endforelse
</div>

<div class="mt-3">{{ $bookings->links() }}</div>
@endsection
