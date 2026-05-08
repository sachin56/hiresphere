@extends('layouts.app')
@section('title', 'Edit Profile')
@section('page-title', 'My Profile')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
    <form method="POST" action="{{ route('profile.update') }}">
        @csrf
        @method('PUT')

        {{-- Avatar + name --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4 d-flex align-items-center gap-4">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                     style="width:64px;height:64px;background:#4f46e5;font-size:1.6rem">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="fw-bold fs-5 mb-0">{{ $user->name }}</p>
                    <p class="text-muted small mb-0 text-capitalize">{{ $user->role }} · {{ $user->email }}</p>
                </div>
            </div>
        </div>

        {{-- Basic information --}}
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">Basic Information</h6>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="profileName" class="form-label small fw-medium">Full Name *</label>
                        <input id="profileName" type="text" name="name" value="{{ old('name', $user->name) }}" required
                               class="form-control" />
                    </div>
                    <div class="col-sm-6">
                        <label for="profilePhone" class="form-label small fw-medium">Phone</label>
                        <input id="profilePhone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                               class="form-control" />
                    </div>
                    <div class="col-sm-6">
                        <label for="profileLinkedin" class="form-label small fw-medium">LinkedIn URL</label>
                        <input id="profileLinkedin" type="url" name="linkedin_url" value="{{ old('linkedin_url', $user->linkedin_url) }}"
                               class="form-control" placeholder="https://linkedin.com/in/…" />
                    </div>
                    <div class="col-sm-6">
                        <label for="profileGithub" class="form-label small fw-medium">GitHub URL</label>
                        <input id="profileGithub" type="url" name="github_url" value="{{ old('github_url', $user->github_url) }}"
                               class="form-control" placeholder="https://github.com/…" />
                    </div>
                    <div class="col-12">
                        <label for="profileBio" class="form-label small fw-medium">Bio</label>
                        <textarea id="profileBio" name="bio" rows="3" class="form-control"
                                  placeholder="Tell others about yourself…">{{ old('bio', $user->bio) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Candidate-specific --}}
        @if($user->isCandidate())
        @php $cp = $user->candidateProfile; @endphp
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">Candidate Details</h6>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="currentRole" class="form-label small fw-medium">Current Role</label>
                        <input id="currentRole" type="text" name="current_role" value="{{ old('current_role', $cp?->current_role) }}"
                               class="form-control" placeholder="e.g. Software Engineer" />
                    </div>
                    <div class="col-sm-6">
                        <label for="targetRole" class="form-label small fw-medium">Target Role</label>
                        <input id="targetRole" type="text" name="target_role" value="{{ old('target_role', $cp?->target_role) }}"
                               class="form-control" placeholder="e.g. Senior SWE at FAANG" />
                    </div>
                    <div class="col-sm-6">
                        <label for="yearsExp" class="form-label small fw-medium">Years of Experience</label>
                        <input id="yearsExp" type="number" name="years_of_experience" value="{{ old('years_of_experience', $cp?->years_of_experience) }}"
                               min="0" class="form-control" />
                    </div>
                    <div class="col-sm-6">
                        <label for="prepLevel" class="form-label small fw-medium">Preparation Level</label>
                        <select id="prepLevel" name="preparation_level" class="form-select">
                            @foreach(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('preparation_level', $cp?->preparation_level) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Interviewer-specific --}}
        @if($user->isInterviewer())
        @php $ip = $user->interviewerProfile; @endphp
        <div class="card stat-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">Interviewer Details</h6>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="currentCompany" class="form-label small fw-medium">Current Company</label>
                        <input id="currentCompany" type="text" name="current_company" value="{{ old('current_company', $ip?->current_company) }}"
                               class="form-control" />
                    </div>
                    <div class="col-sm-6">
                        <label for="currentTitle" class="form-label small fw-medium">Current Title *</label>
                        <input id="currentTitle" type="text" name="current_title" value="{{ old('current_title', $ip?->current_title) }}"
                               required class="form-control" />
                    </div>
                    <div class="col-sm-6">
                        <label for="ipYearsExp" class="form-label small fw-medium">Years of Experience *</label>
                        <input id="ipYearsExp" type="number" name="years_of_experience" value="{{ old('years_of_experience', $ip?->years_of_experience) }}"
                               min="0" required class="form-control" />
                    </div>
                    <div class="col-sm-6">
                        <label for="expLevel" class="form-label small fw-medium">Experience Level *</label>
                        <select id="expLevel" name="experience_level" required class="form-select">
                            @foreach(['senior','staff','principal','distinguished'] as $l)
                                <option value="{{ $l }}" @selected(old('experience_level', $ip?->experience_level) === $l)>{{ ucfirst($l) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="hourlyRate" class="form-label small fw-medium">Hourly Rate ($) *</label>
                        <input id="hourlyRate" type="number" name="hourly_rate" value="{{ old('hourly_rate', $ip?->hourly_rate) }}"
                               min="0" step="5" required class="form-control" />
                    </div>
                    <div class="col-sm-6">
                        <label for="sessionDuration" class="form-label small fw-medium">Session Duration *</label>
                        <select id="sessionDuration" name="session_duration_minutes" required class="form-select">
                            @foreach([30, 45, 60, 90, 120] as $d)
                                <option value="{{ $d }}" @selected(old('session_duration_minutes', $ip?->session_duration_minutes) == $d)>{{ $d }} minutes</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Domains --}}
                    <div class="col-12">
                        <p class="form-label small fw-medium mb-2">Domains *</p>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['backend','frontend','devops','aiml','mobile','fullstack','security'] as $d)
                                <input type="checkbox" class="btn-check" name="domains[]" value="{{ $d }}"
                                       id="domain_{{ $d }}" autocomplete="off"
                                       @if(in_array($d, $ip?->domains ?? [])) checked @endif />
                                <label class="btn btn-sm btn-outline-primary rounded-pill" for="domain_{{ $d }}">
                                    {{ ucfirst($d === 'aiml' ? 'AI/ML' : $d) }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Interview types --}}
                    <div class="col-12">
                        <p class="form-label small fw-medium mb-2">Interview Types *</p>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['dsa' => 'DSA', 'system_design' => 'System Design', 'behavioral' => 'Behavioral', 'mixed' => 'Mixed'] as $val => $label)
                                <input type="checkbox" class="btn-check" name="interview_types[]" value="{{ $val }}"
                                       id="itype_{{ $val }}" autocomplete="off"
                                       @if(in_array($val, $ip?->interview_types ?? [])) checked @endif />
                                <label class="btn btn-sm btn-outline-secondary rounded-pill" for="itype_{{ $val }}">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="interviewApproach" class="form-label small fw-medium">Interview Approach</label>
                        <textarea id="interviewApproach" name="interview_approach" rows="3" class="form-control"
                                  placeholder="Describe how you conduct your interviews…">{{ old('interview_approach', $ip?->interview_approach) }}</textarea>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="is_available" value="1" id="isAvailable"
                                   class="form-check-input" @if($ip?->is_available) checked @endif />
                            <label for="isAvailable" class="form-check-label small">Available for bookings</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <button type="submit" class="btn btn-primary w-100 fw-semibold py-3">Save Changes</button>
    </form>
</div>
</div>
@endsection
