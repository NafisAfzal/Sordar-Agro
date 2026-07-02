@extends('layouts.dashboard')
@section('title', 'Sellers')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Partner sellers</h3>
        <a href="{{ route('admin.sellers.create') }}" class="btn btn-sa"><i class="bi bi-plus-lg"></i> Provision seller</a>
    </div>

    @if ($sellers->isEmpty())
        <div class="alert alert-info">No sellers yet. Provision one to get started.</div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Products</th><th>Status</th><th>First login</th></tr></thead>
                    <tbody>
                        @foreach ($sellers as $s)
                            <tr>
                                <td class="fw-semibold">{{ $s->name }}</td>
                                <td class="small">{{ $s->email }}</td>
                                <td>{{ $s->products_count }}</td>
                                <td>@if ($s->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-danger">Suspended</span>@endif</td>
                                <td>@if ($s->must_change_password)<span class="badge bg-warning text-dark">Password not yet changed</span>@else<span class="text-muted small">Done</span>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $sellers->links() }}</div>
    @endif
@endsection
