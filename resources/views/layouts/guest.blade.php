<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sordar Agro')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/favicon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<div class="container" style="max-width: 460px;">
    <div class="text-center my-4">
        <a href="{{ route('home') }}" class="text-decoration-none text-sa brand-mark fs-3">
            <i class="bi bi-water"></i> SORDAR AGRO
        </a>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            @include('partials.flash')
            @yield('content')
        </div>
    </div>
    <p class="text-center text-muted small mt-3">Aquarium Marketplace</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
