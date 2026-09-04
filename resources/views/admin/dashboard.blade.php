@extends('layouts.dashboard')
@section('title', 'Admin Dashboard')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Admin overview</h3>

    {{-- ── All-time stats ────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        @php
            $allTimeCards = [
                ['Customers', $customers, 'people', 'primary', null],
                ['Sellers', $sellers, 'shop', 'info', null],
                ['Products', $products, 'box-seam', 'secondary', null],
                ['Pending approvals', $pending_products, 'hourglass-split', 'warning', route('admin.products.index', ['status' => 'pending'])],
                ['Pending community', $pending_community, 'chat-square-text', 'warning', route('admin.community.index')],
            ];
        @endphp
        @foreach ($allTimeCards as [$label, $value, $icon, $color, $link])
            <div class="col-6 col-md-4 col-lg">
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

    {{-- ── Date filter bar ───────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('admin.dashboard') }}" class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-auto">
                    <label class="form-label small fw-semibold">Period</label>
                    <select name="period" class="form-select form-select-sm" id="analyticsPeriod">
                        @php $options = ['all'=>'All time','today'=>'Today','week'=>'This week','month'=>'This month','custom'=>'Custom range']; @endphp
                        @foreach ($options as $val => $lbl)
                            <option value="{{ $val }}" {{ $period === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-auto custom-dates" style="{{ $period !== 'custom' ? 'display:none' : '' }}">
                    <label class="form-label small fw-semibold">From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                </div>
                <div class="col-6 col-md-auto custom-dates" style="{{ $period !== 'custom' ? 'display:none' : '' }}">
                    <label class="form-label small fw-semibold">To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                    @if ($period !== 'all')
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- ── Period summary cards ──────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        @php
            $periodCards = [
                ['Total orders', $periodOrders, 'cart-check', 'primary'],
                ['Total sales', '৳'.number_format($periodRevenue, 2), 'cash-stack', 'success'],
                ['Units sold', number_format($totalUnitsSold), 'box-seam', 'info'],
                ['Admin own-product sales', '৳'.number_format($ownProductSales, 2), 'building', 'primary'],
                ['Seller-product sales', '৳'.number_format($sellerProductSales, 2), 'shop', 'info'],
                ['Marketplace earnings', '৳'.number_format($marketplaceEarnings, 2), 'wallet2', 'success'],
            ];
        @endphp
        @foreach ($periodCards as [$label, $value, $icon, $color])
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

    {{-- ── Operations snapshot — compact, only shows states that actually exist ── --}}
    @php $hasOps = ($pending_products>0||$pending_community>0||($processingOrders??0)>0||($lowStockProductsCount??0)>0||($outOfStockProducts??0)>0); @endphp
    @if($hasOps)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-bell"></i> Needs attention</h6>
            <div class="row g-2 small">
                @if($pending_products>0)
                    <div class="col-12 col-md-6 col-lg-3"><a href="{{ route('admin.products.index') }}" class="alert alert-warning py-2 px-3 mb-0 d-flex justify-content-between align-items-center text-decoration-none"><span><i class="bi bi-hourglass-split"></i> {{ $pending_products }} pending approvals</span><i class="bi bi-arrow-right"></i></a></div>
                @endif
                @if($pending_community>0)
                    <div class="col-12 col-md-6 col-lg-3"><a href="{{ route('admin.community.index') }}" class="alert alert-warning py-2 px-3 mb-0 d-flex justify-content-between align-items-center text-decoration-none"><span><i class="bi bi-chat-square-text"></i> {{ $pending_community }} community to review</span><i class="bi bi-arrow-right"></i></a></div>
                @endif
                @if(($processingOrders??0)>0)
                    <div class="col-12 col-md-6 col-lg-3"><a href="{{ route('admin.orders.index', ['status'=>'processing']) }}" class="alert alert-warning py-2 px-3 mb-0 d-flex justify-content-between align-items-center text-decoration-none"><span><i class="bi bi-truck"></i> {{ $processingOrders }} processing order{{ $processingOrders>1?'s':'' }}</span><i class="bi bi-arrow-right"></i></a></div>
                @endif
                @if(($lowStockProductsCount??0)>0)
                    <div class="col-12 col-md-6 col-lg-3"><a href="{{ route('admin.products.inventory') }}" class="alert alert-warning py-2 px-3 mb-0 d-flex justify-content-between align-items-center text-decoration-none"><span><i class="bi bi-box"></i> {{ $lowStockProductsCount }} low-stock (≤5)</span><i class="bi bi-arrow-right"></i></a></div>
                @endif
                @if(($outOfStockProducts??0)>0)
                    <div class="col-12 col-md-6 col-lg-3"><div class="alert alert-danger py-2 px-3 mb-0"><i class="bi bi-exclamation-triangle"></i> {{ $outOfStockProducts }} out of stock</div></div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── Sales by product ──────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Sales by product <small class="text-muted fw-normal">(paid, non-cancelled{{ $period !== 'all' ? ', '.$period : '' }})</small></h6>
            @if ($salesByProduct->isEmpty())
                <p class="text-muted mb-0">No paid sales{{ $period !== 'all' ? ' in this period' : '' }}.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Seller</th>
                                <th class="text-end">Units</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Marketplace earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($salesByProduct as $row)
                                <tr>
                                    <td>{{ $row->product_name }}</td>
                                    <td>{{ $row->seller_name }}</td>
                                    <td class="text-end">{{ number_format($row->units_sold) }}</td>
                                    <td class="text-end fw-semibold">৳{{ number_format($row->revenue, 2) }}</td>
                                    <td class="text-end">৳{{ number_format($row->marketplace_earned, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Marketplace share breakdown (seller detail) ───────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Marketplace share by seller &amp; product <small class="text-muted fw-normal">(paid orders{{ $period !== 'all' ? ', '.$period : '' }})</small></h6>
            @if ($shareBreakdown->isEmpty())
                <p class="text-muted mb-0">No paid sales{{ $period !== 'all' ? ' in this period' : '' }}.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Seller</th><th>Product</th><th class="text-end">Units sold</th><th class="text-end">Share earned</th></tr></thead>
                        <tbody>
                            @foreach ($shareBreakdown as $row)
                                <tr>
                                    <td>{{ $row->seller_name }}</td>
                                    <td>{{ $row->product_name }}</td>
                                    <td class="text-end">{{ number_format($row->units_sold) }}</td>
                                    <td class="text-end fw-semibold">৳{{ number_format($row->share_earned, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Recent orders ─────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Recent orders</h6>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">View all orders</a>
            </div>
            @if ($recentOrders->isEmpty())
                <p class="text-muted mb-0">No orders yet.</p>
            @else
                <div class="table-responsive">
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
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('analyticsPeriod').addEventListener('change', function() {
        document.querySelectorAll('.custom-dates').forEach(function(el) {
            el.style.display = this.value === 'custom' ? '' : 'none';
        }.bind(this));
    });
</script>
@endpush
