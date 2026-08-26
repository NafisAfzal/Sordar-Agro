<?php
/**
 * TEMPORARY E2E cleanup — removes records created by the Playwright run and
 * restores variant stock decremented by simulated payments.
 * Identified by unmistakable E2E markers only. Deleted after Phase 9.
 */
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;

$orders = Order::query()
    ->where(function ($q) {
        $q->where('transaction_id', 'like', 'E2ETRX%')
          ->orWhere('shipping_address', 'like', 'E2E TEST%');
    })
    ->get();

foreach ($orders as $order) {
    DB::transaction(function () use ($order) {
        // Only PAID orders decremented stock at payment time — restoring
        // unpaid orders would inflate stock (unpaid never touched it).
        if ($order->payment_status === 'paid') {
            foreach ($order->items as $item) {
                if ($item->variant) {
                    $item->variant->increment('stock', $item->quantity);
                }
            }
        }
        $order->items()->delete();
        $order->delete();
    });
    echo "removed order {$order->order_number} ({$order->payment_status})\n";
}

// Remove any carts left by E2E runs of the demo customer.
$carts = Cart::whereHas('user', fn ($q) => $q->where('email', 'customer@example.com'))->get();
foreach ($carts as $cart) {
    // Only clear rows pointing at products whose name marks them as test data,
    // or standard demo products added during the run — cart rows are ephemeral
    // by design, so clearing them is safe.
    $cart->delete();
}
echo 'cleared '.$carts->count().' leftover cart row(s)'."\n";
echo 'remaining E2E orders: '.Order::where('transaction_id', 'like', 'E2ETRX%')->count()."\n";
