@extends('layouts.guest')
@section('title', 'Reset Password')
@section('content')
    <h4 class="mb-3 text-center">Choose a new password</h4>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $email) }}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">New password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm new password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button class="btn btn-sa w-100">Reset password</button>
    </form>
@endsection
