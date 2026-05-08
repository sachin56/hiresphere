<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Create Account — HireSphere</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">

<div class="container-fluid min-vh-100">
    <div class="row min-vh-100 g-0">

        {{-- Left panel --}}
        <div class="col-lg-5 auth-panel-left d-none d-lg-flex flex-column justify-content-between p-5">
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center justify-content-center rounded-3"
                     style="width:40px;height:40px;background:rgba(255,255,255,.2)">
                    <span class="text-white fw-bold">H</span>
                </div>
                <span class="text-white fw-semibold fs-5">HireSphere</span>
            </div>

            <div>
                <h1 class="display-6 fw-bold text-white mb-3">Land your dream<br/>tech role faster.</h1>
                <p class="text-white opacity-75 mb-5">Practice with engineers from Google, Meta, Amazon and OpenAI — then walk into your real interview with confidence.</p>
                <ul class="list-unstyled d-flex flex-column gap-3">
                    @foreach(['Live mock interviews with real-time video','Detailed AI-powered evaluation reports','Code submissions with expert annotations'] as $f)
                    <li class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center rounded-2"
                             style="width:32px;height:32px;background:rgba(255,255,255,.15);flex-shrink:0">
                            <svg width="14" height="14" fill="none" stroke="white" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="text-white opacity-75 small">{{ $f }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-3 p-4" style="background:rgba(255,255,255,.12)">
                <div class="d-flex mb-2" style="gap:2px">
                    @for($i=0;$i<5;$i++)<svg width="14" height="14" viewBox="0 0 20 20" fill="#FBBF24"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>@endfor
                </div>
                <p class="text-white fst-italic mb-3 small">"After 3 sessions I got an offer from Stripe. The feedback was exactly what I needed."</p>
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                         style="width:32px;height:32px;background:rgba(255,255,255,.25);font-size:.75rem;flex-shrink:0">AK</div>
                    <div>
                        <div class="text-white fw-semibold" style="font-size:.8rem">Arjun K.</div>
                        <div class="text-white opacity-50" style="font-size:.72rem">Software Engineer · Stripe</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right panel --}}
        <div class="col-lg-7 bg-white d-flex align-items-center justify-content-center p-4 p-sm-5 overflow-auto">
            <div class="w-100 py-3" style="max-width:480px">

                <div class="d-flex align-items-center gap-2 mb-4 d-lg-none">
                    <div class="d-flex align-items-center justify-content-center rounded-3"
                         style="width:36px;height:36px;background:#4f46e5">
                        <span class="text-white fw-bold">H</span>
                    </div>
                    <span class="fw-bold fs-5">HireSphere</span>
                </div>

                <h2 class="fw-bold mb-1">Create your account</h2>
                <p class="text-muted mb-4">Join thousands of engineers levelling up their interview skills</p>

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif
                @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('register.post') }}" id="registerForm">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="name" class="form-label fw-medium">Full name</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}"
                                   class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                   placeholder="Jane Smith" required autocomplete="name" />
                        </div>
                        <div class="col-sm-6">
                            <label for="phone" class="form-label fw-medium">
                                Phone <span class="text-muted fw-normal">(optional)</span>
                            </label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                                   class="form-control" placeholder="+1 555 000 0000" autocomplete="tel" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Email address</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               placeholder="jane@example.com" required autocomplete="email" />
                    </div>

                    {{-- Role selector --}}
                    <fieldset class="mb-3">
                        <legend class="form-label fw-medium">I want to join as a…</legend>
                        <div class="row g-3">
                            <div class="col-6">
                                <input type="radio" class="d-none" name="role" id="roleCandidate"
                                       value="candidate" {{ old('role','candidate') === 'candidate' ? 'checked' : '' }}
                                       onchange="updateRoleCards()" />
                                <label for="roleCandidate" class="role-card w-100 text-center {{ old('role','candidate') === 'candidate' ? 'selected' : '' }}" id="cardCandidate">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-2"
                                         style="width:44px;height:44px;background:#eef2ff">
                                        <svg width="20" height="20" fill="none" stroke="#4f46e5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div class="fw-semibold small text-primary">Candidate</div>
                                    <div class="text-muted" style="font-size:.75rem">Practice & get hired</div>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="d-none" name="role" id="roleInterviewer"
                                       value="interviewer" {{ old('role') === 'interviewer' ? 'checked' : '' }}
                                       onchange="updateRoleCards()" />
                                <label for="roleInterviewer" class="role-card w-100 text-center {{ old('role') === 'interviewer' ? 'selected' : '' }}" id="cardInterviewer">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-2"
                                         style="width:44px;height:44px;background:#f1f3f5">
                                        <svg width="20" height="20" fill="none" stroke="#6b7280" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="fw-semibold small text-dark">Interviewer</div>
                                    <div class="text-muted" style="font-size:.75rem">Mentor & earn</div>
                                </label>
                            </div>
                        </div>
                    </fieldset>

                    {{-- Password --}}
                    <div class="mb-3">
                        <label for="password" class="form-label fw-medium">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password"
                                   class="form-control border-end-0"
                                   placeholder="Min. 8 characters"
                                   required autocomplete="new-password"
                                   oninput="checkStrength(this.value)" />
                            <button type="button" class="input-group-text bg-white border-start-0"
                                    onclick="togglePassword('password', this)">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Strength meter --}}
                        <div id="strengthMeter" class="mt-2" style="display:none">
                            <div class="d-flex gap-1 mb-1" id="strengthBars">
                                <div class="strength-bar" id="bar1"></div>
                                <div class="strength-bar" id="bar2"></div>
                                <div class="strength-bar" id="bar3"></div>
                                <div class="strength-bar" id="bar4"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex gap-3">
                                    <span class="small text-muted" id="req-length">· 8+ chars</span>
                                    <span class="small text-muted" id="req-upper">· Uppercase</span>
                                    <span class="small text-muted" id="req-number">· Number</span>
                                    <span class="small text-muted" id="req-symbol">· Symbol</span>
                                </div>
                                <span class="small fw-medium" id="strengthLabel"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-medium">Confirm password</label>
                        <div class="input-group">
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control border-end-0"
                                   placeholder="••••••••"
                                   required autocomplete="new-password" />
                            <button type="button" class="input-group-text bg-white border-start-0"
                                    onclick="togglePassword('password_confirmation', this)">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="registerBtn">
                        Create Account
                    </button>
                </form>

                <hr class="my-4" />

                <a href="{{ route('login') }}" class="btn btn-outline-secondary w-100 py-2">
                    Already have an account? Sign in
                </a>

                <p class="text-center text-muted small mt-3 mb-0">
                    By creating an account you agree to our
                    <a href="#" class="text-decoration-none">Terms</a> and
                    <a href="#" class="text-decoration-none">Privacy Policy</a>
                </p>
            </div>
        </div>

    </div>
</div>

<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function updateRoleCards() {
    const isCandidate = document.getElementById('roleCandidate').checked;
    document.getElementById('cardCandidate').classList.toggle('selected', isCandidate);
    document.getElementById('cardInterviewer').classList.toggle('selected', !isCandidate);

    const candidateIcon = document.querySelector('#cardCandidate svg');
    const interviewerIcon = document.querySelector('#cardInterviewer svg');
    const candidateTitle = document.querySelector('#cardCandidate .fw-semibold');
    const interviewerTitle = document.querySelector('#cardInterviewer .fw-semibold');
    const candidateBg = document.querySelector('#cardCandidate .rounded-circle');
    const interviewerBg = document.querySelector('#cardInterviewer .rounded-circle');

    if (isCandidate) {
        candidateIcon.setAttribute('stroke', '#4f46e5');
        candidateBg.style.background = '#eef2ff';
        candidateTitle.className = 'fw-semibold small text-primary';
        interviewerIcon.setAttribute('stroke', '#6b7280');
        interviewerBg.style.background = '#f1f3f5';
        interviewerTitle.className = 'fw-semibold small text-dark';
    } else {
        interviewerIcon.setAttribute('stroke', '#4f46e5');
        interviewerBg.style.background = '#eef2ff';
        interviewerTitle.className = 'fw-semibold small text-primary';
        candidateIcon.setAttribute('stroke', '#6b7280');
        candidateBg.style.background = '#f1f3f5';
        candidateTitle.className = 'fw-semibold small text-dark';
    }
}

function checkStrength(val) {
    const meter = document.getElementById('strengthMeter');
    meter.style.display = val.length ? 'block' : 'none';

    const checks = {
        length: val.length >= 8,
        upper: /[A-Z]/.test(val),
        number: /[0-9]/.test(val),
        symbol: /[^A-Za-z0-9]/.test(val),
    };

    const score = Object.values(checks).filter(Boolean).length;
    const colors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#22c55e'];
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];

    for (let i = 1; i <= 4; i++) {
        document.getElementById('bar' + i).style.background = i <= score ? colors[score] : '#e9ecef';
    }

    const labelEl = document.getElementById('strengthLabel');
    labelEl.textContent = labels[score];
    labelEl.style.color = colors[score];

    const reqMap = { length: 'req-length', upper: 'req-upper', number: 'req-number', symbol: 'req-symbol' };
    const labels2 = { length: '✓ 8+ chars', upper: '✓ Uppercase', number: '✓ Number', symbol: '✓ Symbol' };
    const labels3 = { length: '· 8+ chars', upper: '· Uppercase', number: '· Number', symbol: '· Symbol' };

    for (const [key, elId] of Object.entries(reqMap)) {
        const el = document.getElementById(elId);
        el.textContent = checks[key] ? labels2[key] : labels3[key];
        el.className = checks[key] ? 'small text-success' : 'small text-muted';
    }
}

document.getElementById('registerForm').addEventListener('submit', function() {
    const btn = document.getElementById('registerBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating account…';
});
</script>
</body>
</html>
