<h6 class="text-uppercase text-white-50 small mb-3">Seller workspace</h6>
<a href="{{ route('seller.dashboard') }}" class="{{ request()->routeIs('seller.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
<a href="{{ route('seller.products.index') }}" class="{{ request()->routeIs('seller.products.index') ? 'active' : '' }}"><i class="bi bi-box-seam"></i> My Products</a>
<a href="{{ route('seller.products.create') }}" class="{{ request()->routeIs('seller.products.create') ? 'active' : '' }}"><i class="bi bi-plus-square"></i> Add Product</a>
<hr class="text-white-50">
<a href="{{ route('home') }}"><i class="bi bi-shop"></i> Go to storefront</a>
<a href="{{ route('orders.index') }}"><i class="bi bi-bag"></i> My Orders (as buyer)</a>
