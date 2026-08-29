@extends('layouts.app')
@php
    $metaCategory = request('category')
        ? ($categories->firstWhere('slug', request('category'))?->name ?? ucfirst(str_replace('-', ' ', request('category'))))
        : null;
@endphp
@section('title', ($metaCategory ? $metaCategory.' — ' : '').'Shop — Sordar Agro')
@section('meta_description', $metaCategory
    ? 'Browse '.$metaCategory.' available at Sordar Agro'.(request('q') ? ' matching “'.request('q').'”' : '').', with prices, stock availability and tank-suitability details.'
    : 'Browse all aquarium fish, aquatic plants, fish food and equipment available at Sordar Agro, with filters for tank size, temperament and price.')
{{-- Filters/sort/pagination are variations of the clean catalogue URL;
     consolidate them onto the canonical /products listing. --}}
@section('canonical_url', route('products.index'))
@section('content')
@php
    $activeCategory = request('category');
    $activeCategoryName = $activeCategory
        ? ($categories->firstWhere('slug', $activeCategory)?->name ?? ucfirst(str_replace('-', ' ', $activeCategory)))
        : null;
    $activeFilters = collect([
        'category'    => request('category'),
        'temperament' => request('temperament'),
        'tank_size'   => request('tank_size'),
        'min_price'   => request('min_price'),
        'max_price'   => request('max_price'),
        'availability'=> request('availability'),
    ])->filter();
    $resultCount = $products->total();
@endphp

{{-- Catalogue Header --}}
<div class="catalogue-header mb-4">
    <h1 class="catalogue-title mb-1">
        @if ($activeCategoryName)
            {{ $activeCategoryName }}
        @else
            Shop All Products
        @endif
    </h2>
    <p class="catalogue-subtitle text-muted mb-0">
        {{ $resultCount }} {{ Str::plural('product', $resultCount) }} available
        @if (request('q'))
            for "<strong>{{ request('q') }}</strong>"
        @endif
    </p>
</div>

{{-- Active Filter Chips --}}
@if ($activeFilters->isNotEmpty())
    <div class="catalogue-chips mb-3 d-flex flex-wrap gap-2 align-items-center">
        @foreach ($activeFilters as $key => $value)
            @php
                $label = match($key) {
                    'category' => $categories->firstWhere('slug', $value)?->name ?? ucfirst(str_replace('-', ' ', $value)),
                    'temperament' => ucfirst(str_replace('-', ' ', $value)),
                    'availability' => 'In stock only',
                    default => ucfirst(str_replace('_', ' ', $key)) . ': ' . $value,
                };
            @endphp
            <a href="{{ route('products.index', request()->except($key)) }}"
               class="filter-chip d-inline-flex align-items-center gap-1">
                {{ $label }}
                <i class="bi bi-x"></i>
            </a>
        @endforeach
        <a href="{{ route('products.index') }}" class="filter-chip-clear ms-1">Clear all</a>
    </div>
@endif

<div class="row">
    {{-- Mobile filter/sort controls --}}
    <div class="catalogue-mobile-controls">
        <button class="btn btn-sa-outline" type="button" id="mobileFilterTrigger" aria-expanded="false" aria-controls="mobileFilterDrawer">
            <i class="bi bi-funnel me-1"></i> Filters
            @if ($activeFilters->isNotEmpty())
                <span class="badge bg-white text-sa ms-1">{{ $activeFilters->count() }}</span>
            @endif
        </button>
        <form method="GET" class="flex-grow-1">
            @foreach (request()->except('sort') as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <select name="sort" class="form-select form-select-sm w-100 h-100" aria-label="Sort products"
                    onchange="this.form.submit()" style="min-height:44px;">
                <option value="newest" @selected(request('sort') === 'newest')>Sort: Newest</option>
                <option value="name" @selected(request('sort') === 'name')>Sort: Name A–Z</option>
            </select>
        </form>
    </div>

    {{-- Desktop Filter Sidebar --}}
    <div class="catalogue-desktop-filters">
        <div class="col-lg-3 mb-4">
            <div class="filter-sidebar">
                <div class="filter-sidebar-header d-flex align-items-center justify-content-between mb-3">
                    <h6 class="filter-sidebar-title mb-0"><i class="bi bi-funnel me-1"></i> Filters</h6>
                    @if ($activeFilters->isNotEmpty())
                        <a href="{{ route('products.index') }}" class="filter-clear-all small">Clear all</a>
                    @endif
                </div>
                <form method="GET" action="{{ route('products.index') }}" class="filter-form">
                    <input type="hidden" name="q" value="{{ request('q') }}">

                    {{-- Category --}}
                    <div class="filter-section">
                        <button class="filter-section-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#filterCategory"
                                aria-expanded="true" aria-controls="filterCategory">
                            Category <i class="bi bi-chevron-down filter-chevron"></i>
                        </button>
                        <div class="collapse show" id="filterCategory">
                            <div class="filter-section-body">
                                <select name="category" class="form-select form-select-sm" aria-label="Filter by category">
                                    <option value="">All categories</option>
                                    @foreach ($categories as $c)
                                        <option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Price range --}}
                    <div class="filter-section">
                        <button class="filter-section-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#filterPrice"
                                aria-expanded="true" aria-controls="filterPrice">
                            Price Range <i class="bi bi-chevron-down filter-chevron"></i>
                        </button>
                        <div class="collapse show" id="filterPrice">
                            <div class="filter-section-body">
                                <div class="d-flex gap-2">
                                    <input type="number" name="min_price" value="{{ request('min_price') }}"
                                           class="form-control form-control-sm" placeholder="Min" min="0" aria-label="Minimum price">
                                    <input type="number" name="max_price" value="{{ request('max_price') }}"
                                           class="form-control form-control-sm" placeholder="Max" min="0" aria-label="Maximum price">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tank size --}}
                    <div class="filter-section">
                        <button class="filter-section-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#filterTank"
                                aria-expanded="true" aria-controls="filterTank">
                            Min. Tank Size <i class="bi bi-chevron-down filter-chevron"></i>
                        </button>
                        <div class="collapse show" id="filterTank">
                            <div class="filter-section-body">
                                <input type="number" name="tank_size" value="{{ request('tank_size') }}"
                                       class="form-control form-control-sm" placeholder="e.g. 60" min="0" aria-label="Minimum tank size in litres">
                                <small class="filter-hint d-block mt-1">Shows fish suited to this tank or smaller.</small>
                            </div>
                        </div>
                    </div>

                    {{-- Temperament --}}
                    <div class="filter-section">
                        <button class="filter-section-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#filterTemperament"
                                aria-expanded="true" aria-controls="filterTemperament">
                            Temperament <i class="bi bi-chevron-down filter-chevron"></i>
                        </button>
                        <div class="collapse show" id="filterTemperament">
                            <div class="filter-section-body">
                                <select name="temperament" class="form-select form-select-sm" aria-label="Filter by temperament">
                                    <option value="">Any</option>
                                    @foreach (['peaceful', 'semi-aggressive', 'aggressive'] as $t)
                                        <option value="{{ $t }}" @selected(request('temperament') === $t)>{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Availability --}}
                    <div class="filter-section">
                        <button class="filter-section-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#filterAvailability"
                                aria-expanded="true" aria-controls="filterAvailability">
                            Availability <i class="bi bi-chevron-down filter-chevron"></i>
                        </button>
                        <div class="collapse show" id="filterAvailability">
                            <div class="filter-section-body">
                                <div class="form-check">
                                    <input type="checkbox" name="availability" value="in_stock"
                                           class="form-check-input" id="inStock"
                                           @checked(request('availability') === 'in_stock')>
                                    <label class="form-check-label small" for="inStock">In stock only</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-sa btn-sm w-100 mt-1" type="submit">Apply filters</button>
                    <a href="{{ route('products.index') }}" class="btn btn-link btn-sm w-100 mt-1">Clear all</a>
                </form>
            </div>
        </div>
    </div>

    {{-- Mobile Filter Drawer --}}
    <div class="mobile-filter-drawer" id="mobileFilterDrawer" inert>
        <div class="mobile-filter-drawer-backdrop" id="mobileFilterBackdrop"></div>
        <div class="mobile-filter-drawer-panel" role="dialog" aria-modal="true" aria-label="Product filters">
            <div class="mobile-filter-drawer-header">
                <h6><i class="bi bi-funnel me-1"></i> Filters</h6>
                <button class="mobile-filter-drawer-close" id="mobileFilterClose" aria-label="Close filters">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form method="GET" action="{{ route('products.index') }}" class="filter-form" id="mobileFilterForm">
                <input type="hidden" name="q" value="{{ request('q') }}">

                <div class="filter-section">
                    <label class="filter-sidebar-title mb-2" for="mobileCategory">Category</label>
                    <select name="category" id="mobileCategory" class="form-select form-select-sm">
                        <option value="">All categories</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-section">
                    <label class="filter-sidebar-title mb-2" for="mobileMinPrice">Price Range</label>
                    <div class="d-flex gap-2">
                        <input type="number" name="min_price" id="mobileMinPrice" value="{{ request('min_price') }}"
                               class="form-control form-control-sm" placeholder="Min" min="0" aria-label="Minimum price">
                        <input type="number" name="max_price" value="{{ request('max_price') }}"
                               class="form-control form-control-sm" placeholder="Max" min="0" aria-label="Maximum price">
                    </div>
                </div>

                <div class="filter-section">
                    <label class="filter-sidebar-title mb-2" for="mobileTankSize">Min. Tank Size</label>
                    <input type="number" name="tank_size" id="mobileTankSize" value="{{ request('tank_size') }}"
                           class="form-control form-control-sm" placeholder="e.g. 60" min="0">
                </div>

                <div class="filter-section">
                    <label class="filter-sidebar-title mb-2" for="mobileTemperament">Temperament</label>
                    <select name="temperament" id="mobileTemperament" class="form-select form-select-sm">
                        <option value="">Any</option>
                        @foreach (['peaceful', 'semi-aggressive', 'aggressive'] as $t)
                            <option value="{{ $t }}" @selected(request('temperament') === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-section">
                    <label class="filter-sidebar-title mb-2">Availability</label>
                    <div class="form-check">
                        <input type="checkbox" name="availability" value="in_stock"
                               class="form-check-input" id="mobileInStock"
                               @checked(request('availability') === 'in_stock')>
                        <label class="form-check-label small" for="mobileInStock">In stock only</label>
                    </div>
                </div>

                <div class="mobile-filter-apply-btn">
                    <button class="btn btn-sa w-100" type="submit">Apply filters</button>
                    @if ($activeFilters->isNotEmpty())
                        <a href="{{ route('products.index') }}" class="btn btn-link w-100 mt-1 small">Clear all</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Product Grid --}}
    <div class="col-lg-9">
        {{-- Toolbar: sort + count --}}
        <div class="catalogue-toolbar d-flex justify-content-between align-items-center mb-3">
            <span class="catalogue-result-count small text-muted d-none d-md-block">
                {{ $resultCount }} {{ Str::plural('product', $resultCount) }}
            </span>
            <form method="GET" class="catalogue-sort ms-auto">
                @foreach (request()->except('sort') as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <label for="catalogueSort" class="small text-muted me-1 d-none d-md-inline">Sort by</label>
                <select name="sort" id="catalogueSort" class="form-select form-select-sm d-inline-block w-auto"
                        aria-label="Sort by" onchange="this.form.submit()">
                    <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
                    <option value="name" @selected(request('sort') === 'name')>Name A–Z</option>
                </select>
            </form>
        </div>

        {{-- Product cards --}}
        <div class="row g-3">
            @forelse ($products as $product)
                <div class="col-6 col-md-4">@include('partials.product-card')</div>
            @empty
                <div class="col-12">
                    <div class="catalogue-empty text-center py-5">
                        <div class="catalogue-empty-icon mb-3">
                            <i class="bi bi-search"></i>
                        </div>
                        <h5 class="mb-2">No products found</h5>
                        <p class="text-muted mb-3">No products match your current filters. Try adjusting your search or browse all products.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-sa">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Clear all filters
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($products->hasPages())
            <div class="catalogue-pagination mt-4 d-flex justify-content-center">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const trigger = document.getElementById('mobileFilterTrigger');
    const drawer = document.getElementById('mobileFilterDrawer');
    const backdrop = document.getElementById('mobileFilterBackdrop');
    const closeBtn = document.getElementById('mobileFilterClose');

    if (!trigger || !drawer) return;

    function openDrawer() {
        drawer.inert = false;
        drawer.classList.add('open');
        document.body.style.overflow = 'hidden';
        trigger.setAttribute('aria-expanded', 'true');
        if (closeBtn) closeBtn.focus();
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        document.body.style.overflow = '';
        trigger.setAttribute('aria-expanded', 'false');
        drawer.inert = true;
        trigger.focus();
    }

    trigger.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer.classList.contains('open')) {
            closeDrawer();
        }
    });
})();
</script>
@endpush
