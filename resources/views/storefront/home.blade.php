@extends('layouts.app')
@section('title', 'Sordar Agro — Aquarium Marketplace')
@section('meta_description', 'Shop healthy aquarium fish, aquatic plants, fish food and equipment from Sordar Agro, with care guides and tank-suitability information for hobbyists in Bangladesh.')
@section('canonical_url', url('/'))
@section('content')
    <section class="hero px-4 px-md-5 pb-5 mb-5">
        <div class="row vh-100 align-items-center justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <h1 class="fw-bold mb-3">Build a healthier aquarium.</h1>
                <p class="lead mb-4">Healthy fish, aquatic plants, food and equipment, selected for aquarium hobbyists in Bangladesh.</p>
                <div class="d-flex gap-3 mb-4">
                    <a href="{{ route('products.index', ['category' => 'fish']) }}" class="btn btn-sa fw-semibold">Shop Fish</a>
                    <a href="{{ route('products.index', ['category' => 'equipment']) }}" class="btn btn-sa-outline fw-semibold">Explore Equipment</a>
                </div>
            </div>
            <div class="col-12 col-md-4 text-center d-none d-md-block">
                <i class="bi bi-water" style="font-size:7rem;opacity:.85;"></i>
            </div>
        </div>
    </section>

    <!-- Section 2: Shop by Category -->
    <section class="py-5">
        <div class="container">
            <div class="row g-3 mb-5">
                @foreach ($categories as $category)
                    @if (in_array($category->slug, ['fish', 'aquatic-plants', 'fish-food', 'equipment']))
                        <div class="col-6 col-md-3">
                            <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                               class="card text-center text-decoration-none h-100">
                                <div class="card-body p-3">
                                    <div class="fs-2 text-sa mb-2"><i class="bi {{ $category->getIconClassAttribute() }}"></i></div>
                                    <h6 class="fw-semibold text-dark mb-1">{{ ucfirst($category->name) }}</h6>
                                    <small class="text-muted">{{ $category->products_count }} items</small>
                                </div>
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <!-- Section 3: Featured Products -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h4 class="fw-bold mb-2">Featured</h4>
                <a href="{{ route('products.index') }}" class="btn btn-sa-outline btn-sm">See all products</a>
            </div>
            <div class="row g-3">
                @if (count($featured) > 0)
                    @foreach ($featured as $product)
                        <div class="col-6 col-md-4 col-lg-3">
                            @include('partials.product-card', ['product' => $product, 'loading' => 'lazy'])
                        </div>
                    @endforeach
                @else
                    <p class="text-muted text-center py-4">No products yet. Run <code>php artisan migrate --seed</code>.</p>
                @endif
            </div>
        </div>
    </section>

    <!-- Section 4: Aquarium Finder -->
    <section class="py-5 bg-sa">
        <div class="container">
            <div class="row g-3 justify-content-center">
                <div class="col-10 col-md-8 col-lg-6">
                    <form action="{{ route('products.index') }}" method="GET" class="p-4 rounded border">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-uppercase" for="finder_tank">Tank size (litres)</label>
                                <input type="number" name="tank_size" id="finder_tank" class="form-control" placeholder="e.g. 60">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-uppercase" for="finder_budget">Budget</label>
                                <input type="text" name="min_price" id="finder_budget" class="form-control" placeholder="From ৳">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-uppercase" for="finder_temperament">Temperament</label>
                                <select name="temperament" id="finder_temperament" class="form-select form-select-sm">
                                    <option value="">Any</option>
                                    <option value="peaceful">Peaceful</option>
                                    <option value="semi-aggressive">Semi-aggressive</option>
                                    <option value="aggressive">Aggressive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sa w-100 mt-3">Show Suitable Fish</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 5: Aquarium Essentials -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-6 col-md-4">
                    <div class="p-4 rounded text-center border">
                        <div class="fs-2 text-sa mb-3">🍽️</div>
                        <h5 class="fw-bold mb-2">Food</h5>
                        <p class="text-muted small">Complete nutrition for your aquarium inhabitants</p>
                        <a href="{{ route('products.index', ['category' => 'fish-food']) }}" class="text-sa small fw-semibold">View products</a>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="p-4 rounded text-center border">
                        <div class="fs-2 text-sa mb-3">⚡</div>
                        <h5 class="fw-bold mb-2">Equipment</h5>
                        <p class="text-muted small">Filters, heaters, lighting and more</p>
                        <a href="{{ route('products.index', ['category' => 'equipment']) }}" class="text-sa small fw-semibold">View products</a>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="p-4 rounded text-center border">
                        <div class="fs-2 text-sa mb-3">🌿</div>
                        <h5 class="fw-bold mb-2">Plants</h5>
                        <p class="text-muted small">Live aquatic plants for healthy tanks</p>
                        <a href="{{ route('products.index', ['category' => 'aquatic-plants']) }}" class="text-sa small fw-semibold">View products</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 6: Trust -->
    <section class="py-5 bg-sa">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-md-6">
                    <h4 class="fw-bold mb-3">Trusted by hobbyists</h4>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><i class="bi bi-check2-all text-sa me-2"></i>Clear tank suitability information for every product</li>
                        <li class="mb-2"><i class="bi bi-check2-all text-sa me-2"></i>Secure local payment options via bKash/Nagad</li>
                        <li class="mb-2"><i class="bi bi-check2-all text-sa me-2"></i>Livestock care guides and compatibility information</li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 text-center">
                    <i class="bi bi-shield-check" style="font-size:4rem;opacity:.7;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 7: Care Guides -->
    @if (count($guides) > 0)
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h4 class="fw-bold mb-2">Latest care guides</h4>
            </div>
            <div class="row g-3">
                @foreach ($guides as $guide)
                    <div class="col-md-4">
                        <a href="{{ route('care.show', $guide->slug) }}" class="card product-card text-decoration-none h-100">
                            @if ($guide->image)
                                <img src="{{ asset('storage/'.$guide->image) }}" class="card-img-top product-thumb" alt="{{ $guide->title }}">
                            @else
                                <div class="thumb-placeholder"><i class="bi bi-journal-text"></i></div>
                            @endif
                            <div class="card-body">
                                <h6 class="text-dark">{{ $guide->title }}</h6>
                                <p class="text-muted small mb-0">{{ Str::limit($guide->excerpt, 80) }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Section 8: Community -->
    @if (isset($submissions) && count($submissions) > 0)
    <section class="py-5 bg-sa">
        <div class="container">
            <div class="text-center mb-4">
                <h4 class="fw-bold mb-2">Community</h4>
            </div>
            <div class="row g-3">
                @foreach ($submissions as $submission)
                    <div class="col-md-6">
                        <div class="card product-card h-100">
                            <div class="card-body">
                                <h6 class="text-dark small fw-bold mb-2">{{ Str::limit($submission->title, 50) }}</h6>
                                <p class="text-muted small mb-0">{{ Str::limit($submission->body, 100) }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Section 9: Final CTA -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="text-center py-5">
                <h2 class="fw-bold mb-3">Ready to build your aquarium?</h2>
                <a href="{{ route('products.index') }}" class="btn btn-sa fw-semibold px-5">Shop the collection</a>
            </div>
        </div>
    </section>
@endsection