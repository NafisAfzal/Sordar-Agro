@extends('layouts.app')
@section('title', $guide->title.' — Sordar Agro Care Guides')
@section('meta_description', $guide->excerpt
    ? Str::limit($guide->excerpt, 155)
    : Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($guide->content))), 155))
@section('canonical_url', route('care.show', $guide))
@if ($guide->image)
    @section('og_image', asset('storage/'.$guide->image))
@endif
@php
    $careBreadcrumbLd = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Care Guides', 'item' => route('care.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $guide->title, 'item' => route('care.show', $guide)],
        ],
    ];
@endphp
@section('content')
    <script type="application/ld+json">{!! json_encode($careBreadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="{{ route('care.index') }}" class="btn btn-outline-secondary btn-sm mb-3">← All guides</a>
            <article class="card border-0 shadow-sm">
                @if ($guide->image)
                    <img src="{{ asset('storage/'.$guide->image) }}" class="card-img-top" style="max-height:340px;object-fit:cover;" alt="{{ $guide->title }}" decoding="async">
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
