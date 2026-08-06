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
                        <textarea name="admin_feedback" class="form-control mb-2" rows="3" placeholder="Reason / feedback for the seller" required></textarea>
                        <button class="btn btn-outline-danger btn-sm">Confirm rejection</button>
                    </form>
                </div>
            @else
                <p class="text-success"><i class="bi bi-check-circle"></i> This product is live in the store.</p>
            @endif

            @if ($product->admin_feedback)
                <div class="alert alert-secondary mt-3 small">Previous feedback: {{ $product->admin_feedback }}</div>
            @endif
        </div>
    </div>
@endsection
