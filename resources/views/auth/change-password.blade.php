@extends('layouts.guest')
@section('title', 'Change Password')
@section('content')
    <h4 class="mb-3 text-center">Change your password</h4>
    @if (auth()->user()->must_change_password)
        <div class="alert alert-warning small">
            You're using a temporary password. Please set a new one to continue.
        </div>
    @endif
    <form method="POST" action="{{ route('password.change.update') }}">
        @csrf
        @unless (auth()->user()->must_change_password)
            <div class="mb-3">
                <label class="form-label">Current password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
        @endunless
        <div class="mb-3">
            <label class="form-label">New password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm new password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button class="btn btn-sa w-100">Update password</button>
    </form>
@endsection
