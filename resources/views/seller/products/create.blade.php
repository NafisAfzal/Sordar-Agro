@extends('layouts.dashboard')
@section('title', 'Add Product')
@section('sidebar') @include('partials.seller-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Add a product</h3>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('seller.products.store') }}" enctype="multipart/form-data">
                @csrf
                @include('seller.products._form', ['product' => null])
                <hr>
                <button class="btn btn-sa">Submit for approval</button>
                <a href="{{ route('seller.products.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
