@extends('layouts.dashboard')
@section('title', 'Admin Dashboard')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Admin overview</h3>

    <div class="row g-3 mb-4">
        @foreach ([
            ['Customers', $stats['customers'], 'people', 'primary'],
            ['Sellers', $stats['sellers'], 'shop', 'info'],
            ['Products', $stats['products'], 'box-seam', 'secondary'],
            ['Pending approvals', $stats['pending_products'], 'hourglass-split', 'warning'],
            ['Orders', $stats['orders'], 'truck', 'success'],
            ['Revenue (paid)', '৳'.number_format($stats['revenue'], 2), 'cash-stack', 'success'],
        ] as [$label, $value, $icon, $color])
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-{{ $color }} fs-4"><i class="bi bi-{{ $icon }}"></i></div>
                        <div class="fs-5 fw-bold">{{ $value }}</div>
                        <div class="text-muted small">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($stats['pending_products'] > 0 || $stats['pending_community'] > 0)
        <div class="alert alert-warning">
            <i class="bi bi-bell"></i>
            You have
            @if ($stats['pending_products'] > 0)<a href="{{ route('admin.products.index') }}">{{ $stats['pending_products'] }} product(s) awaiting approval</a>@endif
            @if ($stats['pending_products'] > 0 && $stats['pending_community'] > 0) and @endif
            @if ($stats['pending_community'] > 0)<a href="{{ route('admin.community.index') }}">{{ $stats['pending_community'] }} community post(s) to review</a>@endif.
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Recent orders</h6>
            @if ($recentOrders->isEmpty())
                <p class="text-muted mb-0">No orders yet.</p>
            @else
                <table class="table mb-0">
                    <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($recentOrders as $o)
                            <tr>
                                <td>{{ $o->order_number }}</td>
                                <td>{{ $o->user->name ?? '—' }}</td>
                                <td>৳{{ number_format($o->total, 2) }}</td>
                                <td><span class="badge bg-{{ $o->payment_status === 'paid' ? 'success' : ($o->payment_status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($o->payment_status) }}</span></td>
                                <td><span class="badge bg-{{ $o->statusColor() }}">{{ ucfirst($o->status) }}</span></td>
                                <td><a href="{{ route('admin.orders.show', $o) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
