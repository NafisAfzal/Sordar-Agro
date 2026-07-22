@extends('layouts.app')
@section('title', 'Your Cart')
@section('content')
    <h3 class="mb-4"><i class="bi bi-cart"></i> Your cart</h3>

    @if ($items->isEmpty())
        <div class="alert alert-info">Your cart is empty. <a href="{{ route('products.index') }}">Start shopping</a>.</div>
    @else
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Product</th><th>Price</th><th style="width:140px;">Qty</th><th>Subtotal</th><th></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>
                                            <a href="{{ route('products.show', $item->variant->product) }}" class="text-decoration-none fw-semibold text-dark">
                                                {{ $item->variant->product->name }}
                                            </a>
                                            <div class="small text-muted">
                                                {{ $item->variant->label }}
                                                @if ($item->variant->product->is_fish) · pair @endif
                                            </div>
                                        </td>
                                        <td>৳{{ number_format($item->variant->price, 2) }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('cart.update', $item) }}" class="d-flex">
                                                @csrf @method('PATCH')
                                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->variant->stock }}" class="form-control form-control-sm me-1">
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat"></i></button>
                                            </form>
                                        </td>
                                        <td class="fw-semibold">৳{{ number_format($item->subtotal(), 2) }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('cart.remove', $item) }}">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mt-3 mt-lg-0">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold">Order summary</h6>
                        <div class="d-flex justify-content-between"><span>Items total</span><span>৳{{ number_format($total, 2) }}</span></div>
                        <hr>
                        <a href="{{ route('checkout.show') }}" class="btn btn-sa w-100">Proceed to checkout</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
