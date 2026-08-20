<?php

use Illuminate\Support\Facades\Route;

// ---- Auth controllers --------------------------------------------------
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;

// ---- Storefront controllers -------------------------------------------
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CareGuideController;
use App\Http\Controllers\CommunityController;

// ---- Seller controllers -----------------------------------------------
use App\Http\Controllers\Seller\DashboardController as SellerDashboard;
use App\Http\Controllers\Seller\ProductController as SellerProductController;

// ---- Admin controllers ------------------------------------------------
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\SellerController as AdminSellerController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\CareGuideController as AdminCareGuideController;
use App\Http\Controllers\Admin\CommunityController as AdminCommunityController;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/search/suggestions', [ProductController::class, 'suggestions'])->name('products.suggestions');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/care-guides', [CareGuideController::class, 'index'])->name('care.index');
Route::get('/care-guides/{careGuide}', [CareGuideController::class, 'show'])->name('care.show');

Route::get('/community', [CommunityController::class, 'index'])->name('community.index');

/*
|--------------------------------------------------------------------------
| Guest-only auth routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [PasswordController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'resetPassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated routes (any role)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Change password — reachable even with a forced temporary password.
    Route::get('/change-password', [PasswordController::class, 'showChange'])->name('password.change');
    Route::post('/change-password', [PasswordController::class, 'updateChange'])->name('password.change.update');

    // Email verification
    Route::get('/email/verify', [App\Http\Controllers\Auth\VerificationController::class, 'notice'])
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])
        ->middleware(['signed'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');

    /*
    | Shopping routes — customers AND sellers (sellers keep buyer powers).
    | password.changed forces temp-password sellers to update first.
    */
    Route::middleware(['role:customer,seller,admin', 'password.changed'])->group(function () {
        // Cart
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/{variant}', [CartController::class, 'add'])->name('cart.add');
        Route::patch('/cart/{cart}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{cart}', [CartController::class, 'remove'])->name('cart.remove');

        // Wishlist
        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist/{product}', [WishlistController::class, 'add'])->name('wishlist.add');
        Route::delete('/wishlist/{wishlist}', [WishlistController::class, 'remove'])->name('wishlist.remove');

        // Checkout + simulated payment — email must be verified before a
        // real order can be placed or paid for. Browsing/cart/wishlist stay
        // open to unverified accounts by design.
        Route::middleware('verified')->group(function () {
            Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
            Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');
            Route::get('/payment/{order}', [PaymentController::class, 'show'])->name('payment.show');
            Route::post('/payment/{order}', [PaymentController::class, 'process'])->name('payment.process');
        });

        // Orders + tracking
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

        // Community contribution
        Route::get('/community/submit', [CommunityController::class, 'create'])->name('community.create');
        Route::post('/community/submit', [CommunityController::class, 'store'])->name('community.store');
    });
});

/*
|--------------------------------------------------------------------------
| Seller workspace
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:seller,admin', 'password.changed'])
    ->prefix('seller')->name('seller.')->group(function () {
        Route::get('/dashboard', [SellerDashboard::class, 'index'])->name('dashboard');
        Route::get('/products', [SellerProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [SellerProductController::class, 'create'])->name('products.create');
        Route::post('/products', [SellerProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [SellerProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [SellerProductController::class, 'destroy'])->name('products.destroy');
    });

/*
|--------------------------------------------------------------------------
| Admin dashboard
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');

        // Users + sellers
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/toggle', [AdminUserController::class, 'toggle'])->name('users.toggle');

        Route::get('/sellers', [AdminSellerController::class, 'index'])->name('sellers.index');
        Route::get('/sellers/create', [AdminSellerController::class, 'create'])->name('sellers.create');
        Route::post('/sellers', [AdminSellerController::class, 'store'])->name('sellers.store');

        // Product approval workflow
        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/products/inventory', [AdminInventoryController::class, 'index'])->name('products.inventory');
        Route::patch('/variants/{variant}/adjust', [AdminInventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('products.show');
        Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
        Route::patch('/products/{product}/approve', [AdminProductController::class, 'approve'])->name('products.approve');
        Route::patch('/products/{product}/reject', [AdminProductController::class, 'reject'])->name('products.reject');

        // Orders + delivery
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');

        // Care guides (CRUD)
        Route::get('/care-guides', [AdminCareGuideController::class, 'index'])->name('care.index');
        Route::get('/care-guides/create', [AdminCareGuideController::class, 'create'])->name('care.create');
        Route::post('/care-guides', [AdminCareGuideController::class, 'store'])->name('care.store');
        Route::get('/care-guides/{careGuide}/edit', [AdminCareGuideController::class, 'edit'])->name('care.edit');
        Route::put('/care-guides/{careGuide}', [AdminCareGuideController::class, 'update'])->name('care.update');
        Route::delete('/care-guides/{careGuide}', [AdminCareGuideController::class, 'destroy'])->name('care.destroy');

        // Community submissions review
        Route::get('/community', [AdminCommunityController::class, 'index'])->name('community.index');
        Route::patch('/community/{submission}/approve', [AdminCommunityController::class, 'approve'])->name('community.approve');
        Route::patch('/community/{submission}/reject', [AdminCommunityController::class, 'reject'])->name('community.reject');
    });