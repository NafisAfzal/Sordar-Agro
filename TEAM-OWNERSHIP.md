# Sordar Agro — File Ownership Map (Group 05)

This maps **every file to the team member responsible for it.** Use it so each person
commits only their own files on their own branch. The shared skeleton (framework
folders, config, composer.json) goes to `main` first — see GIT-WORKFLOW.md.

Members: **Nafis** (lead), **Mostahid**, **Junaid**, **Sayed**.

---

## NAFIS — lead: architecture, auth core, catalogue, checkout, payment, database
**Controllers**
- app/Http/Controllers/Controller.php (base)
- app/Http/Controllers/Auth/LoginController.php
- app/Http/Controllers/ProductController.php  (catalogue search + filters)
- app/Http/Controllers/CheckoutController.php
- app/Http/Controllers/PaymentController.php   (simulated bKash/Nagad — reviewed/fixed)
- app/Http/Controllers/Admin/InventoryController.php

**Middleware / Services**
- app/Http/Middleware/RoleMiddleware.php
- app/Http/Middleware/EnsurePasswordChanged.php
- app/Services/InventoryService.php

**Models** (core domain schema)
- User.php, Product.php, ProductVariant.php, ProductImage.php, Category.php, Order.php, OrderItem.php

**Views**
- resources/views/storefront/index.blade.php
- resources/views/checkout/show.blade.php, checkout/payment.blade.php
- resources/views/admin/products/inventory.blade.php
- resources/views/auth/login.blade.php
- resources/views/layouts/app.blade.php, dashboard.blade.php, guest.blade.php
- resources/views/partials/flash.blade.php, product-card.blade.php, whatsapp.blade.php

**Shared skeleton (push to `main`)**
- bootstrap/app.php, routes/web.php, routes/console.php
- app/Providers/AppServiceProvider.php
- config/*, composer.json, .env.example, .gitignore, .editorconfig
- public/index.php, public/.htaccess, public/css/app.css, public/img/*
- ALL database/migrations/*, database/seeders/*, database/factories/*
- tests/*  (test suite + fixtures)

---

## MOSTAHID — registration, passwords, product detail, cart, care guides
**Controllers**
- app/Http/Controllers/Auth/RegisterController.php
- app/Http/Controllers/Auth/PasswordController.php
- app/Http/Controllers/CartController.php
- app/Http/Controllers/CareGuideController.php
- app/Http/Controllers/Admin/CareGuideController.php

**Models**
- Cart.php, CareGuide.php

**Views**
- resources/views/auth/register.blade.php, forgot-password.blade.php, reset-password.blade.php, change-password.blade.php
- resources/views/storefront/show.blade.php   (product detail + size-variant selection)
- resources/views/cart/index.blade.php
- resources/views/care/index.blade.php, care/show.blade.php
- resources/views/admin/care/index.blade.php, create.blade.php, edit.blade.php, _form.blade.php

---

## JUNAID — homepage, admin dashboard, seller onboarding, product approvals, users
**Controllers**
- app/Http/Controllers/HomeController.php
- app/Http/Controllers/Admin/DashboardController.php
- app/Http/Controllers/Admin/SellerController.php
- app/Http/Controllers/Admin/ProductController.php   (approval workflow)
- app/Http/Controllers/Admin/UserController.php

**Views**
- resources/views/storefront/home.blade.php
- resources/views/admin/dashboard.blade.php
- resources/views/admin/sellers/index.blade.php, create.blade.php
- resources/views/admin/products/index.blade.php, show.blade.php
- resources/views/admin/users/index.blade.php
- resources/views/partials/admin-sidebar.blade.php

---

## SAYED — seller workspace, product upload, wishlist, orders & tracking, community
**Controllers**
- app/Http/Controllers/Seller/DashboardController.php
- app/Http/Controllers/Seller/ProductController.php
- app/Http/Controllers/WishlistController.php
- app/Http/Controllers/OrderController.php
- app/Http/Controllers/Admin/OrderController.php
- app/Http/Controllers/CommunityController.php
- app/Http/Controllers/Admin/CommunityController.php

**Models**
- Wishlist.php, CommunitySubmission.php

**Views**
- resources/views/seller/dashboard.blade.php
- resources/views/seller/products/index.blade.php, create.blade.php, edit.blade.php, _form.blade.php
- resources/views/partials/seller-sidebar.blade.php
- resources/views/wishlist/index.blade.php
- resources/views/orders/index.blade.php, show.blade.php
- resources/views/admin/orders/index.blade.php, show.blade.php
- resources/views/community/index.blade.php, create.blade.php
- resources/views/admin/community/index.blade.php

---

### Rough split (controllers + views + models)
- **Nafis:** ~26 files + the full skeleton/tests (heaviest, as lead)
- **Mostahid:** ~15 files
- **Junaid:** ~12 files
- **Sayed:** ~19 files
