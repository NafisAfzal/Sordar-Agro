@extends('layouts.dashboard')
@section('title', 'Product Approvals')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Product approvals</h3>

    <ul class="nav nav-pills mb-3">
        @foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.products.index', ['status' => $key]) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    @if ($products->isEmpty())
        <div class="alert alert-info">No {{ $status }} products.</div>
    @else
        <div class="row g-3">
            @foreach ($products as $p)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        @if ($p->thumbnail)
                            <img src="{{ asset('storage/'.$p->thumbnail) }}" class="card-img-top product-thumb" alt="">
                        @else
                            <div class="thumb-placeholder"><i class="bi bi-water"></i></div>
                        @endif
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">{{ $p->name }}</h6>
                            <p class="small text-muted mb-2">
                                {{ $p->category->name ?? '—' }} ·
                                {{ $p->seller->name ?? 'Marketplace' }} ·
                                From ৳{{ number_format($p->starting_price, 2) }}
                            </p>
                            <a href="{{ route('admin.products.show', $p) }}" class="btn btn-sm btn-outline-secondary w-100">Review</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-3">{{ $products->links() }}</div>
    @endif
@endsection
