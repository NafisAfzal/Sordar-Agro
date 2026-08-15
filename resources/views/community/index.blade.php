@extends('layouts.app')
@section('title', 'Community Knowledge')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-people"></i> Community knowledge</h3>
        @auth
            @if (auth()->user()->canShop())
                <a href="{{ route('community.create') }}" class="btn btn-sa btn-sm"><i class="bi bi-plus-lg"></i> Contribute</a>
            @endif
        @endauth
    </div>

    <p class="text-muted">Tips and experiences shared by fellow aquarists — reviewed by our team before publishing.</p>

    @if ($submissions->isEmpty())
        <div class="alert alert-info">No community posts yet. Be the first to contribute!</div>
    @else
        <div class="row g-3">
            @foreach ($submissions as $post)
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="fw-bold">{{ $post->title }}</h6>
                            <p class="small text-muted mb-2">by {{ $post->user->name }} · {{ $post->created_at->format('d M Y') }}</p>
                            <p class="mb-0">{{ Str::limit($post->body, 220) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $submissions->links() }}</div>
    @endif
@endsection
