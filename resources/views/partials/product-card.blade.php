{{-- Reusable storefront product card. Expects $product with variants + category loaded. --}}
@php
    $out = $product->isOutOfStock();
    $isNew = $product->created_at && $product->created_at->diffInDays(now()) <= 7;
    $totalStock = $product->total_stock;
    $isLowStock = ! $out && $totalStock > 0 && $totalStock <= 2;
    $isBestSeller = $product->is_featured;
    $variantLabels = $product->variants->pluck('label')->filter()->unique()->values();
@endphp
<div class="card product-card h-100">
    <div class="position-relative">
        <a href="{{ route('products.show', $product) }}" aria-label="{{ $product->name }}" class="product-thumb-wrap">
            @if ($product->thumbnail)
                <img src="{{ asset('storage/'.$product->thumbnail) }}" class="product-thumb" alt="{{ $product->name }}" loading="{{ $loading ?? 'lazy' }}" decoding="async">
            @else
                <div class="thumb-placeholder"><i class="bi {{ $product->category->icon_class ?? 'bi-water' }}"></i></div>
            @endif
        </a>

        @if ($isNew || $isLowStock || $isBestSeller)
            <div class="product-badges">
                @if ($isBestSeller)
                    <span class="badge badge-best-seller">Featured</span>
                @endif
                @if ($isNew)
                    <span class="badge badge-new">New</span>
                @endif
                @if ($isLowStock)
                    <span class="badge badge-low-stock">Low Stock</span>
                @endif
            </div>
        @endif

        @auth
            <form method="POST" action="{{ route('wishlist.add', $product) }}" class="product-wishlist-btn">
                @csrf
                <button type="submit" class="btn btn-wishlist-icon" title="Save to wishlist" aria-label="Save {{ $product->name }} to wishlist">
                    <i class="bi bi-heart"></i>
                </button>
            </form>
        @endauth
    </div>

    <div class="card-body d-flex flex-column">
        <span class="badge bg-light text-sa align-self-start mb-1">{{ $product->category->name ?? 'Product' }}</span>
        <h6 class="card-title mb-1">
            <a href="{{ route('products.show', $product) }}" class="text-decoration-none text-dark">{{ $product->name }}</a>
        </h6>
        @if ($variantLabels->isNotEmpty() && ($variantLabels->count() > 1 || $variantLabels->first() !== 'Standard'))
            <small class="text-muted mb-1">{{ $variantLabels->implode(' · ') }}</small>
        @endif
        @if ($product->is_fish)
            <small class="text-muted mb-2"><i class="bi bi-info-circle"></i> Sold as a pair (2 fish)</small>
        @endif
        <div class="mt-auto">
            <div class="fw-bold text-sa mb-2">From ৳{{ number_format($product->starting_price, 2) }}</div>
            @auth
                @if ($out)
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled>
                        <i class="bi bi-heart"></i> Out of Stock
                    </button>
                @else
                    <a href="{{ route('products.show', $product) }}" class="btn btn-sa btn-sm w-100">
                        <i class="bi bi-cart-plus"></i> View &amp; Add
                    </a>
                @endif
            @else
                <a href="{{ route('products.show', $product) }}" class="btn btn-sa btn-sm w-100">View product</a>
            @endauth
        </div>
    </div>
</div>
