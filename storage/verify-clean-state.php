<?php
/** TEMPORARY E2E helper — prints canonical clean-state assertions. */
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;

$orders = Order::count();
$e2eOrders = Order::where('transaction_id', 'like', 'E2ETRX%')->count();
$carts = Cart::count();
$stock = Product::where('slug', 'java-fern')->first()->variants->first()->stock;

echo "orders(total)={$orders} orders(e2e-marked)={$e2eOrders} carts={$carts} java-fern-stock={$stock}\n";
