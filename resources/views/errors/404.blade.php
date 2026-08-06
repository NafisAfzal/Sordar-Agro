@extends('layouts.app')
@section('title', 'Page Not Found')
@section('content')
<div class="text-center py-5">
    <div class="display-1 fw-bold text-sa">404</div>
    <h3 class="mb-3">We couldn't find that page</h3>
    <p class="text-muted mb-4">
        The page you're looking for may have been moved, or the link might be broken.
    </p>
    <a href="{{ route('home') }}" class="btn btn-sa">
        <i class="bi bi-house"></i> Back to home
    </a>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary ms-2">
        <i class="bi bi-shop"></i> Browse the shop
    </a>
</div>
@endsection