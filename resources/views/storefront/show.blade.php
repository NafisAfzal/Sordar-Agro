@extends('layouts.app')
@section('title', $product->name.' — Sordar Agro')
@section('content')
@php
    $out = $product->isOutOfStock();
    // First in-stock variant is the default selection (fallback: first variant).
    $variants = $product->variants;
    $default = $variants->firstWhere('stock', '>', 0) ?? $variants->first();
@endphp

<nav aria-label="breadcrumb">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Shop</a></li>
        <li class="breadcrumb-item active">{{ $product->name }}</li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-md-5">
        @if ($product->thumbnail)
            <img src="{{ asset('storage/'.$product->thumbnail) }}" class="img-fluid rounded shadow-sm" alt="{{ $product->name }}">
        @else
            <div class="thumb-placeholder rounded" style="height:340px;"><i class="bi bi-water"></i></div>
        @endif
    </div>

    <div class="col-md-7">
        <span class="badge bg-light text-sa mb-2">{{ $product->category->name ?? '' }}</span>
        <h2>{{ $product->name }}</h2>

        @if ($product->is_fish)
            <p class="text-muted"><i class="bi bi-info-circle"></i> Fish are sold <strong>as a pair</strong> — each unit = 2 fish.</p>
        @endif

        <ul class="list-inline small text-muted">
            @if ($product->min_tank_size_litres)<li class="list-inline-item"><i class="bi bi-droplet"></i> Min tank: {{ $product->min_tank_size_litres }} L</li>@endif
            @if ($product->temperament)<li class="list-inline-item"><i class="bi bi-emoji-smile"></i> {{ ucfirst($product->temperament) }}</li>@endif
            @if ($product->seller)<li class="list-inline-item"><i class="bi bi-shop"></i> Sold by {{ $product->seller->name }}</li>@endif
        </ul>

        <p>{{ $product->description }}</p>

        @if ($out)
            <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Currently out of stock.</div>
            @auth
                <form method="POST" action="{{ route('wishlist.add', $product) }}">
                    @csrf
                    <button class="btn btn-outline-danger"><i class="bi bi-heart"></i> Add to Wishlist</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-danger">Log in to wishlist</a>
            @endauth
        @else
            {{-- Size selector --}}
            <h6 class="fw-bold">{{ $product->is_fish ? 'Choose a size' : 'Option' }}</h6>
            <div class="mb-2" id="sizePills">
                @foreach ($variants as $v)
                    <button type="button"
                            class="btn btn-outline-secondary size-pill m-1 {{ $v->id === $default->id ? 'active' : '' }} {{ $v->stock <= 0 ? 'disabled' : '' }}"
                            data-id="{{ $v->id }}"
                            data-price="{{ number_format($v->price, 2) }}"
                            data-stock="{{ $v->stock }}"
                            data-desc="{{ e($v->size_description) }}"
                            {{ $v->stock <= 0 ? 'disabled' : '' }}>
                        {{ $v->label }}
                    </button>
                @endforeach
            </div>

            <p class="text-muted small" id="sizeDesc">{{ $default->size_description }}</p>

            <div class="d-flex align-items-center gap-3 mb-3">
                <h3 class="text-sa mb-0">৳<span id="priceLabel">{{ number_format($default->price, 2) }}</span></h3>
                <span class="badge bg-success" id="stockLabel">{{ $default->stock }} {{ $product->is_fish ? 'pairs' : 'units' }} in stock</span>
            </div>

            @auth
                @if (auth()->user()->canShop())
                    <div class="d-flex gap-2 align-items-end flex-wrap">
                        <form method="POST" action="{{ route('cart.add', $default) }}" id="addForm" class="d-flex gap-2 align-items-end">
                            @csrf
                            <div>
                                <label class="form-label small">Quantity</label>
                                <div class="input-group input-group-sm qty-stepper" style="width:130px;">
                                    <button type="button" class="btn btn-outline-secondary" id="qtyMinus">−</button>
                                    <input type="number" name="quantity" value="1" min="1" max="{{ $default->stock }}"
                                           class="form-control text-center" id="qtyInput" readonly>
                                    <button type="button" class="btn btn-outline-secondary" id="qtyPlus">+</button>
                                </div>
                            </div>
                            <button class="btn btn-sa"><i class="bi bi-cart-plus"></i> Add to cart</button>
                        </form>
                        <form method="POST" action="{{ route('wishlist.add', $product) }}">
                            @csrf
                            <button class="btn btn-outline-danger" title="Save to wishlist"><i class="bi bi-heart"></i></button>
                        </form>
                    </div>
                @else
                    <p class="text-muted">Administrators shop using a customer/seller account.</p>
                @endif
            @else
                <a href="{{ route('login') }}" class="btn btn-sa">Log in to buy</a>
            @endauth
        @endif

        @if ($product->status === 'rejected' && $product->admin_feedback)
            <div class="alert alert-secondary mt-3 small">Admin note: {{ $product->admin_feedback }}</div>
        @endif
    </div>
</div>

@if ($related->isNotEmpty())
    <hr class="my-5">
    <h5 class="mb-3">Related products</h5>
    <div class="row g-3">
        @foreach ($related as $product)
            <div class="col-6 col-md-3">@include('partials.product-card')</div>
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
<script>
    // Switch price / stock / description and rewrite the add-to-cart action
    // to the selected variant id when a size pill is clicked.
    (function () {
        const pills = document.querySelectorAll('#sizePills .size-pill');
        const priceLabel = document.getElementById('priceLabel');
        const stockLabel = document.getElementById('stockLabel');
        const sizeDesc   = document.getElementById('sizeDesc');
        const addForm    = document.getElementById('addForm');
        const qtyInput   = document.getElementById('qtyInput');
        const unit = {{ $product->is_fish ? "'pairs'" : "'units'" }};
        const baseAction = "{{ url('/cart') }}/";

        pills.forEach(pill => {
            if (pill.disabled) return;
            pill.addEventListener('click', () => {
                pills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                if (priceLabel) priceLabel.textContent = pill.dataset.price;
                if (stockLabel) stockLabel.textContent = pill.dataset.stock + ' ' + unit + ' in stock';
                if (sizeDesc)   sizeDesc.textContent = pill.dataset.desc || '';
                if (qtyInput)   { qtyInput.max = pill.dataset.stock; if (+qtyInput.value > +pill.dataset.stock) qtyInput.value = pill.dataset.stock; }
                if (addForm)    addForm.setAttribute('action', baseAction + pill.dataset.id);
            });
        });

        // +/- stepper buttons for quantity.
        const qtyMinus = document.getElementById('qtyMinus');
        const qtyPlus  = document.getElementById('qtyPlus');
        if (qtyMinus && qtyInput) {
            qtyMinus.addEventListener('click', () => {
                let val = parseInt(qtyInput.value, 10);
                if (val > 1) qtyInput.value = val - 1;
            });
        }
        if (qtyPlus && qtyInput) {
            qtyPlus.addEventListener('click', () => {
                let val = parseInt(qtyInput.value, 10);
                const max = parseInt(qtyInput.max, 10);
                if (val < max) qtyInput.value = val + 1;
            });
        }
    })();
</script>
@endpush