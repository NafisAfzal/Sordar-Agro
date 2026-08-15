@extends('layouts.dashboard')
@section('title', 'Community Review')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Community submissions</h3>

    <ul class="nav nav-pills mb-3">
        @foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $key => $label)
            <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.community.index', ['status' => $key]) }}">{{ $label }}</a></li>
        @endforeach
    </ul>

    @if ($submissions->isEmpty())
        <div class="alert alert-info">No {{ $status }} submissions.</div>
    @else
        @foreach ($submissions as $post)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h6 class="fw-bold mb-1">{{ $post->title }}</h6>
                        <span class="badge bg-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$post->status] }}">{{ ucfirst($post->status) }}</span>
                    </div>
                    <p class="small text-muted">by {{ $post->user->name }} · {{ $post->created_at->format('d M Y') }}</p>
                    <p>{{ $post->body }}</p>
                    @if ($post->status === 'pending')
                        <form method="POST" action="{{ route('admin.community.approve', $post) }}" class="d-inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Approve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.community.reject', $post) }}" class="d-inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Reject</button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
        <div class="mt-3">{{ $submissions->links() }}</div>
    @endif
@endsection
