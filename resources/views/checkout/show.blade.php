@extends('layouts.app')
@section('title', 'Checkout')
@section('content')
    <h3 class="mb-4"><i class="bi bi-bag-check"></i> Checkout</h3>
    <form method="POST" action="{{ route('checkout.place') }}">
        @csrf
        <div class="row">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Shipping details</h6>
                        <div class="mb-3">
                            <label class="form-label">Full name</label>
                            <input type="text" name="shipping_name" value="{{ old('shipping_name', auth()->user()->name) }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="shipping_phone" value="{{ old('shipping_phone', auth()->user()->phone) }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Delivery address</label>
                            <textarea name="shipping_address" rows="3" class="form-control" required>{{ old('shipping_address') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Payment method</h6>
                        <p class="small text-muted">Payments are <strong>simulated</strong> for this academic project — no real charge is made.</p>
                        <div class="form-check border rounded p-3 mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="bkash" id="bkash" required>
                            <label class="form-check-label fw-semibold text-danger" for="bkash">bKash</label>
                        </div>
                        <div class="form-check border rounded p-3">
                            <input class="form-check-input" type="radio" name="payment_method" value="nagad" id="nagad">
                            <label class="form-check-label fw-semibold text-warning" for="nagad">Nagad</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 mt-3 mt-lg-0">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Order summary</h6>
                        @foreach ($items as $item)
                            <div class="d-flex justify-content-between small mb-2">
                                <span>{{ $item->variant->product->name }} ({{ $item->variant->label }}) × {{ $item->quantity }}</span>
                                <span>৳{{ number_format($item->subtotal(), 2) }}</span>
                            </div>
                        @endforeach
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span><span>৳{{ number_format($total, 2) }}</span>
                        </div>
                        <button class="btn btn-sa w-100 mt-3">Place order &amp; pay</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
