<?php

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| E2E test-data helpers
|--------------------------------------------------------------------------
| These replace the ad-hoc storage/*.php scripts. They can only mutate the
| database in local/testing environments, so a production shell can never
| accidentally delete real orders or restore stock.
*/
Artisan::command('e2e:cleanup', function () {
    if (! app()->environment('local', 'testing')) {
        abort(403);
    }

    $orders = Order::query()
        ->where(function ($q) {
            $q->where('transaction_id', 'like', 'E2ETRX%')
                ->orWhere('shipping_address', 'like', 'E2E TEST%');
        })
        ->get();

    foreach ($orders as $order) {
        DB::transaction(function () use ($order) {
            // Stock is reserved when a TrxID is submitted (unpaid + transaction_id)
            // and when a payment is verified (paid). Failed orders were already
            // released by the admin rejection/cancellation path, so restoring them
            // here would inflate stock.
            $stockReserved = $order->payment_status === 'paid'
                || ($order->payment_status === 'unpaid' && $order->transaction_id !== null);

            if ($stockReserved) {
                foreach ($order->items as $item) {
                    if ($item->variant) {
                        $item->variant->increment('stock', $item->quantity);
                    }
                }
            }

            $order->items()->delete();
            $order->delete();
        });

        $this->info("removed order {$order->order_number} ({$order->payment_status})");
    }

    // Remove any carts left by E2E runs of the demo customer.
    $carts = Cart::whereHas('user', fn ($q) => $q->where('email', 'customer@example.com'))->get();
    foreach ($carts as $cart) {
        $cart->delete();
    }

    $this->line('cleared '.$carts->count().' leftover cart row(s)');
    $this->line('remaining E2E orders: '.Order::where('transaction_id', 'like', 'E2ETRX%')->count());
})->purpose('Remove Playwright E2E orders and restore reserved stock (local/testing only)');

Artisan::command('e2e:verify-clean', function () {
    if (! app()->environment('local', 'testing')) {
        abort(403);
    }

    $orders = Order::count();
    $e2eOrders = Order::where('transaction_id', 'like', 'E2ETRX%')->count();
    $carts = Cart::count();
    $product = Product::where('slug', 'java-fern')->first();
    $stock = $product?->variants->first()?->stock;

    $this->line(
        "orders(total)={$orders} orders(e2e-marked)={$e2eOrders} carts={$carts} "
        .'java-fern-stock='.($stock ?? 'n/a')
    );
})->purpose('Print canonical E2E clean-state assertions (local/testing only)');

// Hourly: release reserved stock for orders whose TrxID was never verified
// within the configured TTL (default 24 hours, config: orders.reservation_ttl_hours).
Schedule::command('orders:release-stale-reservations')->hourly();

// Hourly: check queue health and log errors when failed jobs are detected.
Schedule::command('queue:check-health')->hourly();
