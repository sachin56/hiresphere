<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Sign In — HireSphere</title>
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
                <h1 class="display-6 fw-bold text-white mb-3">Your next offer<br/>starts here.</h1>
                <p class="text-white opacity-75 mb-5">
                    Get matched with engineers from Google, Meta, Amazon and OpenAI for realistic mock interviews with actionable feedback.
                </p>
                <div class="row g-3">
                    @foreach([['500+','Expert interviewers'],['4.9','Avg. rating'],['92%','Offer rate']] as $s)
                    <div class="col-4">
                        <div class="rounded-3 p-3 text-center" style="background:rgba(255,255,255,.12)">
                            <div class="text-white fw-bold fs-4">{{ $s[0] }}</div>
                            <div class="text-white opacity-75" style="font-size:.75rem">{{ $s[1] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3 p-4" style="background:rgba(255,255,255,.12)">
                <div class="d-flex mb-2" style="gap:2px">
                    @for($i=0;$i<5;$i++)<svg width="14" height="14" viewBox="0 0 20 20" fill="#FBBF24"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>@endfor
                </div>
                <p class="text-white fst-italic mb-3" style="font-size:.9rem">"Three sessions in, I got an offer from Meta. The feedback was brutal, honest and exactly what I needed."</p>
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                         style="width:34px;height:34px;background:rgba(255,255,255,.25);font-size:.8rem;flex-shrink:0">SM</div>
                    <div>
                        <div class="text-white fw-semibold" style="font-size:.8rem">Sara M.</div>
                        <div class="text-white opacity-50" style="font-size:.72rem">Software Engineer · Meta</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right panel --}}
        <div class="col-lg-7 d-flex align-items-center justify-content-center p-4 p-sm-5 bg-white">
            <div class="w-100" style="max-width:400px">

                <div class="d-flex align-items-center gap-2 mb-4 d-lg-none">
                    <div class="d-flex align-items-center justify-content-center rounded-3"
                         style="width:36px;height:36px;background:#4f46e5">
                        <span class="text-white fw-bold">H</span>
                    </div>
                    <span class="fw-bold fs-5">HireSphere</span>
                </div>

                <h2 class="fw-bold mb-1">Welcome back</h2>
                <p class="text-muted mb-4">Sign in to your HireSphere account</p>

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

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

                <form method="POST" action="{{ route('login.post') }}" id="loginForm">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Email address</label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email') }}"
                               class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               placeholder="you@example.com"
                               required autofocus autocomplete="email" />
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label fw-medium mb-0">Password</label>
                            <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot password?</a>
                        </div>
                        <div class="input-group">
                            <input type="password" id="password" name="password"
                                   class="form-control border-end-0"
                                   placeholder="••••••••"
                                   required autocomplete="current-password" />
                            <button type="button" class="input-group-text bg-white border-start-0"
                                    onclick="togglePassword('password', this)">
                                <svg class="icon-eye" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="loginBtn">
                        Sign In
                    </button>
                </form>

                <hr class="my-4" />

                <a href="{{ route('register') }}" class="btn btn-outline-secondary w-100 py-2">
                    Create a free account
                </a>
            </div>
        </div>

    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in…';
});
</script>
</body>
</html>
