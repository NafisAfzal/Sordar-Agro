@extends('layouts.app')
@section('title', 'Access Denied')
@section('content')
<div class="text-center py-5">
    <div class="display-1 fw-bold text-danger">403</div>
    <h3 class="mb-3">You don't have access to this page</h3>
    <p class="text-muted mb-4">
        This area is restricted. If you think this is a mistake, please contact support.
    </p>
    <a href="{{ route('home') }}" class="btn btn-sa">
        <i class="bi bi-house"></i> Back to home
    </a>
</div>
@endsection