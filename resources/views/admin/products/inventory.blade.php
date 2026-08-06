@extends('layouts.dashboard')
@section('title', 'Inventory')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Inventory management</h3>
    <p class="text-muted small">Adjust stock per variant. Positive numbers add stock (e.g. a new shipment); negative numbers reduce it (e.g. fish loss). Increasing stock on an out-of-stock product notifies wishlist subscribers.</p>

    <form method="GET" class="mb-3">
        <div class="input-group" style="max-width:360px;">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search products">
            <button class="btn btn-sa">Search</button>
        </div>
    </form>

    @foreach ($products as $product)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6 class="fw-bold mb-2">{{ $product->name }}
                        <small class="text-muted">({{ $product->category->name ?? '—' }})</small>
                    </h6>
                    @if ($product->isOutOfStock())<span class="badge bg-danger">Out of stock</span>@endif
                </div>
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Size</th><th>Current stock</th><th>Adjust by</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($product->variants as $v)
                            <tr>
                                <td>{{ $v->label }}</td>
                                <td><span class="badge bg-{{ $v->stock > 0 ? 'success' : 'secondary' }}">{{ $v->stock }}</span></td>
                                <td colspan="2">
                                    <form method="POST" action="{{ route('admin.inventory.adjust', $v) }}" class="d-flex gap-2" style="max-width:280px;">
                                        @csrf @method('PATCH')
                                        <input type="number" name="delta" class="form-control form-control-sm" placeholder="e.g. +10 or -2" required>
                                        <button class="btn btn-sm btn-sa">Apply</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
    <div class="mt-3">{{ $products->links() }}</div>
@endsection
