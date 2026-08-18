@extends('layouts.dashboard')
@section('title', 'Admin Dashboard')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Admin overview</h3>

       <div class="row g-3 mb-5">
        @php
            $cards = [
                ['Customers', $stats['customers'], 'people', 'primary', null],
                ['Sellers', $stats['sellers'], 'shop', 'info', null],
                ['Products', $stats['products'], 'box-seam', 'secondary', null],
                ['Pending approvals', $stats['pending_products'], 'hourglass-split', 'warning', route('admin.products.index', ['status' => 'pending'])],
                ['Orders', $stats['orders'], 'truck', 'success', route('admin.orders.index')],
                ['Revenue (paid)', '৳'.number_format($stats['revenue'], 2), 'cash-stack', 'success', null],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $icon, $color, $link])
            <div class="col-6 col-md-4 col-lg-2">
                @if ($link)
                    <a href="{{ $link }}" class="text-decoration-none">
                @endif
                <div class="card border-0 shadow-sm h-100 {{ $link ? 'dashboard-card-clickable' : '' }}">
                    <div class="card-body">
                        <div class="text-{{ $color }} fs-4"><i class="bi bi-{{ $icon }}"></i></div>
                        <div class="fs-5 fw-bold">{{ $value }}</div>
                        <div class="text-muted small">{{ $label }}</div>
                    </div>
                </div>
                @if ($link)
                    </a>
                @endif
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
            <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold mb-0">Recent orders</h6>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">View all orders</a>
</div>
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
