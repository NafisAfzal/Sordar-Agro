<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitShareSalesTest extends TestCase
{
    use RefreshDatabase;

    private function placeAndPayOrder(User $customer, $variant, int $quantity, string $transactionId): Order
    {
        $this->actingAs($customer)->post("/cart/{$variant->id}", ['quantity' => $quantity]);
        $this->actingAs($customer)->post('/checkout', [
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => '123 Test Road, Dhaka',
            'payment_method' => 'bkash',
        ]);

        // orderByDesc('id') rather than latest(): two orders placed within the
        // same second would tie on created_at and latest() could return the
        // wrong (already-paid) one when this helper is called back-to-back.
        $order = Order::where('user_id', $customer->id)->orderByDesc('id')->firstOrFail();
        $this->actingAs($customer)->post("/payment/{$order->id}", ['transaction_id' => $transactionId]);

        return $order->fresh();
    }

    public function test_marketplace_share_amount_is_snapshotted_on_order_item_at_purchase_time(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['profit_share_amount' => 75]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SNAP1', 'price' => 200, 'stock' => 10]);
        $variant = $product->variants()->first();

        $order = $this->placeAndPayOrder($customer, $variant, 2, 'TRXSNAP001');

        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('75.00', (string) $item->marketplace_share_amount);
        $this->assertSame(150.0, $item->marketplaceShareTotal()); // 75 * 2

        // The seller changes their share amount afterward — the already-placed
        // order must keep the amount that was true at the time of purchase.
        $product->update(['profit_share_amount' => 999]);
        $item->refresh();

        $this->assertSame('75.00', (string) $item->marketplace_share_amount);
    }

    public function test_seller_dashboard_shows_correct_units_sold_and_share_totals(): void
    {
        $seller = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id, 'profit_share_amount' => 50]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'DASH1', 'price' => 300, 'stock' => 20]);
        $variant = $product->variants()->first();

        $this->placeAndPayOrder($customer, $variant, 3, 'TRXDASH001'); // 3 * 50 = 150

        $response = $this->actingAs($seller)->get('/seller/products');

        $response->assertOk();
        $response->assertSee('3 sold');
        $response->assertSee('৳150.00');
    }

    public function test_admin_dashboard_shows_correct_totals_across_multiple_orders_and_sellers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();

        $sellerA = User::factory()->seller()->create(['name' => 'Seller Alpha']);
        $productA = Product::factory()->create(['seller_id' => $sellerA->id, 'name' => 'Alpha Betta', 'profit_share_amount' => 40]);
        $productA->variants()->create(['size' => 'standard', 'sku' => 'ADM-A', 'price' => 200, 'stock' => 20]);

        $sellerB = User::factory()->seller()->create(['name' => 'Seller Beta']);
        $productB = Product::factory()->create(['seller_id' => $sellerB->id, 'name' => 'Beta Goldfish', 'profit_share_amount' => 60]);
        $productB->variants()->create(['size' => 'standard', 'sku' => 'ADM-B', 'price' => 250, 'stock' => 20]);

        $this->placeAndPayOrder($customer, $productA->variants()->first(), 2, 'TRXADMA001'); // 2 * 40 = 80
        $this->placeAndPayOrder($customer, $productB->variants()->first(), 4, 'TRXADMB001'); // 4 * 60 = 240

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('৳320.00'); // total marketplace share stat: 80 + 240
        $response->assertSee('Seller Alpha');
        $response->assertSee('Alpha Betta');
        $response->assertSee('Seller Beta');
        $response->assertSee('Beta Goldfish');
    }
}
