@extends('layouts.guest')
@section('title', 'Confirm Email')

@section('content')
<div class="text-center mb-4">
    <div class="fs-1 mb-2">📧</div>
    <h4 class="fw-bold mb-1">Check your email</h4>
    <p class="text-muted small">We sent a 6-digit code to<br><strong>{{ $email ?? 'your email address' }}</strong></p>
</div>

<form method="POST" action="{{ route('auth.confirm.post') }}">
    @csrf

    <div class="mb-3">
        <label for="confirm-email" class="form-label fw-medium">Email address</label>
        <input type="email" id="confirm-email" name="email"
               value="{{ $email ?? old('email') }}"
               class="form-control" placeholder="your@email.com" required />
    </div>

    <div class="mb-4">
        <label for="code" class="form-label fw-medium">Confirmation code</label>
        <input type="text" id="code" name="code"
               class="form-control text-center fw-bold fs-4 font-monospace letter-spacing-wide"
               style="letter-spacing:.5rem"
               placeholder="123456" maxlength="6" required />
        <div class="form-text">Check your inbox and spam folder.</div>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        Verify Email
    </button>
</form>

<div class="text-center mt-4">
    <a href="{{ route('login') }}" class="small text-decoration-none">← Back to login</a>
</div>
@endsection
