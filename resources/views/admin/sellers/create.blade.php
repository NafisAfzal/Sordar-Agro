@extends('layouts.dashboard')
@section('title', 'Provision Seller')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Provision a partner seller</h3>
    <div class="row">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="small text-muted">The seller will be required to change this temporary password on first login.</p>
                    <form method="POST" action="{{ route('admin.sellers.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Seller name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone (optional)</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Temporary password</label>
                            <input type="text" name="password" value="{{ old('password', $suggested) }}" class="form-control" required>
                            <small class="text-muted">Share this securely with the seller.</small>
                        </div>
                        <button class="btn btn-sa">Create seller</button>
                        <a href="{{ route('admin.sellers.index') }}" class="btn btn-link">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
