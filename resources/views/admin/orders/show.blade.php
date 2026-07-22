@extends('layouts.dashboard')
@section('title', 'Manage Order')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm mb-3">← Back to orders</a>
    <h3 class="mb-1">Order {{ $order->order_number }}</h3>
    <p class="text-muted">{{ $order->created_at->format('d M Y, H:i') }} · {{ $order->user->name ?? '—' }}</p>

    <div class="row">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold">Items</h6>
                    <table class="table mb-0">
                        <thead><tr><th>Product</th><th>Size</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr><td>{{ $item->product_name }}</td><td>{{ $item->variant_size }}</td><td>৳{{ number_format($item->price, 2) }}</td><td>{{ $item->quantity }}</td><td>৳{{ number_format($item->lineTotal(), 2) }}</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td>৳{{ number_format($order->total, 2) }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body small">
                    <h6 class="fw-bold">Shipping</h6>
                    {{ $order->shipping_name }} · {{ $order->shipping_phone }}<br>
                    {{ $order->shipping_address }}<br>
                    <hr>
                    Payment: {{ ucfirst($order->payment_method) }} ({{ ucfirst($order->payment_status) }})
                    @if ($order->transaction_id) · Txn <code>{{ $order->transaction_id }}</code>@endif
                </div>
            </div>
        </div>

        <div class="col-lg-5 mt-3 mt-lg-0">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold">Fulfilment</h6>
                    <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                        @csrf @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select">
                                @foreach (['processing','shipped','delivered','cancelled'] as $s)
                                    <option value="{{ $s }}" @selected($order->status === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Courier</label>
                            <select name="courier" class="form-select">
                                <option value="">— none —</option>
                                <option value="pathao" @selected($order->courier === 'pathao')>Pathao</option>
                                <option value="steadfast" @selected($order->courier === 'steadfast')>Steadfast</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Tracking code</label>
                            <input type="text" name="tracking_code" value="{{ $order->tracking_code }}" class="form-control" placeholder="Auto-generated on ship if blank">
                        </div>
                        <button class="btn btn-sa w-100">Update order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
