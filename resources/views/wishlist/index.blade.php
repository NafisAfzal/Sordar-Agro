@extends('layouts.app')
@section('title', 'Your Wishlist')
@section('content')
    <h3 class="mb-4"><i class="bi bi-heart"></i> Your wishlist</h3>

    @if ($items->isEmpty())
        <div class="text-center py-5">
    <i class="bi bi-heart text-muted" style="font-size:3rem;"></i>
    <h5 class="mt-3">Your wishlist is empty</h5>
    <p class="text-muted">Save products you love and we'll tell you when they're back in stock.</p>
    <a href="{{ route('products.index') }}" class="btn btn-sa">Browse products</a>
</div>
    @else
        <div class="row g-3">
            @foreach ($items as $item)
                @php $product = $item->product; $out = $product->isOutOfStock(); @endphp
                <div class="col-6 col-md-3">
                    <div class="card product-card h-100">
                        <a href="{{ route('products.show', $product) }}">
                            @if ($product->thumbnail)
                                <img src="{{ asset('storage/'.$product->thumbnail) }}" class="card-img-top product-thumb" alt="">
                            @else
                                <div class="thumb-placeholder"><i class="bi bi-water"></i></div>
                            @endif
                        </a>
                        <div class="card-body d-flex flex-column">
                            <h6><a href="{{ route('products.show', $product) }}" class="text-decoration-none text-dark">{{ $product->name }}</a></h6>
                            <div class="mb-2">
                                @if ($out)
                                    <span class="badge bg-danger">Out of stock</span>
                                @else
                                    <span class="badge bg-success">Available now</span>
                                @endif
                            </div>
                            <div class="mt-auto d-flex gap-1">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-sa btn-sm flex-grow-1">View</a>
                                <form method="POST" action="{{ route('wishlist.remove', $item) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
