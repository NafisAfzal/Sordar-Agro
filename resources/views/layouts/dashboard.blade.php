<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — Sordar Agro</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/favicon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
<nav class="navbar navbar-sa px-3">
    <a class="navbar-brand brand-mark text-white" href="{{ route('home') }}">
        <i class="bi bi-water"></i> SORDAR AGRO
    </a>
    <div class="dropdown">
        <a class="nav-link text-white dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-shop"></i> Storefront</a></li>
            <li><a class="dropdown-item" href="{{ route('password.change') }}"><i class="bi bi-key"></i> Change Password</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Log out</button>
                </form>
            </li>
        </ul>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 sa-sidebar p-3">
            @yield('sidebar')
        </aside>
        <main class="col-md-9 col-lg-10 py-4 px-md-4">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
