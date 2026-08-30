@extends('layouts.app')
@section('title', 'Sordar Agro — Aquarium Marketplace')
@section('meta_description', 'Shop healthy aquarium fish, aquatic plants, fish food and equipment from Sordar Agro, with care guides and tank-suitability information for hobbyists in Bangladesh.')
@section('canonical_url', url('/'))
@section('content')
@php
    // Map category slug to representative product image for polished tile imagery
    $categoryImage = [
        'fish' => 'products/neon-tetra.webp',
        'aquatic-plants' => 'products/java-fern.webp',
        'fish-food' => 'products/premium-flake-food-100g.webp',
        'equipment' => 'products/aquarium-filter-1200lh.webp',
    ];
@endphp

    {{-- Hero: compact, aquarium-specific, no oversized gradient banner --}}
    <section class="home-hero mb-4 mb-md-5">
        <div class="home-hero-inner">
            <div class="home-hero-copy">
                <p class="home-hero-kicker">Aquarium marketplace · Dhaka, Bangladesh</p>
                <h1 class="home-hero-title">Build a healthier aquarium.</h1>
                <p class="home-hero-lead">Healthy fish, aquatic plants, food and equipment — curated for hobbyists, with clear tank-suitability and care information.</p>
                <div class="home-hero-actions">
                    <a href="{{ route('products.index', ['category' => 'fish']) }}" class="btn btn-sa fw-semibold">Shop Fish</a>
                    <a href="{{ route('products.index', ['category' => 'equipment']) }}" class="btn btn-sa-outline fw-semibold">Explore Equipment</a>
                </div>
                <div class="home-hero-meta">
                    <span><i class="bi bi-water me-1"></i> Tank-size guidance</span>
                    <span><i class="bi bi-shield-check me-1"></i> bKash / Nagad</span>
                    <span><i class="bi bi-journal-text me-1"></i> Care guides</span>
                </div>
            </div>
            <div class="home-hero-visual" aria-hidden="true">
                <div class="home-hero-img-main">
                    <img src="{{ asset('storage/products/neon-tetra.webp') }}" alt="" loading="eager" decoding="async">
                </div>
                <div class="home-hero-img-stack">
                    <div class="home-hero-img-sm">
                        <img src="{{ asset('storage/products/betta-splendens.webp') }}" alt="" loading="eager" decoding="async">
                    </div>
                    <div class="home-hero-img-sm">
                        <img src="{{ asset('storage/products/java-fern.webp') }}" alt="" loading="eager" decoding="async">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Shop by Category: polished image tiles, aquarium-specific --}}
    <section class="home-section">
        <div class="home-section-head">
            <h2 class="home-section-title">Shop by category</h2>
            <a href="{{ route('products.index') }}" class="home-section-link">Browse all <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3">
            @foreach ($categories as $category)
                @if (in_array($category->slug, ['fish', 'aquatic-plants', 'fish-food', 'equipment']))
                    <div class="col-6 col-lg-3">
                        <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="cat-tile">
                            <div class="cat-tile-img-wrap">
                                @if (isset($categoryImage[$category->slug]))
                                    <img src="{{ asset('storage/'.$categoryImage[$category->slug]) }}" alt="{{ $category->name }}" class="cat-tile-img" loading="lazy" decoding="async">
                                @else
                                    <div class="cat-tile-icon"><i class="bi {{ $category->getIconClassAttribute() }}"></i></div>
                                @endif
                            </div>
                            <div class="cat-tile-body">
                                <h3 class="cat-tile-name">{{ $category->name }}</h3>
                                <span class="cat-tile-count">{{ $category->products_count }} {{ Str::plural('product', $category->products_count) }}</span>
                            </div>
                        </a>
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    {{-- Best Sellers: dynamic, query-preserved --}}
    <section class="home-section">
        <div class="home-section-head">
            <div>
                <h2 class="home-section-title mb-1">Best Sellers</h2>
                <p class="home-section-subtitle mb-0">Most purchased across paid orders — updated live.</p>
            </div>
            <a href="{{ route('products.index') }}" class="btn btn-sa-outline btn-sm d-none d-md-inline-flex">See all products</a>
        </div>
        <div class="row g-3 g-md-4">
            @if (count($featured) > 0)
                @foreach ($featured as $product)
                    <div class="col-6 col-lg-3">
                        @include('partials.product-card', ['product' => $product, 'loading' => 'lazy'])
                    </div>
                @endforeach
            @else
                <div class="col-12">
                    <div class="home-empty">
                        <div class="home-empty-icon"><i class="bi bi-star"></i></div>
                        <p class="mb-1 fw-semibold">Best sellers will appear here</p>
                        <p class="small text-muted mb-3">Once orders are placed, top products are ranked automatically.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-sa btn-sm">Browse products</a>
                    </div>
                </div>
            @endif
        </div>
        @if (count($featured) > 0)
            <div class="text-center mt-3 d-md-none">
                <a href="{{ route('products.index') }}" class="btn btn-sa-outline btn-sm">See all products</a>
            </div>
        @endif
    </section>

    {{-- Aquarium Finder --}}
    <section class="home-section">
        <div class="finder-card">
            <div class="row g-4 align-items-center">
                <div class="col-lg-5">
                    <h2 class="home-section-title mb-2">Find fish for your tank</h2>
                    <p class="home-section-subtitle mb-0">Filter by tank size, budget and temperament to see compatible options.</p>
                    <ul class="finder-points">
                        <li><i class="bi bi-check2 me-2"></i>Minimum tank size guidance</li>
                        <li><i class="bi bi-check2 me-2"></i>Peaceful / semi-aggressive / aggressive</li>
                        <li><i class="bi bi-check2 me-2"></i>Live compatibility info</li>
                    </ul>
                </div>
                <div class="col-lg-7">
                    <form action="{{ route('products.index') }}" method="GET" class="finder-form">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="finder_tank">Tank size (litres)</label>
                                <input type="number" name="tank_size" id="finder_tank" class="form-control" placeholder="e.g. 60" min="0">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="finder_budget">Budget from (৳)</label>
                                <input type="number" name="min_price" id="finder_budget" class="form-control" placeholder="From ৳" min="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="finder_temperament">Temperament</label>
                                <select name="temperament" id="finder_temperament" class="form-select">
                                    <option value="">Any temperament</option>
                                    <option value="peaceful">Peaceful</option>
                                    <option value="semi-aggressive">Semi-aggressive</option>
                                    <option value="aggressive">Aggressive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sa w-100">Show Suitable Fish</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- Aquarium Essentials --}}
    <section class="home-section">
        <h2 class="home-section-title mb-3">Aquarium essentials</h2>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="essentials-card">
                    <div class="essentials-icon"><i class="bi bi-box-seam"></i></div>
                    <h3 class="essentials-title">Fish Food</h3>
                    <p class="essentials-desc">Flakes and pellets for daily nutrition — tropical, bottom-feeder and specialty blends.</p>
                    <a href="{{ route('products.index', ['category' => 'fish-food']) }}" class="essentials-link">View food <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="essentials-card">
                    <div class="essentials-icon"><i class="bi bi-gear"></i></div>
                    <h3 class="essentials-title">Equipment</h3>
                    <p class="essentials-desc">Filtration, lighting and heating — reliable gear for stable, healthy water.</p>
                    <a href="{{ route('products.index', ['category' => 'equipment']) }}" class="essentials-link">View equipment <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="essentials-card">
                    <div class="essentials-icon"><i class="bi bi-flower1"></i></div>
                    <h3 class="essentials-title">Aquatic Plants</h3>
                    <p class="essentials-desc">Low-light and lush background plants to keep water healthy and tanks natural.</p>
                    <a href="{{ route('products.index', ['category' => 'aquatic-plants']) }}" class="essentials-link">View plants <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </section>

    {{-- Trust --}}
    <section class="home-section">
        <div class="trust-card">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <h2 class="home-section-title mb-3">Why hobbyists choose Sordar Agro</h2>
                    <div class="trust-list">
                        <div class="trust-item-inline">
                            <span class="trust-check"><i class="bi bi-check-lg"></i></span>
                            <span>Clear tank-size and temperament info for every fish</span>
                        </div>
                        <div class="trust-item-inline">
                            <span class="trust-check"><i class="bi bi-check-lg"></i></span>
                            <span>Live plant and equipment guidance for beginners</span>
                        </div>
                        <div class="trust-item-inline">
                            <span class="trust-check"><i class="bi bi-check-lg"></i></span>
                            <span>Secure local payments via bKash / Nagad</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="trust-stats">
                        <div class="trust-stat">
                            <div class="trust-stat-num"><i class="bi bi-droplet"></i></div>
                            <div class="trust-stat-label">Tank suitability</div>
                        </div>
                        <div class="trust-stat">
                            <div class="trust-stat-num"><i class="bi bi-heart-pulse"></i></div>
                            <div class="trust-stat-label">Healthy livestock</div>
                        </div>
                        <div class="trust-stat">
                            <div class="trust-stat-num"><i class="bi bi-journal-text"></i></div>
                            <div class="trust-stat-label">Care guides</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Care Guides --}}
    @if (count($guides) > 0)
    <section class="home-section">
        <div class="home-section-head">
            <h2 class="home-section-title">Care guides</h2>
            <a href="{{ route('care.index') }}" class="home-section-link">All guides <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3">
            @foreach ($guides as $guide)
                <div class="col-12 col-md-4">
                    <a href="{{ route('care.show', $guide->slug) }}" class="care-card">
                        @if ($guide->image)
                            <img src="{{ asset('storage/'.$guide->image) }}" class="care-card-img" alt="{{ $guide->title }}" loading="lazy" decoding="async">
                        @else
                            <div class="care-card-placeholder"><i class="bi bi-journal-text"></i></div>
                        @endif
                        <div class="care-card-body">
                            <h3 class="care-card-title">{{ $guide->title }}</h3>
                            <p class="care-card-excerpt">{{ Str::limit($guide->excerpt, 90) }}</p>
                            <span class="care-card-cta">Read guide <i class="bi bi-arrow-right ms-1"></i></span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Community --}}
    @if (isset($submissions) && count($submissions) > 0)
    <section class="home-section">
        <div class="home-section-head">
            <h2 class="home-section-title">From the community</h2>
            <a href="{{ route('community.index') }}" class="home-section-link">View community <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3">
            @foreach ($submissions as $submission)
                <div class="col-12 col-md-6">
                    <div class="community-card">
                        <h3 class="community-title">{{ Str::limit($submission->title, 50) }}</h3>
                        <p class="community-body">{{ Str::limit($submission->body, 110) }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Final CTA --}}
    <section class="home-cta">
        <h2 class="home-cta-title">Ready to build your aquarium?</h2>
        <p class="home-cta-sub">Browse the full collection — fish, plants, food and equipment in one place.</p>
        <a href="{{ route('products.index') }}" class="btn btn-sa btn-lg px-5">Shop the collection</a>
    </section>
@endsection
