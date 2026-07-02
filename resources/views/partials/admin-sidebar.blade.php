<h6 class="text-uppercase text-white-50 small mb-3">Administration</h6>
<a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
<a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.index') || request()->routeIs('admin.products.show') ? 'active' : '' }}"><i class="bi bi-clipboard-check"></i> Product Approvals</a>
<a href="{{ route('admin.products.inventory') }}" class="{{ request()->routeIs('admin.products.inventory') ? 'active' : '' }}"><i class="bi bi-boxes"></i> Inventory</a>
<a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><i class="bi bi-truck"></i> Orders &amp; Delivery</a>
<a href="{{ route('admin.sellers.index') }}" class="{{ request()->routeIs('admin.sellers.*') ? 'active' : '' }}"><i class="bi bi-shop"></i> Sellers</a>
<a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Users</a>
<a href="{{ route('admin.care.index') }}" class="{{ request()->routeIs('admin.care.*') ? 'active' : '' }}"><i class="bi bi-journal-text"></i> Care Guides</a>
<a href="{{ route('admin.community.index') }}" class="{{ request()->routeIs('admin.community.*') ? 'active' : '' }}"><i class="bi bi-chat-square-text"></i> Community</a>
<hr class="text-white-50">
<a href="{{ route('home') }}"><i class="bi bi-shop"></i> Storefront</a>
