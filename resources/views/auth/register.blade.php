@extends('layouts.guest')
@section('title', 'Register')
@section('content')
    <h4 class="mb-3 text-center">Create your account</h4>
    <p class="text-muted small text-center">Public sign-up creates a <strong>Customer</strong> account.
       Seller accounts are provisioned by an administrator.</p>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Full name</label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone <span class="text-muted">(optional)</span></label>
            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button class="btn btn-sa w-100">Register</button>
    </form>
    <p class="text-center mt-3 mb-0 small">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
@endsection
