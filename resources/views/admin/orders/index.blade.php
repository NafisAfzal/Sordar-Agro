@extends('layouts.dashboard')
@section('title', 'Orders')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Orders &amp; delivery</h3>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link {{ !request('status') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">All</a></li>
        @foreach (['processing','shipped','delivered','cancelled'] as $s)
            <li class="nav-item"><a class="nav-link {{ request('status') === $s ? 'active' : '' }}" href="{{ route('admin.orders.index', ['status' => $s]) }}">{{ ucfirst($s) }}</a></li>
        @endforeach
    </ul>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Courier</th><th></th></tr></thead>
                <tbody>
                    @forelse ($orders as $o)
                        <tr>
                            <td class="fw-semibold">{{ $o->order_number }}</td>
                            <td class="small">{{ $o->user->name ?? '—' }}</td>
                            <td>৳{{ number_format($o->total, 2) }}</td>
                            <td><span class="badge bg-{{ $o->payment_status === 'paid' ? 'success' : ($o->payment_status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($o->payment_status) }}</span></td>
                            <td><span class="badge bg-{{ $o->statusColor() }}">{{ ucfirst($o->status) }}</span></td>
                            <td class="small">{{ $o->courier ? ucfirst($o->courier) : '—' }}</td>
                            <td><a href="{{ route('admin.orders.show', $o) }}" class="btn btn-sm btn-outline-secondary">Manage</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $orders->links() }}</div>
@endsection
