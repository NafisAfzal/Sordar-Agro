@extends('layouts.app')
@section('title', 'Shop — Sordar Agro')
@section('content')
    <div class="row">
        {{-- Filters sidebar --}}
        <div class="d-lg-none mb-3">
            <button class="btn btn-outline-secondary w-100" type="button"
                    data-bs-toggle="collapse" data-bs-target="#mobileFilters">
                <i class="bi bi-funnel"></i> Filters
            </button>
        </div>

        <div class="collapse d-lg-block" id="mobileFilters">
            <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-funnel"></i> Filters</h6>
                    <form method="GET" action="{{ route('products.index') }}">
                        <input type="hidden" name="q" value="{{ request('q') }}">

                        {{-- Category --}}
                        <div class="mb-2">
                            <button class="btn btn-sm btn-link text-decoration-none p-0 fw-semibold text-dark"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#filterCategory">
                                Category <i class="bi bi-chevron-down small"></i>
                            </button>
                            <div class="collapse show" id="filterCategory">
                                <select name="category" class="form-select form-select-sm mt-2">
                                    <option value="">All categories</option>
                                    @foreach ($categories as $c)
                                        <option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Price range --}}
                        <div class="mb-2">
                            <button class="btn btn-sm btn-link text-decoration-none p-0 fw-semibold text-dark"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#filterPrice">
                                Price range <i class="bi bi-chevron-down small"></i>
                            </button>
                            <div class="collapse show" id="filterPrice">
                                <div class="d-flex gap-2 mt-2">
                                    <input type="number" name="min_price" value="{{ request('min_price') }}" class="form-control form-control-sm" placeholder="Min" min="0">
                                    <input type="number" name="max_price" value="{{ request('max_price') }}" class="form-control form-control-sm" placeholder="Max" min="0">
                                </div>
                            </div>
                        </div>

                        {{-- Tank size --}}
                        <div class="mb-2">
                            <button class="btn btn-sm btn-link text-decoration-none p-0 fw-semibold text-dark"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#filterTank">
                                Min. tank size <i class="bi bi-chevron-down small"></i>
                            </button>
                            <div class="collapse show" id="filterTank">
                                <input type="number" name="tank_size" value="{{ request('tank_size') }}" class="form-control form-control-sm mt-2" placeholder="e.g. 60" min="0">
                                <small class="text-muted d-block mt-1">Shows fish suited to this tank or smaller.</small>
                            </div>
                        </div>

                        {{-- Temperament --}}
                        <div class="mb-2">
                            <button class="btn btn-sm btn-link text-decoration-none p-0 fw-semibold text-dark"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#filterTemperament">
                                Temperament <i class="bi bi-chevron-down small"></i>
                            </button>
                            <div class="collapse show" id="filterTemperament">
                                <select name="temperament" class="form-select form-select-sm mt-2">
                                    <option value="">Any</option>
                                    @foreach (['peaceful', 'semi-aggressive', 'aggressive'] as $t)
                                        <option value="{{ $t }}" @selected(request('temperament') === $t)>{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Availability --}}
                        <div class="mb-2">
                            <button class="btn btn-sm btn-link text-decoration-none p-0 fw-semibold text-dark"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#filterAvailability">
                                Availability <i class="bi bi-chevron-down small"></i>
                            </button>
                            <div class="collapse show" id="filterAvailability">
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="availability" value="in_stock" class="form-check-input" id="inStock" @checked(request('availability') === 'in_stock')>
                                    <label class="form-check-label small" for="inStock">In stock only</label>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-sa btn-sm w-100 mt-2">Apply filters</button>
                        <a href="{{ route('products.index') }}" class="btn btn-link btn-sm w-100">Clear</a>
                    </form>
                </div>
            </div>
        </div>
        </div>

        {{-- Products grid --}}
        <div class="col-lg-9">
            {{-- Active filters chips --}}
            @php
                $activeFilters = collect([
                    'category' => request('category'),
                    'temperament' => request('temperament'),
                    'tank_size' => request('tank_size'),
                    'min_price' => request('min_price'),
                    'max_price' => request('max_price'),
                    'availability' => request('availability'),
                ])->filter();
            @endphp
            @if ($activeFilters->isNotEmpty())
                <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                    <span class="small text-muted">Active filters:</span>
                    @foreach ($activeFilters as $key => $value)
                        <span class="badge bg-light text-sa border d-flex align-items-center gap-1 px-2 py-1">
                            {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}
                            <a href="{{ route('products.index', request()->except($key)) }}"
                               class="text-danger text-decoration-none fw-bold ms-1" title="Remove">&times;</a>
                        </span>
                    @endforeach
                    <a href="{{ route('products.index') }}" class="small text-muted ms-2">Clear all</a>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Shop</h4>
                    @if (request('q'))<small class="text-muted">Results for "{{ request('q') }}"</small>@endif
                </div>
                <form method="GET">
                    @foreach (request()->except('sort') as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
                        <option value="name" @selected(request('sort') === 'name')>Name A–Z</option>
                    </select>
                </form>
            </div>

            <div class="row g-3">
                @forelse ($products as $product)
                    <div class="col-6 col-md-4">@include('partials.product-card')</div>
                @empty
                    <div class="col-12">
    <div class="text-center py-5">
        <i class="bi bi-search text-muted" style="font-size:3rem;"></i>
        <h5 class="mt-3">No products match your filters</h5>
        <p class="text-muted">Try removing a filter or searching for something different.</p>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Clear all filters</a>
    </div>
</div>
                @endforelse
            </div>

            <div class="mt-4">{{ $products->links() }}</div>
        </div>
    </div>
@endsection