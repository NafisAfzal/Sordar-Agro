@extends('layouts.app')
@section('title', 'Care Guides')
@section('content')
    <h3 class="mb-4"><i class="bi bi-journal-text"></i> Fish &amp; plant care guides</h3>

    @if ($guides->isEmpty())
        <div class="alert alert-info">No care guides published yet.</div>
    @else
        <div class="row g-3">
            @foreach ($guides as $guide)
                <div class="col-md-4">
                    <a href="{{ route('care.show', $guide) }}" class="card product-card text-decoration-none h-100">
                        @if ($guide->image)
                            <img src="{{ asset('storage/'.$guide->image) }}" class="card-img-top product-thumb" alt="">
                        @else
                            <div class="thumb-placeholder"><i class="bi bi-journal-text"></i></div>
                        @endif
                        <div class="card-body">
                            <h6 class="text-dark">{{ $guide->title }}</h6>
                            <p class="text-muted small mb-2">{{ Str::limit($guide->excerpt, 100) }}</p>
                            <small class="text-muted">{{ optional($guide->published_at)->format('d M Y') }}</small>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $guides->links() }}</div>
    @endif
@endsection
