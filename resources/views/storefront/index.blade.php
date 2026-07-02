@extends('layouts.app')
@section('title', 'Shop — Sordar Agro')
@section('content')
    <div class="row">
        {{-- Filters sidebar --}}
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-funnel"></i> Filters</h6>
                    <form method="GET" action="{{ route('products.index') }}">
                        <input type="hidden" name="q" value="{{ request('q') }}">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Category</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="">All categories</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Price range (৳)</label>
                            <div class="d-flex gap-2">
                                <input type="number" name="min_price" value="{{ request('min_price') }}" class="form-control form-control-sm" placeholder="Min" min="0">
                                <input type="number" name="max_price" value="{{ request('max_price') }}" class="form-control form-control-sm" placeholder="Max" min="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Min. tank size (L)</label>
                            <input type="number" name="tank_size" value="{{ request('tank_size') }}" class="form-control form-control-sm" placeholder="e.g. 60" min="0">
                            <small class="text-muted">Shows fish suited to this tank or smaller.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Temperament</label>
                            <select name="temperament" class="form-select form-select-sm">
                                <option value="">Any</option>
                                @foreach (['peaceful', 'semi-aggressive', 'aggressive'] as $t)
                                    <option value="{{ $t }}" @selected(request('temperament') === $t)>{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="availability" value="in_stock" class="form-check-input" id="inStock" @checked(request('availability') === 'in_stock')>
                            <label class="form-check-label small" for="inStock">In stock only</label>
                        </div>

                        <button class="btn btn-sa btn-sm w-100">Apply filters</button>
                        <a href="{{ route('products.index') }}" class="btn btn-link btn-sm w-100">Clear</a>
                    </form>
                </div>
            </div>
        </div>

        {{-- Products grid --}}
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Shop</h4>
                    @if (request('q'))<small class="text-muted">Results for “{{ request('q') }}”</small>@endif
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
                        <div class="alert alert-info">No products match your filters.</div>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $products->links() }}</div>
        </div>
    </div>
@endsection
