@extends('layouts.dashboard')
@section('title', 'Edit Product')
@section('sidebar') @include('partials.seller-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Edit product</h3>
    @if ($product->status === 'rejected' && $product->admin_feedback)
        <div class="alert alert-warning"><strong>Admin feedback:</strong> {{ $product->admin_feedback }}</div>
    @endif
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('seller.products.update', $product) }}" enctype="multipart/form-data">
                @csrf @method('PUT')
                @include('seller.products._form')
                <hr>
                <button class="btn btn-sa">Update &amp; re-submit</button>
                <a href="{{ route('seller.products.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
