@extends('layouts.app')
@section('title', 'My Orders')
@section('content')
    <h3 class="mb-4"><i class="bi bi-bag"></i> My orders</h3>

    @if ($orders->isEmpty())
        <div class="text-center py-5">
    <i class="bi bi-bag text-muted" style="font-size:3rem;"></i>
    <h5 class="mt-3">No orders yet</h5>
    <p class="text-muted">When you place an order, it will appear here with tracking details.</p>
    <a href="{{ route('products.index') }}" class="btn btn-sa">Start shopping</a>
</div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="fw-semibold">{{ $order->order_number }}</td>
                                <td class="small">{{ $order->created_at->format('d M Y') }}</td>
                                <td>{{ $order->items_count }}</td>
                                <td>৳{{ number_format($order->total, 2) }}</td>
                                <td>
                                    @if ($order->payment_status === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif ($order->payment_status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">Unpaid</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $order->statusColor() }}">{{ ucfirst($order->status) }}</span></td>
                                <td>
                                    @if ($order->payment_status === 'unpaid')
                                        <a href="{{ route('payment.show', $order) }}" class="btn btn-sm btn-warning">Pay now</a>
                                    @else
                                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $orders->links() }}</div>
    @endif
@endsection
