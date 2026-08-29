@extends('layouts.app')
@section('title', 'Checkout')

@section('content')
    <div class="checkout-page">
        <h3 class="mb-3"><i class="bi bi-bag-check"></i> Checkout</h3>
        <div class="d-flex align-items-center gap-2 mb-4 small">
            <span class="text-muted"><i class="bi bi-check-circle-fill text-success"></i> Cart</span>
            <span class="text-muted">→</span>
            <span class="fw-bold text-sa"><i class="bi bi-circle-fill"></i> Shipping &amp; Payment</span>
            <span class="text-muted">→</span>
            <span class="text-muted">Confirmation</span>
        </div>

        <form method="POST" action="{{ route('checkout.place') }}">
            @csrf

            <div class="row">
                <div class="col-lg-7">

                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Shipping details</h6>

                            <div class="mb-3">
                                <label class="form-label" for="shipping_name">Full name</label>
                                <input
                                    type="text"
                                    id="shipping_name"
                                    name="shipping_name"
                                    value="{{ old('shipping_name', auth()->user()->name) }}"
                                    class="form-control"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="shipping_phone">Phone</label>
                                <input
                                    type="text"
                                    id="shipping_phone"
                                    name="shipping_phone"
                                    value="{{ old('shipping_phone', auth()->user()->phone) }}"
                                    class="form-control"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="shipping_address">Delivery address</label>
                                <textarea
                                    id="shipping_address"
                                    name="shipping_address"
                                    rows="3"
                                    class="form-control"
                                    required>{{ old('shipping_address') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Payment method</h6>

                            <label class="payment-option payment-option-bkash mb-2" for="bkash">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="payment_method"
                                    value="bkash"
                                    id="bkash"
                                    required>

                                <span class="payment-option-body">
                                    <i class="bi bi-phone fs-4"></i>
                                    <span class="fw-bold fs-5">bKash</span>
                                </span>
                            </label>

                            <label class="payment-option payment-option-nagad" for="nagad">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="payment_method"
                                    value="nagad"
                                    id="nagad">

                                <span class="payment-option-body">
                                    <i class="bi bi-phone fs-4"></i>
                                    <span class="fw-bold fs-5">Nagad</span>
                                </span>
                            </label>

                        </div>
                    </div>

                </div>

                <div class="col-lg-5 mt-3 mt-lg-0">
                    <div class="card border-0 shadow-sm checkout-summary-card">
                        <div class="checkout-summary-toggle d-lg-none" id="summaryToggle">
                            <span class="fw-bold">Order summary</span>
                            <span class="d-flex align-items-center gap-2">
                                <span class="text-muted small">{{ $items->count() }} item{{ $items->count() > 1 ? 's' : '' }}</span>
                                <span class="fw-bold text-sa">৳{{ number_format($total, 2) }}</span>
                                <i class="bi bi-chevron-down" id="summaryChevron"></i>
                            </span>
                        </div>
                        <div class="card-body checkout-summary-body" id="summaryBody">
                            <h6 class="fw-bold mb-3 d-none d-lg-block">Order summary</h6>

                            @foreach ($items as $item)
                                <div class="checkout-summary-item">
                                    <div class="checkout-item-info">
                                        @if ($item->variant->product->thumbnail)
                                            <img src="{{ asset('storage/'.$item->variant->product->thumbnail) }}" class="checkout-item-thumb" alt="{{ $item->variant->product->name }}">
                                        @else
                                            <div class="checkout-item-thumb-placeholder">
                                                <i class="bi {{ $item->variant->product->category->icon_class ?? 'bi-water' }}"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="checkout-item-name">{{ $item->variant->product->name }}</div>
                                            <div class="checkout-item-meta">{{ $item->variant->label }} × {{ $item->quantity }}</div>
                                        </div>
                                    </div>
                                    <div class="checkout-item-price">৳{{ number_format($item->subtotal(), 2) }}</div>
                                </div>
                            @endforeach

                            <hr>

                            <div class="d-flex justify-content-between mb-2 text-muted small">
                                <span>Shipping</span>
                                <span>Calculated at checkout</span>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between fw-bold checkout-total">
                                <span>Total</span>
                                <span>৳{{ number_format($total, 2) }}</span>
                            </div>

                            <button class="btn btn-sa w-100 mt-3 checkout-place-btn" id="placeOrderBtn">Place order &amp; pay</button>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
@push('scripts')
<script>
    document.querySelector('form')?.addEventListener('submit', function () {
        const btn = document.getElementById('placeOrderBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Placing order...';
        }
    });

    const toggle = document.getElementById('summaryToggle');
    const body = document.getElementById('summaryBody');
    const chevron = document.getElementById('summaryChevron');
    if (toggle && body) {
        toggle.addEventListener('click', function () {
            body.classList.toggle('open');
            chevron.classList.toggle('rotated');
        });
    }
</script>
@endpush
@endsection
