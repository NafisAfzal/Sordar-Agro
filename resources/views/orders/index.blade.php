@extends('layouts.app')
@section('title', 'My Orders')
@section('content')
    <div class="orders-page">
        <h3 class="mb-4"><i class="bi bi-bag"></i> My orders</h3>

        @if ($orders->isEmpty())
            <div class="orders-empty">
                <div class="orders-empty-icon">
                    <i class="bi bi-bag"></i>
                </div>
                <h5 class="mt-3 mb-2">No orders yet</h5>
                <p class="text-muted mb-3">When you place an order, it will appear here with tracking details.</p>
                <a href="{{ route('products.index') }}" class="btn btn-sa">Start shopping</a>
            </div>
        @else
            <div class="orders-list">
                @foreach ($orders as $order)
                    <div class="card border-0 shadow-sm order-card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <div class="fw-bold order-number">{{ $order->order_number }}</div>
                                    <div class="small text-muted">{{ $order->created_at->format('d M Y') }} &middot; {{ $order->items_count }} item{{ $order->items_count > 1 ? 's' : '' }}</div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if ($order->payment_status === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif ($order->payment_status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">Unpaid</span>
                                    @endif
                                    <span class="badge bg-{{ $order->statusColor() }}">{{ ucfirst($order->status) }}</span>
                                    <span class="fw-bold text-sa">৳{{ number_format($order->total, 2) }}</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                @if ($order->payment_status === 'unpaid')
                                    <a href="{{ route('payment.show', $order) }}" class="btn btn-sm btn-warning">Pay now</a>
                                @else
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View order</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
