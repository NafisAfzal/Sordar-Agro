@extends('layouts.guest')
@section('title', 'Register')
@section('content')
    <h4 class="mb-3 text-center">Create your account</h4>
    <p class="text-muted small text-center">Public sign-up creates a <strong>Customer</strong> account.
       Seller accounts are provisioned by an administrator.</p>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="reg_name">Full name</label>
            <input type="text" name="name" id="reg_name" value="{{ old('name') }}" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="reg_email">Email</label>
            <input type="email" name="email" id="reg_email" value="{{ old('email') }}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="reg_phone">Phone <span class="text-muted">(optional)</span></label>
            <input type="text" name="phone" id="reg_phone" value="{{ old('phone') }}" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label" for="reg_password">Password</label>
            <input type="password" name="password" id="reg_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="reg_password_confirmation">Confirm password</label>
            <input type="password" name="password_confirmation" id="reg_password_confirmation" class="form-control" required>
        </div>
        <button class="btn btn-sa w-100">Register</button>
    </form>
    <p class="text-center mt-3 mb-0 small">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
@endsection
