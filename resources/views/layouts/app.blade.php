<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sordar Agro — Aquarium Marketplace')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">

<!-- Announcement Bar -->
<div class="announcement-bar text-center">
    <div class="container">
        <span class="fw-medium">Healthy aquarium livestock | Secure local payments via bKash/Nagad</span>
    </div>
</div>

<header class="sticky-header-container shadow-sm">
    <!-- Main Header -->
    <div class="main-header">
        <div class="container d-flex align-items-center justify-content-between">
            <!-- Brand -->
            <a class="brand-mark d-flex align-items-center gap-2 text-decoration-none text-sa" href="{{ route('home') }}">
                <i class="bi bi-water fs-2"></i>
                <span class="fs-4 d-none d-lg-inline">SORDAR AGRO</span>
            </a>

            <!-- Search (Desktop) -->
            <div class="header-search d-none d-md-block flex-grow-1 mx-lg-5 mx-4 position-relative">
                <form action="{{ route('products.index') }}" method="GET" role="search" autocomplete="off">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" id="navSearchInput"
                               placeholder="Search fish, plants, food, equipment..." value="{{ request('q') }}">
                        <button class="btn" type="submit" aria-label="Search">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                <div id="searchSuggestions" class="search-suggestions shadow d-none"></div>
            </div>

            <!-- Actions -->
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <!-- Mobile Search Toggle -->
                <button class="btn d-md-none p-2" type="button" data-bs-toggle="collapse" data-bs-target="#mobileSearch">
                    <i class="bi bi-search fs-5"></i>
                </button>

                @auth
                    @if (auth()->user()->canShop())
                        <a href="{{ route('wishlist.index') }}" class="header-action-btn position-relative" title="Wishlist">
                            <i class="bi bi-heart fs-5"></i>
                            @if (($globalWishlistCount ?? 0) > 0)
                                <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle" style="font-size: 0.6rem;">{{ $globalWishlistCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('cart.index') }}" class="header-action-btn position-relative" title="Cart">
                            <i class="bi bi-cart fs-5"></i>
                            @if (($globalCartCount ?? 0) > 0)
                                <span class="badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle" style="font-size: 0.6rem;">{{ $globalCartCount }}</span>
                            @endif
                        </a>
                    @endif

                    <div class="dropdown">
                        <a href="#" class="header-action-btn dropdown-toggle px-2" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span class="d-none d-lg-inline">{{ Str::limit(auth()->user()->name, 12) }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3">
                            @if (auth()->user()->isAdmin())
                                <li><a class="dropdown-item py-2" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                            @endif
                            @if (auth()->user()->isSeller() || auth()->user()->isAdmin())
                                <li><a class="dropdown-item py-2" href="{{ route('seller.dashboard') }}"><i class="bi bi-shop me-2"></i>Seller Workspace</a></li>
                            @endif
                            @if (auth()->user()->canShop())
                                <li><a class="dropdown-item py-2" href="{{ route('orders.index') }}"><i class="bi bi-bag me-2"></i>My Orders</a></li>
                            @endif
                            <li><a class="dropdown-item py-2" href="{{ route('password.change') }}"><i class="bi bi-key me-2"></i>Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger py-2"><i class="bi bi-box-arrow-right me-2"></i>Log out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-sa d-none d-md-inline-block btn-sm px-4">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-sa d-none d-md-inline-block btn-sm px-4">Register</a>
                    <a href="{{ route('login') }}" class="header-action-btn d-md-none" title="Login">
                        <i class="bi bi-person fs-4"></i>
                    </a>
                @endauth

                <!-- Mobile Menu Toggle -->
                <button class="btn d-lg-none p-2" type="button" id="mobileMenuTrigger" aria-label="Open Menu">
                    <i class="bi bi-list fs-4"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Search (Collapse) -->
    <div class="collapse d-md-none bg-light" id="mobileSearch">
        <div class="container py-3">
            <form action="{{ route('products.index') }}" method="GET">
                <div class="input-group header-search">
                    <input class="form-control" type="search" name="q" placeholder="Search..." value="{{ request('q') }}">
                    <button class="btn btn-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Nav (Desktop) -->
    <nav class="category-nav d-none d-lg-block">
        <div class="container">
            <ul class="nav justify-content-center">
                <li class="nav-item"><a class="nav-link" href="{{ route('products.index') }}">Shop All</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('products.index', ['category' => 'fish']) }}">Fish</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('products.index', ['category' => 'aquatic-plants']) }}">Plants</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('products.index', ['category' => 'fish-food']) }}">Food</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('products.index', ['category' => 'equipment']) }}">Equipment</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('care.index') }}">Care Guides</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('community.index') }}">Community</a></li>
            </ul>
        </div>
    </nav>
</header>

<!-- Mobile Menu Drawer -->
<div class="mobile-drawer-overlay" id="mobileDrawerOverlay"></div>
<div class="mobile-drawer" id="mobileDrawer">
    <div class="mobile-drawer-header">
        <div class="brand-mark d-flex align-items-center gap-2 text-sa">
            <i class="bi bi-water fs-3"></i>
            <span class="fs-5">SORDAR AGRO</span>
        </div>
        <button class="btn-close" type="button" id="mobileDrawerClose" aria-label="Close"></button>
    </div>
    <div class="mobile-drawer-body">
        <div class="px-4 py-2 small fw-bold text-muted text-uppercase letter-spacing-1">Categories</div>
        <a href="{{ route('products.index') }}" class="mobile-nav-link"><i class="bi bi-shop"></i>Shop All</a>
        <a href="{{ route('products.index', ['category' => 'fish']) }}" class="mobile-nav-link"><i class="bi bi-water"></i>Fish</a>
        <a href="{{ route('products.index', ['category' => 'aquatic-plants']) }}" class="mobile-nav-link"><i class="bi bi-tree"></i>Plants</a>
        <a href="{{ route('products.index', ['category' => 'fish-food']) }}" class="mobile-nav-link"><i class="bi bi-egg"></i>Food</a>
        <a href="{{ route('products.index', ['category' => 'equipment']) }}" class="mobile-nav-link"><i class="bi bi-gear"></i>Equipment</a>
        <div class="dropdown-divider mx-4"></div>
        <a href="{{ route('care.index') }}" class="mobile-nav-link"><i class="bi bi-book"></i>Care Guides</a>
        <a href="{{ route('community.index') }}" class="mobile-nav-link"><i class="bi bi-people"></i>Community</a>

        @auth
            <div class="dropdown-divider mx-4 mt-3"></div>
            <div class="px-4 py-2 small fw-bold text-muted text-uppercase letter-spacing-1">My Account</div>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="mobile-nav-link"><i class="bi bi-speedometer2"></i>Admin Dashboard</a>
            @endif
            @if (auth()->user()->isSeller() || auth()->user()->isAdmin())
                <a href="{{ route('seller.dashboard') }}" class="mobile-nav-link"><i class="bi bi-shop"></i>Seller Workspace</a>
            @endif
            @if (auth()->user()->canShop())
                <a href="{{ route('orders.index') }}" class="mobile-nav-link"><i class="bi bi-bag"></i>My Orders</a>
                <a href="{{ route('wishlist.index') }}" class="mobile-nav-link"><i class="bi bi-heart"></i>Wishlist</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button class="mobile-nav-link w-100 border-0 bg-transparent text-danger"><i class="bi bi-box-arrow-right"></i>Log out</button>
            </form>
        @else
            <div class="p-4 d-grid gap-2">
                <a href="{{ route('login') }}" class="btn btn-outline-sa">Login</a>
                <a href="{{ route('register') }}" class="btn btn-sa">Register</a>
            </div>
        @endauth
    </div>
</div>

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

<!-- Mobile Drawer JS -->
<script>
(function () {
    const trigger = document.getElementById('mobileMenuTrigger');
    const drawer = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileDrawerOverlay');
    const closeBtn = document.getElementById('mobileDrawerClose');

    if (!trigger || !drawer || !overlay || !closeBtn) return;

    function openDrawer() {
        drawer.classList.add('active');
        overlay.classList.add('active');
        trigger.setAttribute('aria-expanded', 'true');
        drawer.setAttribute('aria-hidden', 'false');
    }

    function closeDrawer() {
        drawer.classList.remove('active');
        overlay.classList.remove('active');
        trigger.setAttribute('aria-expanded', 'false');
        drawer.setAttribute('aria-hidden', 'true');
        trigger.focus();
    }

    trigger.addEventListener('click', openDrawer);
    closeBtn.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && drawer.classList.contains('active')) {
            closeDrawer();
        }
    });
})();
</script>

@stack('scripts')
</body>
</html>