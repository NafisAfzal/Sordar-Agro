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
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-sa shadow-sm sticky-top py-3">
    <div class="container">
        <a class="navbar-brand brand-mark d-flex align-items-center gap-2 me-4" href="{{ route('home') }}">
            <i class="bi bi-water fs-3"></i>
            <span>SORDAR AGRO</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto gap-lg-2">
                <li class="nav-item"><a class="nav-link px-3" href="{{ route('products.index') }}">Shop</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="{{ route('care.index') }}">Care Guides</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="{{ route('community.index') }}">Community</a></li>
            </ul>

            <!-- ========== NEW SEARCH FORM WITH SUGGESTIONS ========== -->
            <form class="d-flex me-3 my-2 my-lg-0 position-relative" method="GET"
                  action="{{ route('products.index') }}" role="search" autocomplete="off">
                <div class="input-group input-group-sm nav-search">
                    <input class="form-control" type="search" name="q" id="navSearchInput"
                           placeholder="Search fish, plants…" aria-label="Search" value="{{ request('q') }}">
                    <button class="btn btn-light" type="submit" title="Search" aria-label="Search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <div id="searchSuggestions" class="search-suggestions shadow d-none"></div>
            </form>
            <!-- ====================================================== -->

            <ul class="navbar-nav align-items-lg-center gap-lg-1">
                @auth
                    @if (auth()->user()->canShop())
                        <li class="nav-item">
                            <a class="nav-link position-relative px-3" href="{{ route('wishlist.index') }}" title="Wishlist">
                                <i class="bi bi-heart fs-5"></i>
                                @if (($globalWishlistCount ?? 0) > 0)
                                    <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle">{{ $globalWishlistCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link position-relative px-3" href="{{ route('cart.index') }}" title="Cart">
                                <i class="bi bi-cart fs-5"></i>
                                @if (($globalCartCount ?? 0) > 0)
                                    <span class="badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle">{{ $globalCartCount }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle px-3 d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span>{{ Str::limit(auth()->user()->name, 12) }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            @if (auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                            @endif
                            @if (auth()->user()->isSeller())
                                <li><a class="dropdown-item" href="{{ route('seller.dashboard') }}"><i class="bi bi-shop me-2"></i>Seller Workspace</a></li>
                            @endif
                            @if (auth()->user()->canShop())
                                <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-bag me-2"></i>My Orders</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('password.change') }}"><i class="bi bi-key me-2"></i>Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Log out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link px-3" href="{{ route('login') }}">Login</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-light btn-sm px-3 fw-semibold text-sa" href="{{ route('register') }}">Register</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 flex-grow-1">
    @include('partials.flash')
    @yield('content')
</main>

<footer class="bg-dark text-light py-4 mt-5">
    <div class="container text-center">
        <h5 class="brand-mark mb-2 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-water"></i>
            <span>SORDAR AGRO</span>
        </h5>
        <p class="text-secondary mb-3">Your online aquarium marketplace</p>
        <p class="text-secondary small mb-0">&copy; {{ date('Y') }} Sordar Agro. All rights reserved.</p>
    </div>
</footer>

@include('partials.whatsapp')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ========== LIVE SEARCH SUGGESTIONS ========== -->
<script>
(function () {
    const input = document.getElementById('navSearchInput');
    const box   = document.getElementById('searchSuggestions');
    if (!input || !box) return;

    const endpoint = "{{ route('products.suggestions') }}";
    let timer = null, controller = null;

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    function hide() { box.classList.add('d-none'); box.innerHTML = ''; }

    function render(items) {
        if (!items.length) {
            box.innerHTML = '<div class="search-empty">No matching products</div>';
        } else {
            box.innerHTML = items.map(i => `
                <a class="search-item" href="${esc(i.url)}">
                    <span class="search-item-name">${esc(i.name)}</span>
                    <span class="search-item-meta">${esc(i.category)} · from ৳${esc(i.price)}</span>
                </a>`).join('');
        }
        box.classList.remove('d-none');
    }

    input.addEventListener('input', function () {
        const term = input.value.trim();
        clearTimeout(timer);
        if (term.length < 2) { hide(); return; }

        timer = setTimeout(() => {
            if (controller) controller.abort();
            controller = new AbortController();
            fetch(`${endpoint}?q=${encodeURIComponent(term)}`, { signal: controller.signal })
                .then(r => r.ok ? r.json() : [])
                .then(render)
                .catch(() => {});
        }, 250);
    });

    input.addEventListener('keydown', e => { if (e.key === 'Escape') hide(); });
    document.addEventListener('click', e => {
        if (!box.contains(e.target) && e.target !== input) hide();
    });
})();
</script>
<!-- ============================================= -->

@stack('scripts')
</body>
</html>