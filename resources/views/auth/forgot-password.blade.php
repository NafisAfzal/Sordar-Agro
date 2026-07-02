@extends('layouts.guest')
@section('title', 'Forgot Password')
@section('content')
    <h4 class="mb-3 text-center">Reset your password</h4>
    <p class="text-muted small">Enter your email and we'll send a reset link.
       (With the log mail driver, the link is written to <code>storage/logs/laravel.log</code>.)</p>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
        </div>
        <button class="btn btn-sa w-100">Send reset link</button>
    </form>
    <p class="text-center mt-3 mb-0 small"><a href="{{ route('login') }}">Back to login</a></p>
