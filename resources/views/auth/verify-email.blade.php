@extends('layouts.guest')
@section('title', 'Verify Your Email')
@section('content')
    <h4 class="mb-3 text-center">Verify your email</h4>
    <p class="text-muted small text-center">
        Thanks for registering! Before getting started, please check your email
        for a verification link.
    </p>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="btn btn-sa w-100">Resend verification email</button>
    </form>
    <p class="text-center mt-3 mb-0 small">
        <a href="{{ route('logout') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Log out
        </a>
    </p>
    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
@endsection
