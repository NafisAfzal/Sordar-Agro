<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Wishlist;
use App\Models\Cart;

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

        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

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
