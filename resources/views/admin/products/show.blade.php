@extends('layouts.dashboard')
@section('title', 'Review Product')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <a href="{{ route('admin.products.index', ['status' => $product->status]) }}" class="btn btn-outline-secondary btn-sm mb-3">← Back</a>
    <div class="row">
        <div class="col-md-5">
            @if ($product->thumbnail)
                <img src="{{ asset('storage/'.$product->thumbnail) }}" class="img-fluid rounded shadow-sm" alt="">
            @else
                <div class="thumb-placeholder rounded" style="height:300px;"><i class="bi bi-water"></i></div>
            @endif
        </div>
        <div class="col-md-7">
            <h3>{{ $product->name }}</h3>
            <p class="text-muted">
                {{ $product->category->name ?? '—' }} ·
                Seller: {{ $product->seller->name ?? 'Marketplace' }} ·
                <span class="badge bg-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$product->status] }}">{{ ucfirst($product->status) }}</span>
            </p>
            <p>{{ $product->description }}</p>

            <div class="alert alert-info d-flex align-items-center justify-content-between">
                <span><i class="bi bi-cash-coin"></i> <strong>Profit Share Offered</strong></span>
                <span class="fs-5 fw-bold">৳{{ number_format($product->profit_share_amount, 2) }} <small class="fw-normal">per unit sold</small></span>
            </div>

            <table class="table table-sm">
                <thead><tr><th>Size</th><th>Price</th><th>Stock</th><th>Note</th></tr></thead>
                <tbody>
                    @foreach ($product->variants as $v)
                        <tr><td>{{ $v->label }}</td><td>৳{{ number_format($v->price, 2) }}</td><td>{{ $v->stock }}</td><td class="small">{{ $v->size_description }}</td></tr>
                    @endforeach
                </tbody>
            </table>

            @if ($product->status !== 'approved')
                <form method="POST" action="{{ route('admin.products.approve', $product) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-success"><i class="bi bi-check-lg"></i> Approve</button>
                </form>
                <button class="btn btn-danger" data-bs-toggle="collapse" data-bs-target="#rejectBox"><i class="bi bi-x-lg"></i> Reject</button>
                <div class="collapse mt-3" id="rejectBox">
                    <form method="POST" action="{{ route('admin.products.reject', $product) }}">
                        @csrf @method('PATCH')
                        <label class="form-label small fw-semibold">Reason category</label>
                        <select name="rejection_reason_category" class="form-select mb-2" required>
                            <option value="">Choose a reason…</option>
                            @foreach (\App\Models\Product::REJECTION_REASONS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <label class="form-label small fw-semibold">Details for the seller</label>
                        <textarea name="admin_feedback" class="form-control mb-2" rows="3" placeholder="Reason / feedback for the seller" required></textarea>
                        <button class="btn btn-outline-danger btn-sm">Confirm rejection</button>
                    </form>
                </div>
            @else
                <p class="text-success"><i class="bi bi-check-circle"></i> This product is live in the store.</p>
                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline" onsubmit="return confirm('Delete “{{ $product->name }}” permanently? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                </form>
            @endif

            @if ($product->admin_feedback)
                <div class="alert alert-secondary mt-3 small">
                    @if ($product->rejection_reason_category)
                        <span class="badge bg-danger mb-1">Rejected: {{ \App\Models\Product::REJECTION_REASONS[$product->rejection_reason_category] }}</span><br>
                    @endif
                    Previous feedback: {{ $product->admin_feedback }}
                </div>
            @endif
        </div>
    </div>
@endsection
