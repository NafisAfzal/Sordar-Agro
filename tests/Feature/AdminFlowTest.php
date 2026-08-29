<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertOk();
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get('/admin');
        $response->assertForbidden();
    }

    public function test_seller_cannot_access_admin_dashboard(): void
    {
        $seller = User::factory()->seller()->create();

        $response = $this->actingAs($seller)->get('/admin');
        $response->assertForbidden();
    }

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertOk();
    }

    public function test_customer_cannot_access_admin_user_list(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get('/admin/users');
        $response->assertForbidden();
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/toggle");
        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/toggle");
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->patch("/admin/users/{$admin->id}/toggle");

        // Admin should not be able to deactivate themselves (safety check)
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_can_view_seller_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/sellers');
        $response->assertOk();
    }

    public function test_admin_can_view_pending_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        Product::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'pending',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->get('/admin/products');
        $response->assertOk();
    }

    public function test_admin_can_approve_pending_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'pending',
            'category_id' => $category->id,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($admin)->patch("/admin/products/{$product->slug}/approve");
        $this->assertSame('approved', $product->fresh()->status);
    }

    public function test_admin_can_reject_pending_product_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'pending',
            'category_id' => $category->id,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($admin)->patch("/admin/products/{$product->slug}/reject", [
            'rejection_reason_category' => 'price',
            'admin_feedback' => 'Price is too high for this market.',
        ]);

        $product->refresh();
        $this->assertSame('rejected', $product->status);
        $this->assertSame('price', $product->rejection_reason_category);
        $this->assertSame('Price is too high for this market.', $product->admin_feedback);
    }

    public function test_admin_can_edit_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'pending',
            'category_id' => $category->id,
            'name' => 'Original Name',
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($admin)->put("/admin/products/{$product->slug}", [
            'name' => 'Admin Updated Name',
            'category_id' => $category->id,
            'description' => 'Updated by admin',
            'variants' => [
                ['size' => 'standard', 'price' => 150, 'stock' => 8],
            ],
            'profit_share_amount' => 50,
        ]);

        $product->refresh();
        $this->assertSame('Admin Updated Name', $product->name);
    }

    public function test_admin_can_increase_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'standard',
            'price' => 100,
            'stock' => 5,
            'sku' => 'SKU-INV1',
        ]);

        $this->actingAs($admin)->patch("/admin/variants/{$variant->id}/adjust", [
            'delta' => 10,
        ]);

        $this->assertSame(15, $variant->fresh()->stock);
    }

    public function test_admin_can_decrease_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'standard',
            'price' => 100,
            'stock' => 10,
            'sku' => 'SKU-INV2',
        ]);

        $this->actingAs($admin)->patch("/admin/variants/{$variant->id}/adjust", [
            'delta' => -3,
        ]);

        $this->assertSame(7, $variant->fresh()->stock);
    }

    public function test_inventory_adjustment_cannot_go_below_zero(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'standard',
            'price' => 100,
            'stock' => 3,
            'sku' => 'SKU-INV3',
        ]);

        $this->actingAs($admin)->patch("/admin/variants/{$variant->id}/adjust", [
            'delta' => -10,
        ]);

        $this->assertSame(0, $variant->fresh()->stock);
    }

    public function test_customer_cannot_adjust_inventory(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'standard',
            'price' => 100,
            'stock' => 5,
            'sku' => 'SKU-INV4',
        ]);

        $this->actingAs($customer)->patch("/admin/variants/{$variant->id}/adjust", [
            'delta' => 10,
        ])->assertForbidden();

        $this->assertSame(5, $variant->fresh()->stock);
    }

    public function test_admin_can_view_order_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/orders');
        $response->assertOk();
    }

    public function test_admin_can_update_order_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();

        $order = Order::create([
            'order_number' => 'SA-ADM001',
            'user_id' => $customer->id,
            'total' => 200,
            'status' => 'processing',
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
            'transaction_id' => 'TRXADM001',
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        $this->actingAs($admin)->patch("/admin/orders/{$order->id}", [
            'status' => 'shipped',
            'courier' => 'steadfast',
        ]);

        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_admin_cannot_set_invalid_order_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();

        $order = Order::create([
            'order_number' => 'SA-ADM002',
            'user_id' => $customer->id,
            'total' => 200,
            'status' => 'processing',
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
            'transaction_id' => 'TRXADM002',
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/orders/{$order->id}", [
            'status' => 'invalid',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/orders')->assertForbidden();
        $this->actingAs($customer)->get('/admin/products')->assertForbidden();
        $this->actingAs($customer)->get('/admin/users')->assertForbidden();
        $this->actingAs($customer)->get('/admin/sellers')->assertForbidden();
    }

    public function test_seller_cannot_access_admin_orders(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->get('/admin/orders')->assertForbidden();
    }
}
