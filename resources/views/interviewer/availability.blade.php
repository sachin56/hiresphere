@extends('layouts.app')
@section('title', 'Availability')
@section('page-title', 'Manage Availability')

@section('content')
<div class="row g-4">

    {{-- Add slot form --}}
    <div class="col-lg-4">
        <div class="card stat-card">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">Add Time Slot</h6>
                <form method="POST" action="{{ route('availability.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="startTime" class="form-label small fw-medium">Start Date & Time *</label>
                        <input id="startTime" type="datetime-local" name="start_time" required
                               min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                               class="form-control form-control-sm" />
                    </div>
                    <div class="mb-3">
                        <label for="endTime" class="form-label small fw-medium">End Date & Time *</label>
                        <input id="endTime" type="datetime-local" name="end_time" required
                               min="{{ now()->addHours(2)->format('Y-m-d\TH:i') }}"
                               class="form-control form-control-sm" />
                    </div>
                    <div class="mb-4">
                        <label for="timezone" class="form-label small fw-medium">Timezone *</label>
                        <select id="timezone" name="timezone" required class="form-select form-select-sm">
                            @foreach(['UTC', 'America/New_York', 'America/Los_Angeles', 'America/Chicago', 'Europe/London', 'Europe/Berlin', 'Asia/Kolkata', 'Asia/Colombo', 'Asia/Singapore', 'Asia/Tokyo', 'Australia/Sydney'] as $tz)
                                <option value="{{ $tz }}" @selected($tz === 'UTC')>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-sm fw-semibold">Add Slot</button>
                </form>

                <div class="mt-3 pt-3 border-top">
                    <p class="text-muted small mb-1">Your rate: <strong class="text-dark">${{ number_format($profile->hourly_rate) }}/hr</strong></p>
                    <p class="text-muted small mb-1">Session: <strong class="text-dark">{{ $profile->session_duration_minutes }} min</strong></p>
                    <a href="{{ route('profile.edit') }}" class="small text-primary text-decoration-none">Edit pricing →</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Slots list --}}
    <div class="col-lg-8">
        <div class="card stat-card">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">Your Slots ({{ $slots->count() }})</h6>
                @forelse($slots as $slot)
                <div class="d-flex align-items-center gap-3 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="rounded-3 d-flex flex-column align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:#eef2ff">
                        <span class="text-primary fw-medium" style="font-size:.65rem">{{ $slot->start_time->format('M') }}</span>
                        <span class="fw-bold text-primary lh-1">{{ $slot->start_time->format('d') }}</span>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <p class="mb-0 fw-medium small">{{ $slot->start_time->format('l, M d Y') }}</p>
                        <p class="mb-0 text-muted" style="font-size:.75rem">
                            {{ $slot->start_time->format('H:i') }} – {{ $slot->end_time->format('H:i') }} ({{ $slot->timezone }})
                        </p>
                    </div>
                    @php
                        $slotCls = $slot->status === 'available' ? 'bg-success text-white' : 'bg-primary text-white';
                    @endphp
                    <span class="badge {{ $slotCls }}">{{ ucfirst($slot->status) }}</span>
                    @if($slot->status !== 'booked')
                    <form method="POST" action="{{ route('availability.destroy', $slot->id) }}"
                          onsubmit="return confirm('Remove this slot?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-link text-danger p-0" aria-label="Delete slot">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                    @endif
                </div>
                @empty
                <div class="py-5 text-center text-muted">
                    <div class="fs-1 mb-3">📅</div>
                    <p class="fw-medium small">No slots added yet</p>
                    <p class="text-muted" style="font-size:.75rem">Add your available time slots so candidates can book sessions with you.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
