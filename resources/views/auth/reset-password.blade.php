@extends('layouts.guest')
@section('title', 'Reset Password')

@section('content')
<h4 class="fw-bold mb-1">Set new password</h4>
<p class="text-muted small mb-4">Enter the code from your email and choose a new password.</p>

<form method="POST" action="{{ route('password.update') }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label fw-medium">Email address</label>
        <input type="email" id="email" name="email"
               value="{{ $email ?? old('email') }}"
               class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" required />
    </div>

    <div class="mb-3">
        <label for="code" class="form-label fw-medium">Reset code</label>
        <input type="text" id="code" name="code"
               class="form-control text-center fw-bold fs-5 font-monospace {{ $errors->has('code') ? 'is-invalid' : '' }}"
               style="letter-spacing:.4rem"
               placeholder="123456" maxlength="6" required />
    </div>

    <div class="mb-3">
        <label for="password" class="form-label fw-medium">New password</label>
        <input type="password" id="password" name="password"
               class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
               placeholder="Min. 8 characters"
               required autocomplete="new-password" />
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label fw-medium">Confirm new password</label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               class="form-control" required autocomplete="new-password" />
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        Reset Password
    </button>
</form>
@endsection
