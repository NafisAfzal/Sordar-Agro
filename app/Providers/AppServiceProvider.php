<?php

namespace App\Providers;

use App\Models\CareGuide;
use App\Models\Cart;
use App\Models\CommunitySubmission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wishlist;
use App\Observers\CacheInvalidationObserver;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Bootstrap 5 friendly pagination markup.
        Paginator::useBootstrapFive();

        // Object-level authorization for seller/admin product and order actions.
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);

        // Invalidate versioned read-model caches when source data changes.
        foreach ([
            Product::class,
            ProductVariant::class,
            Order::class,
            OrderItem::class,
            CareGuide::class,
            CommunitySubmission::class,
            User::class,
        ] as $model) {
            $model::observe(CacheInvalidationObserver::class);
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Mail::extend('brevo', function () {
            $factory = new BrevoTransportFactory;

            return $factory->create(
                new Dsn('brevo+api', 'default', config('services.brevo.key'))
            );
        });

        // Abuse protection — named rate limiters consumed by throttle middleware.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-email', function (Request $request) {
            $identity = Str::lower($request->input('email')).'|'.$request->ip();

            return [
                Limit::perMinute(3)->by($identity),   // per address + IP
                Limit::perMinute(10)->by($request->ip()), // per IP
            ];
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });

        RateLimiter::for('suggestions', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('community', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('payment', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip());
        });

        // Share lightweight cart/wishlist counts with every view so the
        // navbar badges stay accurate without each controller passing them.
        View::composer('*', function ($view) {
            $cartCount = 0;
            $wishlistCount = 0;

            if (auth()->check()) {
                $cartCount = Cart::where('user_id', auth()->id())->sum('quantity');
                $wishlistCount = Wishlist::where('user_id', auth()->id())->count();
            }

            $view->with('globalCartCount', $cartCount)
                ->with('globalWishlistCount', $wishlistCount);
        });
    }
}
