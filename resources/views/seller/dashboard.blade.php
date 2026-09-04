@extends('layouts.dashboard')
@section('title', 'Seller Dashboard')
@section('sidebar') @include('partials.seller-sidebar') @endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 class="mb-0">Welcome, {{ auth()->user()->name }} 👋</h3>
        <a href="{{ route('seller.products.create') }}" class="btn btn-sa btn-sm"><i class="bi bi-plus-lg"></i> Add product</a>
    </div>
    <p class="text-muted small mb-4">Your store performance — all sales count paid orders only (cancelled excluded). Earnings = gross sales minus marketplace share (snapshot per sale).</p>

    {{-- Period filter (same carbon semantics as admin) --}}
    <form method="GET" action="{{ route('seller.dashboard') }}" class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-auto">
                    <label class="form-label small fw-semibold" for="sellerPeriod">Period</label>
                    <select name="period" id="sellerPeriod" class="form-select form-select-sm">
                        @php $opts = ['all'=>'All time','today'=>'Today','week'=>'This week','month'=>'This month','custom'=>'Custom range']; @endphp
                        @foreach ($opts as $val => $lbl)
                            <option value="{{ $val }}" @selected($period===$val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-auto seller-custom-dates" style="{{ $period!=='custom' ? 'display:none' : '' }}">
                    <label class="form-label small fw-semibold">From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom?->format('Y-m-d') }}">
                </div>
                <div class="col-6 col-md-auto seller-custom-dates" style="{{ $period!=='custom' ? 'display:none' : '' }}">
                    <label class="form-label small fw-semibold">To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo?->format('Y-m-d') }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                    @if($period!=='all')
                        <a href="{{ route('seller.dashboard') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Catalogue status overview --}}
    <div class="row g-3 mb-4">
        @foreach ([
            ['Total products', $stats['total'], 'box-seam', 'primary'],
            ['Approved', $stats['approved'], 'check-circle', 'success'],
            ['Pending', $stats['pending'], 'hourglass-split', 'warning'],
            ['Rejected', $stats['rejected'], 'x-circle', 'danger'],
            ['Out of stock', $outOfStockCount, 'exclamation-triangle', 'danger'],
            ['Low stock (≤5)', $lowStockCount, 'box', 'warning'],
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

    {{-- Summary cards: money + operations --}}
    <div class="row g-3 mb-4">
        @php
            $summaryCards = [
                ['Gross sales', '৳'.number_format($periodGross,2), 'cash-stack', 'success', 'Paid, non-cancelled'.($period!=='all' ? ', '.$period : '')],
                ['Seller earnings', '৳'.number_format($periodEarnings,2), 'wallet2', 'primary', 'Gross minus marketplace share'],
                ['Units sold', number_format($periodUnits), 'box-seam', 'info', 'Across your products'],
                ['Orders (with your items)', number_format($periodOrders), 'cart-check', 'primary', 'Distinct paid orders'],
                ['Pending fulfilment', number_format($pendingOrders), 'truck', 'warning', 'Processing & paid'],
            ];
        @endphp
        @foreach ($summaryCards as [$label, $value, $icon, $color, $hint])
            <div class="col-6 col-lg">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-{{ $color }} fs-4"><i class="bi bi-{{ $icon }}"></i></div>
                        <div class="fs-5 fw-bold text-truncate" title="{{ $value }}">{{ $value }}</div>
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="text-muted" style="font-size:.7rem">{{ $hint }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Needs Attention — compact, only when something actually needs attention --}}
    @php
        $hasAttention = $stats['pending']>0 || $stats['rejected']>0 || $lowStockCount>0 || $outOfStockCount>0 || $pendingOrders>0;
    @endphp
    @if($hasAttention)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-bell"></i> Needs attention</h6>
            <div class="row g-2">
                @if($pendingOrders>0)
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="alert alert-warning py-2 px-3 mb-0 small d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-truck"></i> {{ $pendingOrders }} order{{ $pendingOrders>1?'s':'' }} awaiting fulfilment</span>
                            <span class="badge bg-warning text-dark">{{ $pendingOrders }}</span>
                        </div>
                    </div>
                @endif
                @if($stats['pending']>0)
                    <div class="col-12 col-md-6 col-lg-3">
                        <a href="{{ route('seller.products.index') }}" class="alert alert-warning py-2 px-3 mb-0 small d-flex justify-content-between align-items-center text-decoration-none">
                            <span><i class="bi bi-hourglass-split"></i> {{ $stats['pending'] }} product{{ $stats['pending']>1?'s':'' }} pending approval</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                @endif
                @if($stats['rejected']>0)
                    <div class="col-12 col-md-6 col-lg-3">
                        <a href="{{ route('seller.products.index') }}" class="alert alert-danger py-2 px-3 mb-0 small d-flex justify-content-between align-items-center text-decoration-none">
                            <span><i class="bi bi-x-circle"></i> {{ $stats['rejected'] }} rejected — fix & resubmit</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                @endif
                @if($lowStockCount>0)
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="alert alert-warning py-2 px-3 mb-0 small">
                            <i class="bi bi-box"></i> {{ $lowStockCount }} low-stock product{{ $lowStockCount>1?'s':'' }} (≤5 units): {{ $lowStockProducts->take(3)->pluck('name')->join(', ') }}{{ $lowStockCount>3 ? '…' : '' }}
                        </div>
                    </div>
                @endif
                @if($outOfStockCount>0)
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="alert alert-danger py-2 px-3 mb-0 small">
                            <i class="bi bi-exclamation-triangle"></i> {{ $outOfStockCount }} out of stock: {{ $outOfStockProducts->take(3)->pluck('name')->join(', ') }}{{ $outOfStockCount>3 ? '…' : '' }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Sales overview chart --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0">Sales overview <small class="text-muted fw-normal">({{ $period==='all'?'all time':$period }})</small></h6>
                <span class="small text-muted">Gross vs earnings</span>
            </div>
            @if($chartRows->isEmpty())
                <div class="text-center py-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light" style="width:48px;height:48px"><i class="bi bi-graph-up text-muted"></i></div>
                    <p class="text-muted small mt-2 mb-0">No sales in this period. Your chart will appear once orders are paid.</p>
                </div>
            @else
                <div style="position:relative;height:220px">
                    <canvas id="sellerSalesChart" aria-label="Seller sales trend" role="img"></canvas>
                </div>
                <div class="d-flex gap-3 mt-2 small">
                    <span><span class="d-inline-block rounded" style="width:10px;height:10px;background:var(--sa-secondary)"></span> Gross</span>
                    <span><span class="d-inline-block rounded" style="width:10px;height:10px;background:var(--sa-primary)"></span> Earnings</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Product performance --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Product performance</h6>
                <a href="{{ route('seller.products.index') }}" class="btn btn-sm btn-outline-secondary">Manage products</a>
            </div>
            @if($productPerformance->isEmpty() || $stats['total']==0)
                <p class="text-muted small mb-0">No products yet. Add your first listing to see performance here.</p>
            @elseif($periodUnits==0)
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Product</th><th>Status</th><th class="text-end">Stock</th><th class="text-end">Units sold</th><th class="text-end">Gross</th><th class="text-end">Earnings</th></tr></thead>
                        <tbody>
                            @foreach($productPerformance as $row)
                                <tr>
                                    <td class="fw-semibold text-truncate" style="max-width:180px" title="{{ $row['product']->name }}">{{ $row['product']->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$row['status']] }}">{{ ucfirst($row['status']) }}</span>
                                        @if($row['stock']==0)<span class="badge bg-danger ms-1">Out</span>
                                        @elseif($row['stock']<=5)<span class="badge bg-warning text-dark ms-1">Low</span>@endif
                                    </td>
                                    <td class="text-end">{{ $row['stock'] }}</td>
                                    <td class="text-end text-muted">—</td>
                                    <td class="text-end text-muted">—</td>
                                    <td class="text-end text-muted">—</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mt-2 mb-0">No sales in this period — stock and status shown.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Product</th><th>Status</th><th class="text-end">Stock</th><th class="text-end">Units</th><th class="text-end">Gross</th><th class="text-end">Earnings</th></tr></thead>
                        <tbody>
                            @foreach($productPerformance as $row)
                                <tr>
                                    <td class="fw-semibold text-truncate" style="max-width:180px" title="{{ $row['product']->name }}">{{ $row['product']->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$row['status']] }}">{{ ucfirst($row['status']) }}</span>
                                        @if($row['stock']==0)<span class="badge bg-danger ms-1">Out</span>
                                        @elseif($row['stock']<=5)<span class="badge bg-warning text-dark ms-1">Low</span>@endif
                                        @if($loop->first && $row['units']>0)<span class="badge bg-success ms-1">Best</span>@endif
                                    </td>
                                    <td class="text-end">{{ $row['stock'] }}</td>
                                    <td class="text-end">{{ $row['units'] }}</td>
                                    <td class="text-end">৳{{ number_format($row['gross'],2) }}</td>
                                    <td class="text-end fw-semibold">৳{{ number_format($row['earnings'],2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Recent orders (seller-relevant only) --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Recent orders with your products</h6>
            @if($recentOrdersRows->isEmpty())
                <p class="text-muted small mb-0">No paid orders contain your products{{ $period!=='all' ? ' in this period' : '' }}. Orders appear here once a customer pays.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th class="text-end">Qty</th><th class="text-end">Gross</th><th class="text-end">Earnings</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($recentOrdersRows as $o)
                                <tr>
                                    <td class="fw-semibold text-truncate" style="max-width:130px" title="{{ $o->order_number }}">{{ $o->order_number }}</td>
                                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($o->created_at)->format('d M Y') }}</td>
                                    <td class="small">{{ $o->customer_name }}</td>
                                    <td class="text-end">{{ $o->seller_units }}</td>
                                    <td class="text-end">৳{{ number_format($o->seller_gross,2) }}</td>
                                    <td class="text-end fw-semibold">৳{{ number_format($o->seller_gross - $o->seller_share,2) }}</td>
                                    <td><span class="badge bg-{{ ['processing'=>'warning','shipped'=>'info','delivered'=>'success','cancelled'=>'danger'][$o->status] ?? 'secondary' }}">{{ ucfirst($o->status) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mt-2 mb-0">Amounts show only your products in each order.</p>
            @endif
        </div>
    </div>

    {{-- Recent catalogue --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Recent products</h6>
                <a href="{{ route('seller.products.create') }}" class="btn btn-sa btn-sm">Add product</a>
            </div>
            @if ($recent->isEmpty())
                <p class="text-muted mb-0">No products yet. Add your first listing.</p>
            @else
                <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Name</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                        @foreach ($recent as $p)
                            <tr>
                                <td class="text-truncate" style="max-width:220px">{{ $p->name }}</td>
                                <td><span class="badge bg-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$p->status] }}">{{ ucfirst($p->status) }}</span></td>
                                <td class="small">{{ $p->created_at->format('d M Y') }}</td>
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
(function(){
    var periodSel = document.getElementById('sellerPeriod');
    if(periodSel){
        periodSel.addEventListener('change', function(){
            document.querySelectorAll('.seller-custom-dates').forEach(function(el){
                el.style.display = this.value==='custom' ? '' : 'none';
            }.bind(this));
        });
    }
})();
(function(){
    var canvas = document.getElementById('sellerSalesChart');
    if(!canvas) return;
    var labels = @json($chartLabels);
    var gross = @json($chartGross);
    var earnings = @json($chartEarnings);
    if(!labels.length) return;
    var dpr = window.devicePixelRatio || 1;
    var ctx = canvas.getContext('2d');
    function draw(){
        var rect = canvas.parentElement.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = 220 * dpr;
        canvas.style.width = rect.width+'px';
        canvas.style.height = '220px';
        ctx.setTransform(dpr,0,0,dpr,0,0);
        ctx.clearRect(0,0,rect.width,220);
        var pad = {l:44, r:12, t:16, b:28};
        var w = rect.width - pad.l - pad.r;
        var h = 220 - pad.t - pad.b;
        var max = Math.max.apply(null, gross.concat(earnings).concat([1]));
        max = max*1.15 || 1;
        var step = labels.length<=1 ? w : w/(labels.length-1);
        function x(i){ return pad.l + (labels.length<=1 ? w/2 : i*step); }
        function y(v){ return pad.t + h - (v/max)*h; }
        // grid
        ctx.strokeStyle = 'rgba(0,0,0,0.06)'; ctx.lineWidth=1;
        for(var gi=0; gi<=4; gi++){
            var gy = pad.t + (h/4)*gi;
            ctx.beginPath(); ctx.moveTo(pad.l, gy); ctx.lineTo(pad.l+w, gy); ctx.stroke();
            ctx.fillStyle='#637579'; ctx.font='11px Inter,system-ui'; ctx.textAlign='right';
            ctx.fillText('৳'+Math.round(max*(1-gi/4)).toLocaleString(), pad.l-8, gy+3);
        }
        function line(data, color, fill){
            if(fill){
                ctx.beginPath(); ctx.moveTo(x(0), y(data[0]));
                data.forEach(function(v,i){ ctx.lineTo(x(i), y(v)); });
                ctx.lineTo(x(data.length-1), pad.t+h); ctx.lineTo(x(0), pad.t+h); ctx.closePath();
                ctx.fillStyle=fill; ctx.fill();
            }
            ctx.beginPath(); ctx.strokeStyle=color; ctx.lineWidth=2; ctx.lineJoin='round'; ctx.lineCap='round';
            data.forEach(function(v,i){ if(i===0) ctx.moveTo(x(i), y(v)); else ctx.lineTo(x(i), y(v)); });
            ctx.stroke();
            data.forEach(function(v,i){
                ctx.beginPath(); ctx.fillStyle=color; ctx.arc(x(i), y(v), 3, 0, Math.PI*2); ctx.fill();
                ctx.fillStyle='#fff'; ctx.beginPath(); ctx.arc(x(i), y(v), 1.5, 0, Math.PI*2); ctx.fill();
            });
        }
        line(gross, '#2C7A7B', 'rgba(44,122,123,0.08)');
        line(earnings, '#0F4C5C', 'rgba(15,76,92,0.06)');
        // x labels
        ctx.fillStyle='#637579'; ctx.font='11px Inter,system-ui'; ctx.textAlign='center';
        labels.forEach(function(l,i){
            if(labels.length>8 && i%Math.ceil(labels.length/8)!==0) return;
            ctx.fillText(l, x(i), pad.t+h+18);
        });
    }
    draw();
    var ro = window.ResizeObserver ? new ResizeObserver(draw) : null;
    if(ro) ro.observe(canvas.parentElement); else window.addEventListener('resize', draw);
    if(window.matchMedia('(prefers-reduced-motion: reduce)').matches){
        // no animation needed — static draw already done
    }
})();
</script>
@endpush
