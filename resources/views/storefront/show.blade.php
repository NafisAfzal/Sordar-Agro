@extends('layouts.app')
@section('title', $product->name.' — Sordar Agro')
@section('meta_description', $product->description
    ? Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($product->description))), 155)
    : 'View '.$product->name.' at Sordar Agro'.($product->category ? ' in '.$product->category->name : '').'.')
@section('canonical_url', route('products.show', $product))
@section('og_type', 'product')
@if ($product->thumbnail)
    @section('og_image', asset('storage/'.$product->thumbnail))
@endif
@php
    // Structured data uses ONLY real, already-public data.
    // No brand exists in the system, so none is emitted.
    // No review/rating system exists, so none is emitted.
    $ldImages = $product->images
        ->map(fn ($img) => asset('storage/'.$img->path))
        ->whenEmpty(fn ($c) => $product->thumbnail ? collect([asset('storage/'.$product->thumbnail)]) : collect());

    $productLd = collect([
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $product->name,
        'description' => $product->description
            ? trim(preg_replace('/\s+/', ' ', strip_tags($product->description)))
            : null,
        'image'       => $ldImages->isEmpty() ? null : $ldImages->all(),
        'category'    => $product->category?->name,
        'url'         => route('products.show', $product),
        // One truthful Offer per real variant (prices genuinely differ by size).
        'offers'      => $product->variants->map(function ($v) use ($product) {
            return collect([
                '@type'        => 'Offer',
                'sku'          => $v->sku ?: null,
                'name'         => $v->label,
                'price'        => number_format((float) $v->price, 2, '.', ''),
                'priceCurrency' => 'BDT',
                'availability' => $v->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url'          => route('products.show', $product),
            ])->filter(fn ($value) => $value !== null)->all();
        })->values()->all(),
    ])->filter(fn ($value) => $value !== null)->all();

    $crumbs = collect([
        ['Home', route('home')],
        ['Shop', route('products.index')],
    ]);
    if ($product->category) {
        $crumbs->push([$product->category->name, route('products.index', ['category' => $product->category->slug])]);
    }
    $crumbs->push([$product->name, route('products.show', $product)]);

    $breadcrumbLd = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $crumbs->values()->map(fn ($c, $i) => [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $c[0],
            'item'     => $c[1],
        ])->all(),
    ];
@endphp
@section('content')
@php
    $out = $product->isOutOfStock();
    $variants = $product->variants;
    $default = $variants->firstWhere('stock', '>', 0) ?? $variants->first();
    $galleryImages = $product->images->sortBy('sort_order');
    $hasGallery = $galleryImages->isNotEmpty();
@endphp

<div class="product-detail">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Shop</a></li>
            @if ($product->category)
                <li class="breadcrumb-item"><a href="{{ route('products.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a></li>
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    <script type="application/ld+json">{!! json_encode($productLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    {{-- Main two-column layout --}}
    <div class="row g-4 g-lg-5">

        {{-- ======================== --}}
        {{-- LEFT: Gallery            --}}
        {{-- ======================== --}}
        <div class="col-lg-6">
            @if ($hasGallery)
                @php $first = $galleryImages->first(); @endphp
                <img id="galleryMain"
                     src="{{ asset('storage/'.$first->path) }}"
                     alt="{{ $product->name }}"
                     class="product-gallery-main"
                     loading="eager"
                     decoding="async">
                @if ($galleryImages->count() > 1)
                    <div class="product-gallery-thumbs" role="listbox" aria-label="Product images">
                        @foreach ($galleryImages as $idx => $img)
                            <img src="{{ asset('storage/'.$img->path) }}"
                                 alt="{{ $product->name }} image {{ $idx + 1 }}"
                                 class="product-gallery-thumb {{ $idx === 0 ? 'active' : '' }}"
                                 data-full="{{ asset('storage/'.$img->path) }}"
                                 role="option"
                                 aria-selected="{{ $idx === 0 ? 'true' : 'false' }}"
                                 tabindex="0"
                                 loading="lazy"
                                 decoding="async">
                        @endforeach
                    </div>
                @endif
            @elseif ($product->thumbnail)
                <img src="{{ asset('storage/'.$product->thumbnail) }}"
                     alt="{{ $product->name }}"
                     class="product-gallery-main"
                     loading="eager"
                     decoding="async">
            @else
                <div class="product-gallery-placeholder" aria-label="{{ $product->name }}">
                    <i class="bi {{ $product->category->icon_class ?? 'bi-water' }}"></i>
                </div>
            @endif
        </div>

        {{-- ======================== --}}
        {{-- RIGHT: Summary + Purchase --}}
        {{-- ======================== --}}
        <div class="col-lg-6">
            <div class="product-summary">
                {{-- Category --}}
                @if ($product->category)
                    <span class="category-badge">{{ $product->category->name }}</span>
                @endif

                {{-- Name --}}
                <h1 class="product-name">{{ $product->name }}</h1>

                {{-- Meta: tank size, temperament, seller --}}
                <div class="product-meta">
                    @if ($product->min_tank_size_litres)
                        <span class="product-meta-item">
                            <i class="bi bi-droplet"></i>
                            Min {{ $product->min_tank_size_litres }} L tank
                        </span>
                    @endif
                    @if ($product->temperament)
                        <span class="product-meta-item">
                            <i class="bi bi-emoji-smile"></i>
                            {{ ucfirst($product->temperament) }}
                        </span>
                    @endif
                    @if ($product->seller)
                        <span class="product-meta-item">
                            <i class="bi bi-shop"></i>
                            {{ $product->seller->name }}
                        </span>
                    @endif
                </div>

                {{-- Fish pair note --}}
                @if ($product->is_fish)
                    <p class="fish-pair-note">
                        <i class="bi bi-info-circle"></i>
                        Fish are sold <strong>as a pair</strong> — each unit = 2 fish.
                    </p>
                @endif
            </div>

            {{-- Purchase panel --}}
            <div class="product-purchase">
                @if ($out)
                    {{-- OUT OF STOCK --}}
                    <div class="stock-badge out-of-stock mb-3">
                        <i class="bi bi-exclamation-circle"></i> Out of stock
                    </div>
                    <div class="product-oos-cta">
                        @auth
                            <form method="POST" action="{{ route('wishlist.add', $product) }}">
                                @csrf
                                <button class="btn btn-sa-outline w-100">
                                    <i class="bi bi-heart"></i> Notify me / Add to Wishlist
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-sa-outline w-100">Log in to wishlist</a>
                        @endauth
                    </div>
                @else
                    {{-- IN STOCK --}}
                    {{-- Size / Variant selector --}}
                    @if ($variants->count() > 1)
                        <div class="variant-label">{{ $product->is_fish ? 'Choose a size' : 'Option' }}</div>
                        <div class="mb-2" id="sizePills" data-is-fish="{{ $product->is_fish ? '1' : '0' }}">
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
                        <p class="size-description" id="sizeDesc">{{ $default->size_description }}</p>
                    @endif

                    {{-- Price + Stock --}}
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="price-display">৳<span id="priceLabel">{{ number_format($default->price, 2) }}</span></div>
                        <span class="stock-badge in-stock" id="stockLabel">{{ $default->stock }} {{ $product->is_fish ? 'pairs' : 'units' }} in stock</span>
                    </div>

                    {{-- Add to Cart + Wishlist --}}
                    @auth
                        @if (auth()->user()->canShop())
                            <div class="action-row">
                                <form method="POST" action="{{ route('cart.add', $default) }}" id="addForm" class="d-flex gap-2 align-items-end flex-grow-1">
                                    @csrf
                                    <div class="flex-grow-1">
                                        <label class="form-label small" for="qtyInput">Quantity</label>
                                        <div class="input-group input-group-sm qty-stepper">
                                            <button type="button" class="btn btn-outline-secondary" id="qtyMinus" aria-label="Decrease quantity">&#x2212;</button>
                                            <input type="number" name="quantity" value="1" min="1" max="{{ $default->stock }}"
                                                   class="form-control text-center" id="qtyInput" readonly aria-label="Quantity">
                                            <button type="button" class="btn btn-outline-secondary" id="qtyPlus" aria-label="Increase quantity">+</button>
                                        </div>
                                    </div>
                                    <button class="btn btn-sa">
                                        <i class="bi bi-cart-plus"></i> Add to cart
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('wishlist.add', $product) }}">
                                    @csrf
                                    <button class="wishlist-btn" title="Save to wishlist" aria-label="Save {{ $product->name }} to wishlist">
                                        <i class="bi bi-heart"></i>
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="text-muted small">Administrators shop using a customer/seller account.</p>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sa w-100">Log in to buy</a>
                    @endauth
                @endif

                {{-- Admin feedback --}}
                @if ($product->status === 'rejected' && $product->admin_feedback)
                    <div class="product-admin-note">
                        <i class="bi bi-info-circle"></i> Admin note: {{ $product->admin_feedback }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ======================== --}}
    {{-- Product Info Tabs        --}}
    {{-- ======================== --}}
    <div class="product-info">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-overview" data-bs-toggle="tab" data-bs-target="#panel-overview" type="button" role="tab" aria-controls="panel-overview" aria-selected="true">Overview</button>
            </li>
            @if ($product->is_fish)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-care" data-bs-toggle="tab" data-bs-target="#panel-care" type="button" role="tab" aria-controls="panel-care" aria-selected="false">Care</button>
                </li>
            @endif
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-shipping" data-bs-toggle="tab" data-bs-target="#panel-shipping" type="button" role="tab" aria-controls="panel-shipping" aria-selected="false">Shipping</button>
            </li>
        </ul>

        <div class="tab-content">
            {{-- Overview --}}
            <div class="tab-pane fade show active" id="panel-overview" role="tabpanel" aria-labelledby="tab-overview">
                @if ($product->description)
                    {!! nl2br(e($product->description)) !!}
                @else
                    <p class="text-muted">No description available for this product.</p>
                @endif
            </div>

            {{-- Care (fish only) --}}
            @if ($product->is_fish)
                <div class="tab-pane fade" id="panel-care" role="tabpanel" aria-labelledby="tab-care">
                    @if ($product->min_tank_size_litres)
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-droplet"></i> Minimum Tank Size</span>
                            <span class="info-value">{{ $product->min_tank_size_litres }} litres</span>
                        </div>
                    @endif
                    @if ($product->temperament)
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-emoji-smile"></i> Temperament</span>
                            <span class="info-value">{{ ucfirst($product->temperament) }}</span>
                        </div>
                    @endif
                    @if ($product->is_fish)
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-info-circle"></i> Sold As</span>
                            <span class="info-value">Pairs (2 fish per unit)</span>
                        </div>
                    @endif
                    @if ($product->variants->count() > 0)
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-rulers"></i> Available Sizes</span>
                            <span class="info-value">{{ $product->variants->pluck('label')->filter()->implode(', ') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Shipping --}}
            <div class="tab-pane fade" id="panel-shipping" role="tabpanel" aria-labelledby="tab-shipping">
                <div class="info-row">
                    <span class="info-label"><i class="bi bi-shield-check"></i> Payment</span>
                    <span class="info-value">Secure local payments via bKash / Nagad</span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="bi bi-truck"></i> Delivery</span>
                    <span class="info-value">Delivery via trusted courier partners</span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="bi bi-geo-alt"></i> Tracking</span>
                    <span class="info-value">Tracking available once your order ships</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================== --}}
    {{-- Trust Strip              --}}
    {{-- ======================== --}}
    <div class="product-trust">
        <div class="trust-grid">
            <div class="trust-item">
                <div class="trust-icon"><i class="bi bi-heart-pulse"></i></div>
                <div>
                    <div class="trust-title">Healthy Livestock</div>
                    <div class="trust-desc">Carefully selected aquarium fish and plants</div>
                </div>
            </div>
            <div class="trust-item">
                <div class="trust-icon"><i class="bi bi-shield-lock"></i></div>
                <div>
                    <div class="trust-title">Secure Payments</div>
                    <div class="trust-desc">bKash and Nagad — trusted local methods</div>
                </div>
            </div>
            <div class="trust-item">
                <div class="trust-icon"><i class="bi bi-truck"></i></div>
                <div>
                    <div class="trust-title">Order Tracking</div>
                    <div class="trust-desc">Courier tracking once your order ships</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================== --}}
    {{-- Related Products         --}}
    {{-- ======================== --}}
    @if ($related->isNotEmpty())
        <div class="product-related">
            <h5 class="section-title">Related products</h5>
            <div class="row g-3">
                @foreach ($related as $product)
                    <div class="col-6 col-md-3">@include('partials.product-card')</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Mobile sticky purchase bar --}}
    @if (!$out)
        <div class="product-mobile-purchase-bar" id="mobilePurchaseBar">
            <div>
                <div class="mobile-bar-price" id="mobilePriceLabel">৳{{ number_format($default->price, 2) }}</div>
                <div class="mobile-bar-stock" id="mobileStockLabel">{{ $default->stock }} {{ $product->is_fish ? 'pairs' : 'units' }}</div>
            </div>
            @auth
                @if (auth()->user()->canShop())
                    <button class="btn btn-sa" id="mobileAddToCart" type="button">
                        <i class="bi bi-cart-plus"></i> Add to cart
                    </button>
                @endif
            @endauth
        </div>
    @endif
</div>
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
        const mobilePrice = document.getElementById('mobilePriceLabel');
        const mobileStock = document.getElementById('mobileStockLabel');
        const isFish = document.getElementById('sizePills')?.dataset.isFish === '1';
        const unit = isFish ? 'pairs' : 'units';
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
                if (mobilePrice) mobilePrice.textContent = '৳' + pill.dataset.price;
                if (mobileStock) mobileStock.textContent = pill.dataset.stock + ' ' + unit;
            });
        });

        // Mobile sticky Add to Cart — triggers the main form submit
        const mobileAddBtn = document.getElementById('mobileAddToCart');
        if (mobileAddBtn && addForm) {
            mobileAddBtn.addEventListener('click', () => {
                addForm.requestSubmit();
            });
        }

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

    // Gallery thumbnail switching
    (function () {
        const main = document.getElementById('galleryMain');
        const thumbs = document.querySelectorAll('.product-gallery-thumb');
        if (!main || !thumbs.length) return;

        thumbs.forEach(thumb => {
            function activate() {
                thumbs.forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
                thumb.classList.add('active');
                thumb.setAttribute('aria-selected', 'true');
                main.src = thumb.dataset.full;
            }
            thumb.addEventListener('click', activate);
            thumb.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(); } });
        });
    })();
</script>
@endpush
