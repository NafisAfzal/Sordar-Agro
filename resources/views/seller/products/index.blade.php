@extends('layouts.dashboard')
@section('title', 'My Products')
@section('sidebar') @include('partials.seller-sidebar') @endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">My products</h3>
        <a href="{{ route('seller.products.create') }}" class="btn btn-sa"><i class="bi bi-plus-lg"></i> Add product</a>
    </div>

    @if ($products->isEmpty())
        <div class="alert alert-info">You haven't listed any products yet.</div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Category</th><th>From</th><th>Stock</th><th>Profit Share</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $p)
                            <tr>
                                <td class="fw-semibold">{{ $p->name }}</td>
                                <td>{{ $p->category->name ?? '—' }}</td>
                                <td>৳{{ number_format($p->starting_price, 2) }}</td>
                                <td>{{ $p->total_stock }}</td>
                                <td>৳{{ number_format($p->profit_share_amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$p->status] }}">{{ ucfirst($p->status) }}</span>
                                    @if ($p->status === 'rejected' && $p->rejection_reason_category)
                                        <span class="badge bg-danger">{{ \App\Models\Product::REJECTION_REASONS[$p->rejection_reason_category] }}</span>
                                    @endif
                                    @if ($p->status === 'rejected' && $p->admin_feedback)
                                        <i class="bi bi-info-circle text-danger" title="{{ $p->admin_feedback }}"></i>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if ($p->status === 'approved')
                                        <span class="text-muted small">Locked</span>
                                    @else
                                        <a href="{{ route('seller.products.edit', $p) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="{{ route('seller.products.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Delete this product?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $products->links() }}</div>
    @endif
@endsection
