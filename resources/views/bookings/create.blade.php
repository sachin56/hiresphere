@extends('layouts.app')
@section('title', 'Book Interview')
@section('page-title', 'Book Interview')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">

    {{-- Interviewer summary --}}
    <div class="card stat-card mb-4">
        <div class="card-body p-4 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                 style="width:56px;height:56px;background:#4f46e5;font-size:1.4rem">
                {{ strtoupper(substr($profile->user->name ?? '?', 0, 1)) }}
            </div>
            <div>
                <p class="fw-bold fs-5 mb-0">{{ $profile->user->name }}</p>
                <p class="text-muted small mb-1">{{ $profile->current_title }}{{ $profile->current_company ? ' @ '.$profile->current_company : '' }}</p>
                <p class="text-primary fw-semibold small mb-0">${{ number_format($profile->hourly_rate) }}/hr · {{ $profile->session_duration_minutes }} min session</p>
            </div>
        </div>
    </div>

    {{-- Booking form --}}
    <form method="POST" action="{{ route('bookings.store') }}" id="bookingForm">
        @csrf
        <input type="hidden" name="interviewer_id" value="{{ $profile->user_id }}" />
        <input type="hidden" name="slot_id" id="slotIdInput" value="{{ request('slot_id') }}" required />
        <input type="hidden" name="interview_type" id="typeInput" value="dsa" />

        {{-- Slot selection --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">Select a Time Slot *</h6>
                @if($profile->availableSlots->isEmpty())
                    <div class="text-center py-4 text-muted border border-dashed rounded-3">
                        <p class="mb-0 small">No available slots. Check back later or message the interviewer.</p>
                    </div>
                @else
                    <div class="row g-2" style="max-height:260px;overflow-y:auto" id="slotGrid">
                        @foreach($profile->availableSlots as $slot)
                        <div class="col-sm-6">
                            <button type="button" class="pick-card p-3 text-start {{ request('slot_id') == $slot->id ? 'selected' : '' }}"
                                onclick="selectSlot(this, '{{ $slot->id }}')">
                                <p class="mb-0 fw-semibold small">{{ $slot->start_time->format('D, M d Y') }}</p>
                                <p class="mb-0 text-muted" style="font-size:.73rem">
                                    {{ $slot->start_time->format('H:i') }} – {{ $slot->end_time->format('H:i') }} {{ $slot->timezone }}
                                </p>
                            </button>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Interview type --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">Interview Type *</h6>
                <div class="row g-3">
                    @foreach(['dsa' => 'DSA', 'system_design' => 'System Design', 'behavioral' => 'Behavioral', 'mixed' => 'Mixed'] as $val => $label)
                        @if(in_array($val, $profile->interview_types ?? []))
                        <div class="col-6">
                            <button type="button" class="pick-card p-3 text-center {{ $val === 'dsa' ? 'selected' : '' }}"
                                    onclick="selectInterviewType(this, '{{ $val }}')">
                                <p class="mb-0 fw-medium small">{{ $label }}</p>
                            </button>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Domain --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <label for="interviewDomain" class="form-label fw-semibold small">Focus Domain</label>
                <select id="interviewDomain" name="interview_domain" class="form-select">
                    <option value="">Let the interviewer decide</option>
                    @foreach($profile->domains ?? [] as $domain)
                        <option value="{{ $domain }}">{{ ucfirst($domain) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Notes --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <label for="candidateNotes" class="form-label fw-semibold small">Notes for Interviewer</label>
                <textarea id="candidateNotes" name="candidate_notes" rows="3" class="form-control"
                          placeholder="Your current level, specific topics to cover, etc.">{{ old('candidate_notes') }}</textarea>
            </div>
        </div>

        {{-- Recording consent --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4 bg-light rounded-3">
                <div class="form-check">
                    <input type="checkbox" name="recording_consent" value="1" id="recording"
                           class="form-check-input" {{ old('recording_consent') ? 'checked' : '' }} />
                    <label for="recording" class="form-check-label small fw-medium">I consent to session recording</label>
                    <p class="text-muted mb-0" style="font-size:.73rem">Recordings are stored securely and can be reviewed later.</p>
                </div>
            </div>
        </div>

        {{-- Cost + Submit --}}
        <div class="card stat-card">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Total cost</p>
                    <p class="display-6 fw-bold text-primary mb-0">${{ number_format($profile->hourly_rate * ($profile->session_duration_minutes / 60), 2) }}</p>
                    <p class="text-muted mb-0" style="font-size:.73rem">{{ $profile->session_duration_minutes }} min session</p>
                </div>
                <button type="submit" id="submitBtn" class="btn btn-primary fw-semibold px-5 py-3"
                        {{ request('slot_id') ? '' : 'disabled' }}>
                    Confirm Booking
                </button>
            </div>
        </div>

    </form>
</div>
</div>

<script>
function selectSlot(card, slotId) {
    document.querySelectorAll('#slotGrid .pick-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('slotIdInput').value = slotId;
    document.getElementById('submitBtn').disabled = false;
}

function selectInterviewType(card, val) {
    document.querySelectorAll('#bookingForm .pick-card').forEach(c => {
        if (c.closest('.col-6') && c !== card) {
            // only reset type cards (not slot cards)
        }
    });
    // Reset all type cards in the type section
    card.closest('.row').querySelectorAll('.pick-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('typeInput').value = val;
}
</script>
@endsection
