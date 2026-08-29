@extends('layouts.app')
@section('title', 'Order '.$order->order_number)
@section('content')
    <div class="order-detail-page">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Order {{ $order->order_number }}</h3>
                <div class="small text-muted mt-1">{{ $order->created_at->format('d M Y, g:i A') }}</div>
            </div>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">Back to orders</a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Items</h6>
                        <div class="order-items-list">
                            @foreach ($order->items as $item)
                                <div class="d-flex justify-content-between align-items-start py-2 @if (!$loop->last) border-bottom @endif">
                                    <div>
                                        <div class="fw-semibold">{{ $item->product_name }}</div>
                                        <div class="small text-muted">{{ $item->variant_size }} &middot; {{ $item->quantity }} × ৳{{ number_format($item->price, 2) }}</div>
                                    </div>
                                    <div class="fw-bold">৳{{ number_format($item->lineTotal(), 2) }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-between fw-bold mt-3 pt-3 border-top">
                            <span>Total</span>
                            <span>৳{{ number_format($order->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Tracking</h6>
                        <p class="mb-1">Status: <span class="badge bg-{{ $order->statusColor() }}">{{ ucfirst($order->status) }}</span></p>
                        @if ($order->courier)
                            <p class="mb-1 small">Courier: <strong>{{ ucfirst($order->courier) }}</strong></p>
                        @endif
                        @if ($order->tracking_code)
                            <p class="mb-1 small">Tracking code: <code>{{ $order->tracking_code }}</code></p>
                        @else
                            <p class="text-muted small mb-0">A tracking code appears once your order ships.</p>
                        @endif
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Shipping</h6>
                        <p class="small mb-0">
                            {{ $order->shipping_name }}<br>
                            {{ $order->shipping_phone }}<br>
                            {{ $order->shipping_address }}
                        </p>
                        <hr>
                        <p class="small mb-0">
                            Payment: {{ ucfirst($order->payment_method) }}
                            ({{ ucfirst($order->payment_status) }})
                            @if ($order->transaction_id)<br>Txn: <code>{{ $order->transaction_id }}</code>@endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
