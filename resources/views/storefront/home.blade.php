@extends('layouts.app')
@section('title', 'Sordar Agro — Aquarium Marketplace')
@section('content')
    <section class="hero p-4 p-md-5 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold">Bring the ocean home 🐠</h1>
                <p class="lead mb-3">Healthy fish (sold in pairs), lush plants, quality foods and equipment — all in one marketplace.</p>
                <a href="{{ route('products.index') }}" class="btn btn-light text-sa fw-semibold">Browse the shop</a>
            </div>
            <div class="col-md-4 text-center d-none d-md-block">
                <i class="bi bi-water" style="font-size:7rem;opacity:.85;"></i>
            </div>
        </div>
    </section>

    <h4 class="mb-3">Shop by category</h4>
    <div class="row g-3 mb-5">
        @forelse ($categories as $category)
            <div class="col-6 col-md-3">
                <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                   class="card text-center text-decoration-none product-card p-3 h-100">
                    <div class="fs-2 text-sa"><i class="bi bi-tags"></i></div>
                    <div class="fw-semibold text-dark">{{ $category->name }}</div>
                    <small class="text-muted">{{ $category->products_count }} items</small>
                </a>
            </div>
        @empty
            <p class="text-muted">No categories yet. Run the seeder to add sample data.</p>
        @endforelse
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Best selling</h4>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">See more</a>
    </div>
    <div class="row g-3 mb-5">
        @forelse ($featured as $product)
            <div class="col-6 col-md-3">@include('partials.product-card')</div>
        @empty
            <p class="text-muted">No products yet. Run <code>php artisan migrate --seed</code>.</p>
        @endforelse
    </div>

    @if ($guides->isNotEmpty())
        <h4 class="mb-3">Latest care guides</h4>
        <div class="row g-3">
            @foreach ($guides as $guide)
                <div class="col-md-4">
                    <a href="{{ route('care.show', $guide) }}" class="card product-card text-decoration-none h-100">
                        @if ($guide->image)
                            <img src="{{ asset('storage/'.$guide->image) }}" class="card-img-top product-thumb" alt="">
                        @else
                            <div class="thumb-placeholder"><i class="bi bi-journal-text"></i></div>
                        @endif
                        <div class="card-body">
                            <h6 class="text-dark">{{ $guide->title }}</h6>
                            <p class="text-muted small mb-0">{{ Str::limit($guide->excerpt, 90) }}</p>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
@endsection
