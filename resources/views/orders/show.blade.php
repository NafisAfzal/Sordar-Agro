@extends('layouts.app')
@section('title', 'Order '.$order->order_number)
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Order {{ $order->order_number }}</h3>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">Back to orders</a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold">Items</h6>
                    <table class="table mb-0">
                        <thead><tr><th>Product</th><th>Size</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->variant_size }}</td>
                                    <td>৳{{ number_format($item->price, 2) }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>৳{{ number_format($item->lineTotal(), 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td>৳{{ number_format($order->total, 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold">Tracking</h6>
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
                    <h6 class="fw-bold">Shipping</h6>
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
@endsection
