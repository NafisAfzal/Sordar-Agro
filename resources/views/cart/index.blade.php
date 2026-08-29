@extends('layouts.app')
@section('title', 'Your Cart')
@section('content')
    <div class="cart-page">
        <h3 class="mb-4"><i class="bi bi-cart"></i> Your cart</h3>

        @if ($items->isEmpty())
            <div class="cart-empty">
                <div class="cart-empty-icon">
                    <i class="bi bi-cart-x"></i>
                </div>
                <h5 class="mt-3 mb-2">Your cart is empty</h5>
                <p class="text-muted mb-3">Looks like you haven't added anything yet.</p>
                <a href="{{ route('products.index') }}" class="btn btn-sa">
                    <i class="bi bi-arrow-left"></i> Start shopping
                </a>
            </div>
        @else
            <div class="row">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm cart-items-card">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Price</th>
                                        <th style="width:160px;">Qty</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="cartRows">
                                    @foreach ($items as $item)
                                        <tr id="cartRow{{ $item->id }}" data-cart-id="{{ $item->id }}"
                                            data-price="{{ $item->variant->price }}" data-max="{{ $item->variant->stock }}">
                                            <td>
                                                <div class="cart-product-info">
                                                    <a href="{{ route('products.show', $item->variant->product) }}" class="cart-product-thumb">
                                                        @if ($item->variant->product->thumbnail)
                                                            <img src="{{ asset('storage/'.$item->variant->product->thumbnail) }}" alt="{{ $item->variant->product->name }}">
                                                        @else
                                                            <div class="thumb-placeholder-sm">
                                                                <i class="bi {{ $item->variant->product->category->icon_class ?? 'bi-water' }}"></i>
                                                            </div>
                                                        @endif
                                                    </a>
                                                    <div class="cart-product-details">
                                                        <a href="{{ route('products.show', $item->variant->product) }}" class="cart-product-name">
                                                            {{ $item->variant->product->name }}
                                                        </a>
                                                        <div class="cart-product-meta">
                                                            {{ $item->variant->label }}
                                                            @if ($item->variant->product->is_fish) · pair @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end cart-price">৳{{ number_format($item->variant->price, 2) }}</td>
                                            <td>
                                                <div class="input-group input-group-sm qty-stepper" style="width:130px;">
                                                    <button type="button" class="btn btn-outline-secondary qty-minus" data-cart-id="{{ $item->id }}">−</button>
                                                    <input type="number" class="form-control text-center qty-input"
                                                           value="{{ $item->quantity }}" min="1" max="{{ $item->variant->stock }}"
                                                           data-cart-id="{{ $item->id }}" readonly>
                                                    <button type="button" class="btn btn-outline-secondary qty-plus" data-cart-id="{{ $item->id }}">+</button>
                                                </div>
                                            </td>
                                            <td class="text-end fw-semibold cart-subtotal" data-cart-id="{{ $item->id }}">৳{{ number_format($item->subtotal(), 2) }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('cart.remove', $item) }}" onsubmit="return confirm('Remove this item?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger cart-remove-btn" title="Remove item">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="cart-continue mt-3">
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Continue shopping
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 mt-3 mt-lg-0">
                    <div class="card border-0 shadow-sm cart-summary-card">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Order summary</h6>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Items total</span>
                                <span id="cartTotal">৳{{ number_format($total, 2) }}</span>
                            </div>

                            <div class="d-flex justify-content-between mb-3 text-muted small">
                                <span>Shipping</span>
                                <span>Calculated at checkout</span>
                            </div>

                            <hr>

                            <a href="{{ route('checkout.show') }}" class="btn btn-sa w-100 cart-checkout-btn">
                                Proceed to checkout <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mobile sticky CTA --}}
            <div class="cart-mobile-cta d-lg-none">
                <div class="cart-mobile-cta-inner">
                    <div class="cart-mobile-total">
                        <span class="small text-muted">Total</span>
                        <span class="fw-bold" id="cartTotalMobile">৳{{ number_format($total, 2) }}</span>
                    </div>
                    <a href="{{ route('checkout.show') }}" class="btn btn-sa">
                        Checkout <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const baseUrl = "{{ url('/cart') }}/";
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    function money(n) {
        return '৳' + Number(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function recalcGrandTotal() {
        let sum = 0;
        document.querySelectorAll('.cart-subtotal').forEach(el => {
            sum += parseFloat(el.dataset.raw || '0');
        });
        document.getElementById('cartTotal').textContent = money(sum);
        const mobileEl = document.getElementById('cartTotalMobile');
        if (mobileEl) mobileEl.textContent = money(sum);
    }

    function updateRow(cartId, qty) {
        const row = document.getElementById('cartRow' + cartId);
        const price = parseFloat(row.dataset.price);
        const subtotalEl = row.querySelector('.cart-subtotal');
        const subtotal = price * qty;
        subtotalEl.dataset.raw = subtotal;
        subtotalEl.textContent = money(subtotal);
        recalcGrandTotal();
    }

    document.querySelectorAll('.cart-subtotal').forEach(el => {
        const row = document.getElementById('cartRow' + el.dataset.cartId);
        el.dataset.raw = parseFloat(row.dataset.price) * parseInt(
            row.querySelector('.qty-input').value, 10
        );
    });

    function persist(cartId, qty) {
        fetch(baseUrl + cartId, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ quantity: qty }),
        }).catch(() => {});
    }

    document.querySelectorAll('.qty-minus').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.cartId;
            const input = document.querySelector(`.qty-input[data-cart-id="${id}"]`);
            let val = parseInt(input.value, 10);
            if (val > 1) {
                val -= 1;
                input.value = val;
                updateRow(id, val);
                persist(id, val);
            }
        });
    });

    document.querySelectorAll('.qty-plus').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.cartId;
            const input = document.querySelector(`.qty-input[data-cart-id="${id}"]`);
            const max = parseInt(input.max, 10);
            let val = parseInt(input.value, 10);
            if (val < max) {
                val += 1;
                input.value = val;
                updateRow(id, val);
                persist(id, val);
            }
        });
    });
})();
</script>
@endpush
