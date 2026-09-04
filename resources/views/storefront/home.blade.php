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

    {{-- Hero: balanced desktop, single-column mobile, infinite seamless carousel --}}
    <section class="home-hero mb-4 mb-md-5">
        <div class="home-hero-inner">
            <div class="home-hero-copy">
                <p class="home-hero-kicker">Aquarium marketplace · Dhaka, Bangladesh</p>
                <h1 class="home-hero-title">Choose fish and plants that fit your tank.</h1>
                <p class="home-hero-lead">See tank size, temperament and care at a glance — find neon tetras, bettas, Java fern and more.</p>
                <div class="home-hero-actions">
                    <a href="{{ route('products.index', ['category' => 'fish']) }}" class="btn btn-sa fw-semibold px-4">Shop Fish</a>
                    <a href="{{ route('products.index') }}" class="btn btn-sa-outline fw-semibold">Browse all</a>
                </div>
            </div>
            <div class="home-hero-visual">
                <div class="hero-carousel" role="region" aria-roledescription="carousel" aria-label="Featured aquarium products">
                    <div class="hero-carousel-viewport">
                        <div class="hero-carousel-track">
                            {{-- Clone of last (for seamless previous from 1 → 3) --}}
                            <div class="hero-slide is-clone" aria-hidden="true" role="group" aria-roledescription="slide" aria-label="3 of 3">
                                <div class="home-hero-img-main">
                                    <img src="{{ asset('storage/products/java-fern.webp') }}" alt="" loading="eager" decoding="async" aria-hidden="true">
                                    <div class="hero-slide-caption" aria-hidden="true"><span class="hero-slide-badge">Plant</span><strong>Java Fern</strong><span>Hardy, low-light plant — easy for first tanks</span></div>
                                </div>
                            </div>
                            <div class="hero-slide is-active" role="group" aria-roledescription="slide" aria-label="1 of 3">
                                <div class="home-hero-img-main">
                                    <img src="{{ asset('storage/products/neon-tetra.webp') }}" alt="Neon Tetra — peaceful schooling fish for planted community tanks" loading="eager" decoding="async">
                                    <div class="hero-slide-caption"><span class="hero-slide-badge">Fish</span><strong>Neon Tetra</strong><span>Peaceful schooling fish for planted community tanks</span></div>
                                </div>
                            </div>
                            <div class="hero-slide" role="group" aria-roledescription="slide" aria-label="2 of 3">
                                <div class="home-hero-img-main">
                                    <img src="{{ asset('storage/products/betta-splendens.webp') }}" alt="Betta Splendens — vibrant centrepiece fish, best kept singly in calm water" loading="eager" decoding="async">
                                    <div class="hero-slide-caption"><span class="hero-slide-badge">Fish</span><strong>Betta Splendens</strong><span>Vibrant centrepiece — best kept singly in calm water</span></div>
                                </div>
                            </div>
                            <div class="hero-slide" role="group" aria-roledescription="slide" aria-label="3 of 3">
                                <div class="home-hero-img-main">
                                    <img src="{{ asset('storage/products/java-fern.webp') }}" alt="Java Fern — hardy low-light plant, easy for beginners" loading="eager" decoding="async">
                                    <div class="hero-slide-caption"><span class="hero-slide-badge">Plant</span><strong>Java Fern</strong><span>Hardy, low-light plant — easy for first tanks</span></div>
                                </div>
                            </div>
                            {{-- Clone of first (for seamless next from 3 → 1) --}}
                            <div class="hero-slide is-clone" aria-hidden="true" role="group" aria-roledescription="slide" aria-label="1 of 3">
                                <div class="home-hero-img-main">
                                    <img src="{{ asset('storage/products/neon-tetra.webp') }}" alt="" loading="eager" decoding="async" aria-hidden="true">
                                    <div class="hero-slide-caption" aria-hidden="true"><span class="hero-slide-badge">Fish</span><strong>Neon Tetra</strong><span>Peaceful schooling fish for planted community tanks</span></div>
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
    // Infinite: [cloneLast, 1,2,3, cloneFirst] = 5
    if (!track || slides.length !== 5) return;
    const realCount = 3;
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let current = 1; // real 1
    let isAnimating = false;
    let timer = null;
    const interval = 2500;

    function dotIndex() { return (current - 1 + realCount) % realCount; }

    function setTransform(withTransition) {
        if (prefersReduced) {
            track.style.transition = 'none';
        } else {
            track.style.transition = withTransition ? 'transform 0.45s ease' : 'none';
        }
        track.style.transform = 'translateX(' + (-current * 100) + '%)';
    }

    function updateDots() {
        const di = dotIndex();
        dots.forEach((d, i) => {
            const active = i === di;
            d.classList.toggle('is-active', active);
            d.setAttribute('aria-selected', active ? 'true' : 'false');
            d.tabIndex = active ? 0 : -1;
        });
        slides.forEach((s, i) => {
            // only real slides get is-active for a11y, clones never
            const isRealActive = i === current && i >= 1 && i <= realCount;
            s.classList.toggle('is-active', isRealActive);
        });
    }

    function jumpTo(idxWithoutTransition) {
        current = idxWithoutTransition;
        setTransform(false);
        // force reflow before restoring transition
        void track.offsetHeight;
        if (!prefersReduced) track.style.transition = 'transform 0.45s ease';
        updateDots();
    }

    function goToReal(realIdx) {
        if (isAnimating) return;
        if (prefersReduced) {
            current = realIdx + 1;
            setTransform(false);
            updateDots();
            return;
        }
        isAnimating = true;
        current = realIdx + 1;
        setTransform(true);
        updateDots();
    }

    function next() {
        if (isAnimating) return;
        if (prefersReduced) {
            current = current % realCount + 1;
            setTransform(false);
            updateDots();
            return;
        }
        isAnimating = true;
        current += 1;
        setTransform(true);
        updateDots();
    }
    function prev() {
        if (isAnimating) return;
        if (prefersReduced) {
            current = (current - 2 + realCount) % realCount + 1;
            setTransform(false);
            updateDots();
            return;
        }
        isAnimating = true;
        current -= 1;
        setTransform(true);
        updateDots();
    }

    track.addEventListener('transitionend', function (e) {
        if (e.propertyName !== 'transform') return;
        if (current === 0) {
            // landed on cloneLast -> snap to real 3
            jumpTo(realCount);
            isAnimating = false;
        } else if (current === realCount + 1) {
            // landed on cloneFirst -> snap to real 1
            jumpTo(1);
            isAnimating = false;
        } else {
            isAnimating = false;
        }
    });

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
            goToReal(parseInt(dot.getAttribute('data-slide'), 10));
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

    // initial position at real 1 without animation
    setTransform(false);
    updateDots();
    // restore transition for subsequent moves
    if (!prefersReduced) {
        void track.offsetHeight;
        track.style.transition = 'transform 0.45s ease';
    }
    start();
})();
</script>
@endpush
