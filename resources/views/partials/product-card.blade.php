{{-- Reusable storefront product card. Expects $product with variants loaded. --}}
@php $out = $product->isOutOfStock(); @endphp
<div class="card product-card h-100">
    <a href="{{ route('products.show', $product) }}">
        @if ($product->thumbnail)
            <img src="{{ asset('storage/'.$product->thumbnail) }}" class="card-img-top product-thumb" alt="{{ $product->name }}" loading="{{ $loading ?? 'lazy' }}">
        @else
            <div class="thumb-placeholder"><i class="bi {{ $product->category->icon_class ?? 'bi-water' }}"></i></div>
        @endif
    </a>
    <div class="card-body d-flex flex-column">
        <span class="badge bg-light text-sa align-self-start mb-1">{{ $product->category->name ?? 'Product' }}</span>
        <h6 class="card-title mb-1">
            <a href="{{ route('products.show', $product) }}" class="text-decoration-none text-dark">{{ $product->name }}</a>
        </h6>
        @if ($product->is_fish)
            <small class="text-muted mb-2"><i class="bi bi-info-circle"></i> Sold as a pair (2 fish)</small>
        @endif
        <div class="mt-auto">
            <div class="fw-bold text-sa mb-2">From ৳{{ number_format($product->starting_price, 2) }}</div>
            @auth
                @if ($out)
                    <form method="POST" action="{{ route('wishlist.add', $product) }}">
                        @csrf
                        <button class="btn btn-outline-danger btn-sm w-100">
                            <i class="bi bi-heart"></i> Add to Wishlist
                        </button>
                    </form>
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
