@extends('layouts.guest')
@section('title', 'Forgot Password')

@section('content')
<h4 class="fw-bold mb-1">Reset your password</h4>
<p class="text-muted small mb-4">Enter your email and we'll send you a reset code.</p>

<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-4">
        <label for="email" class="form-label fw-medium">Email address</label>
        <input type="email" id="email" name="email"
               value="{{ old('email') }}"
               class="form-control" placeholder="you@example.com" required />
    </div>
    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        Send Reset Code
    </button>
</form>

<div class="text-center mt-4">
    <a href="{{ route('login') }}" class="small text-decoration-none">← Back to login</a>
</div>
@endsection
