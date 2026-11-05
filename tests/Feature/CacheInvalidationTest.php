<?php

namespace Tests\Feature;

use App\Models\CareGuide;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrderFor(Product $product, User $customer, int $quantity = 1): Order
    {
        $variant = $product->variants()->firstOrFail();

        $order = Order::create([
            'order_number' => 'SA-'.strtoupper(Str::random(10)),
            'user_id' => $customer->id,
            'total' => (float) $variant->price * $quantity,
            'status' => 'processing',
            'payment_method' => 'bkash',
            'payment_status' => Order::PAYMENT_PAID,
            'transaction_id' => strtoupper(Str::random(12)),
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_size' => $variant->label,
            'price' => $variant->price,
            'quantity' => $quantity,
            'marketplace_share_amount' => $product->profit_share_amount ?? 0,
        ]);

        return $order;
    }

    public function test_sitemap_cache_is_invalidated_when_a_product_is_approved(): void
    {
        $product = Product::factory()->withVariant()->create(['status' => 'pending']);

        $this->get('/sitemap.xml')->assertDontSee(route('products.show', $product));

        $product->update(['status' => 'approved']);

        $this->get('/sitemap.xml')->assertSee(route('products.show', $product));
    }

    public function test_sitemap_cache_is_invalidated_when_a_care_guide_is_published(): void
    {
        $guide = CareGuide::create([
            'title' => 'Cached Draft Guide',
            'slug' => 'cached-draft-guide',
            'content' => 'Draft content.',
            'published_at' => null,
        ]);

        $this->get('/sitemap.xml')->assertDontSee(route('care.show', $guide));

        $guide->update(['published_at' => now()->subMinute()]);

        $this->get('/sitemap.xml')->assertSee(route('care.show', $guide));
    }

    public function test_admin_dashboard_cache_is_invalidated_by_a_new_paid_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->get('/admin?period=all')
            ->assertOk()
            ->assertDontSee('৳150.00');

        Order::create([
            'order_number' => 'SA-CACHEADMIN',
            'user_id' => $customer->id,
            'total' => 150,
            'status' => 'processing',
            'payment_method' => 'bkash',
            'payment_status' => Order::PAYMENT_PAID,
            'transaction_id' => 'TRXCACHEADMIN',
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        $this->actingAs($admin)
            ->get('/admin?period=all')
            ->assertOk()
            ->assertSee('৳150.00');
    }

    public function test_seller_dashboard_cache_is_invalidated_by_a_new_paid_order(): void
    {
        $seller = User::factory()->seller()->create();
        $customer = User::factory()->create();
        $product = Product::factory()
            ->withVariant(stock: 10, price: 100)
            ->create([
                'seller_id' => $seller->id,
                'profit_share_amount' => 10,
                'status' => 'approved',
            ]);

        $this->actingAs($seller)
            ->get('/seller/dashboard')
            ->assertOk()
            ->assertDontSee('৳200.00');

        $this->paidOrderFor($product, $customer, 2);

        $this->actingAs($seller)
            ->get('/seller/dashboard')
            ->assertOk()
            ->assertSee('৳200.00');
    }

    public function test_admin_payment_verification_is_idempotent(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->firstOrFail();

        $order = Order::create([
            'order_number' => 'SA-IDEMPOTENT',
            'user_id' => $customer->id,
            'total' => 100,
            'status' => 'processing',
            'payment_method' => 'bkash',
            'payment_status' => Order::PAYMENT_UNPAID,
            'transaction_id' => 'TRXIDEMPOTENT1',
            'payment_submitted_at' => now(),
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        // Stock was already reserved when the TrxID was submitted.
        $variant->update(['stock' => 4]);

        $this->actingAs($admin)
            ->patch("/admin/orders/{$order->id}/payment/verify")
            ->assertRedirect();

        $order->refresh();
        $verifiedAt = $order->payment_verified_at;

        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertSame($admin->id, (int) $order->payment_verified_by);
        $this->assertSame(4, $variant->fresh()->stock);

        // Replaying the verification must be a no-op: no second stock deduction.
        $this->actingAs($admin)
            ->patch("/admin/orders/{$order->id}/payment/verify")
            ->assertRedirect()
            ->assertSessionHas('error');

        $order->refresh();

        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertSame($admin->id, (int) $order->payment_verified_by);
        $this->assertTrue($verifiedAt->equalTo($order->payment_verified_at));
        $this->assertSame(4, $variant->fresh()->stock);
    }
}
