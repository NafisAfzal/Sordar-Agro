@extends('layouts.app')
@section('title', $guide->title)
@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="{{ route('care.index') }}" class="btn btn-outline-secondary btn-sm mb-3">← All guides</a>
            <article class="card border-0 shadow-sm">
                @if ($guide->image)
                    <img src="{{ asset('storage/'.$guide->image) }}" class="card-img-top" style="max-height:340px;object-fit:cover;" alt="">
                @endif
                <div class="card-body p-4">
                    <h2>{{ $guide->title }}</h2>
                    <p class="text-muted small">
                        {{ optional($guide->published_at)->format('d M Y') }}
                        @if ($guide->author) · by {{ $guide->author->name }} @endif
                    </p>
                    <hr>
                    <div class="care-content">{!! nl2br(e($guide->content)) !!}</div>
                </div>
            </article>
        </div>
    </div>
@endsection
