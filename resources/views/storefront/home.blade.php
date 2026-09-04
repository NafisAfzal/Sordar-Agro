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

    {{-- Hero: compact, aquarium-specific — single dominant visual for balance --}}
    <section class="home-hero mb-4 mb-md-5">
        <div class="home-hero-inner">
            <div class="home-hero-copy">
                <p class="home-hero-kicker">Aquarium marketplace · Dhaka, Bangladesh</p>
                <h1 class="home-hero-title">Build a healthier aquarium.</h1>
                <p class="home-hero-lead">Healthy fish, aquatic plants, food and equipment — curated for hobbyists, with clear tank-suitability and care information.</p>
                <div class="home-hero-actions">
                    <a href="{{ route('products.index', ['category' => 'fish']) }}" class="btn btn-sa fw-semibold px-4">Shop Fish</a>
                    <a href="{{ route('products.index') }}" class="btn btn-sa-outline fw-semibold">Browse all</a>
                </div>
            </div>
            <div class="home-hero-visual">
                <div class="hero-carousel" role="region" aria-roledescription="carousel" aria-label="Featured aquarium products">
                    <div class="hero-carousel-viewport">
                        <div class="hero-carousel-track">
                            <div class="hero-slide is-active" role="group" aria-roledescription="slide" aria-label="1 of 3">
                                <div class="home-hero-img-main">
                                    <img src="{{ asset('storage/products/neon-tetra.webp') }}" alt="Neon Tetra — peaceful schooling fish for planted aquariums" loading="eager" decoding="async">
                                </div>
                            </div>
                            <div class="hero-slide" role="group" aria-roledescription="slide" aria-label="2 of 3">
                                <div class="home-hero-img-main">
                                    <img src="{{ asset('storage/products/betta-splendens.webp') }}" alt="Betta Splendens — vibrant centrepiece fish" loading="eager" decoding="async">
                                </div>
                            </div>
                            <div class="hero-slide" role="group" aria-roledescription="slide" aria-label="3 of 3">
                                <div class="home-hero-img-main">
                                    <img src="{{ asset('storage/products/java-fern.webp') }}" alt="Java Fern — hardy aquatic plant for beginners" loading="eager" decoding="async">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="hero-carousel-btn hero-carousel-prev" aria-label="Previous slide"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="hero-carousel-btn hero-carousel-next" aria-label="Next slide"><i class="bi bi-chevron-right"></i></button>
                    <div class="hero-carousel-dots" role="tablist" aria-label="Carousel pagination">
                        <button type="button" role="tab" aria-label="Go to slide 1" aria-selected="true" data-slide="0" class="is-active"></button>
                        <button type="button" role="tab" aria-label="Go to slide 2" aria-selected="false" data-slide="1"></button>
                        <button type="button" role="tab" aria-label="Go to slide 3" aria-selected="false" data-slide="2"></button>
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

    {{-- Care Guides — curated educational content, lighter than Best Sellers --}}
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

    {{-- Community — kept only if approved submissions exist --}}
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

@push('scripts')
<script>
(function () {
    const carousel = document.querySelector('.hero-carousel');
    if (!carousel) return;
    const track = carousel.querySelector('.hero-carousel-track');
    const slides = carousel.querySelectorAll('.hero-slide');
    const prevBtn = carousel.querySelector('.hero-carousel-prev');
    const nextBtn = carousel.querySelector('.hero-carousel-next');
    const dots = carousel.querySelectorAll('.hero-carousel-dots button');
    if (!track || slides.length !== 3) return;

    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let index = 0;
    let timer = null;
    const interval = 2500;

    function update() {
        track.style.transform = 'translateX(' + (-index * 100) + '%)';
        slides.forEach((s, i) => {
            s.classList.toggle('is-active', i === index);
        });
        dots.forEach((d, i) => {
            const active = i === index;
            d.classList.toggle('is-active', active);
            d.setAttribute('aria-selected', active ? 'true' : 'false');
            d.tabIndex = active ? 0 : -1;
        });
    }

    function goTo(i) {
        index = (i + slides.length) % slides.length;
        update();
    }

    function next() { goTo(index + 1); }
    function prev() { goTo(index - 1); }

    function start() {
        if (prefersReduced) return;
        stop();
        timer = setInterval(next, interval);
    }
    function stop() {
        if (timer) { clearInterval(timer); timer = null; }
    }

    prevBtn.addEventListener('click', function () { prev(); start(); });
    nextBtn.addEventListener('click', function () { next(); start(); });
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            goTo(parseInt(dot.getAttribute('data-slide'), 10));
            start();
        });
    });

    carousel.addEventListener('mouseenter', stop);
    carousel.addEventListener('mouseleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', start);

    carousel.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft') { e.preventDefault(); prev(); start(); }
        if (e.key === 'ArrowRight') { e.preventDefault(); next(); start(); }
    });

    carousel.setAttribute('tabindex', '0');

    update();
    start();
})();
</script>
@endpush
