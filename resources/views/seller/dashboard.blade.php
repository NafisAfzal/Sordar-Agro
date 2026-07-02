@extends('layouts.dashboard')
@section('title', 'Seller Dashboard')
@section('sidebar') @include('partials.seller-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Welcome, {{ auth()->user()->name }} 👋</h3>

    <div class="row g-3 mb-4">
        @foreach ([
            ['Total products', $stats['total'], 'box-seam', 'primary'],
            ['Approved', $stats['approved'], 'check-circle', 'success'],
            ['Pending', $stats['pending'], 'hourglass-split', 'warning'],
            ['Rejected', $stats['rejected'], 'x-circle', 'danger'],
        ] as [$label, $value, $icon, $color])
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-{{ $color }} fs-3"><i class="bi bi-{{ $icon }}"></i></div>
                        <div class="fs-4 fw-bold">{{ $value }}</div>
                        <div class="text-muted small">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Recent products</h6>
                <a href="{{ route('seller.products.create') }}" class="btn btn-sa btn-sm">Add product</a>
            </div>
            @if ($recent->isEmpty())
                <p class="text-muted mb-0">No products yet. Add your first listing.</p>
            @else
                <table class="table mb-0">
                    <thead><tr><th>Name</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                        @foreach ($recent as $p)
                            <tr>
                                <td>{{ $p->name }}</td>
                                <td>
                                    <span class="badge bg-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$p->status] }}">
                                        {{ ucfirst($p->status) }}
                                    </span>
                                </td>
                                <td class="small">{{ $p->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
