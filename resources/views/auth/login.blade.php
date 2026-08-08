@extends('layouts.guest')
@section('title', 'Login')
@section('content')
    <h4 class="mb-3 text-center">Welcome back</h4>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <div class="form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
        </div>
        <button class="btn btn-sa w-100">Log in</button>
    </form>
    <p class="text-center mt-3 mb-0 small">
        New here? <a href="{{ route('register') }}">Create a customer account</a>
    </p>
@endsection
