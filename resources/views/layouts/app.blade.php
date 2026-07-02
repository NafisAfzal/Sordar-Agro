<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sordar Agro — Aquarium Marketplace')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/favicon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-sa shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand brand-mark" href="{{ route('home') }}">
            <i class="bi bi-water"></i> SORDAR AGRO
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="{{ route('products.index') }}">Shop</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('care.index') }}">Care Guides</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('community.index') }}">Community</a></li>
            </ul>

            <form class="d-flex me-3" method="GET" action="{{ route('products.index') }}">
                <input class="form-control form-control-sm" type="search" name="q"
                       placeholder="Search fish, plants…" value="{{ request('q') }}">
            </form>

            <ul class="navbar-nav">
                @auth
                    @if (auth()->user()->canShop())
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="{{ route('wishlist.index') }}">
                                <i class="bi bi-heart"></i>
                                @if (($globalWishlistCount ?? 0) > 0)
                                    <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle">{{ $globalWishlistCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="{{ route('cart.index') }}">
                                <i class="bi bi-cart"></i>
                                @if (($globalCartCount ?? 0) > 0)
                                    <span class="badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle">{{ $globalCartCount }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> {{ Str::limit(auth()->user()->name, 12) }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if (auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Admin Dashboard</a></li>
                            @endif
                            @if (auth()->user()->isSeller())
                                <li><a class="dropdown-item" href="{{ route('seller.dashboard') }}"><i class="bi bi-shop"></i> Seller Workspace</a></li>
                            @endif
                            @if (auth()->user()->canShop())
                                <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-bag"></i> My Orders</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('password.change') }}"><i class="bi bi-key"></i> Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Log out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                    <li class="nav-item"><a class="btn btn-light btn-sm ms-2 mt-1 text-sa fw-semibold" href="{{ route('register') }}">Register</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    @include('partials.flash')
    @yield('content')
</main>

<footer class="bg-dark text-light py-4 mt-5">
    <div class="container small">
        <div class="row">
            <div class="col-md-6">
                <h6 class="brand-mark"><i class="bi bi-water"></i> SORDAR AGRO</h6>
                <p class="text-secondary mb-0">Your online aquarium marketplace — fish (sold in pairs), plants, foods &amp; equipment.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-1">CSE412 Software Engineering — Group 05</p>
                <p class="text-secondary mb-0">&copy; {{ date('Y') }} Sordar Agro. For academic use.</p>
            </div>
        </div>
    </div>
</footer>

@include('partials.whatsapp')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
