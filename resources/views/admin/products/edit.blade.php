@extends('layouts.dashboard')
@section('title', 'Edit Product')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <a href="{{ route('admin.products.show', $product) }}" class="btn btn-outline-secondary btn-sm mb-3">← Back</a>
    <h3 class="mb-4">Edit product</h3>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
                @csrf @method('PUT')
                @include('seller.products._form')
                <hr>
                <button class="btn btn-sa">Save changes</button>
                <a href="{{ route('admin.products.show', $product) }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
