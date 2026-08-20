<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_an_approved_product_submitted_by_another_seller(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id'   => $seller->id,
            'status'      => 'approved',
            'category_id' => $category->id,
            'name'        => 'Original Name',
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'ORIG1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($admin)->put("/admin/products/{$product->slug}", [
            'name'        => 'Edited By Admin',
            'category_id' => $category->id,
            'description' => 'Updated description',
            'variants'    => [
                ['size' => 'standard', 'price' => 150, 'stock' => 8],
            ],
        ]);

        $product->refresh();

        $this->assertSame('Edited By Admin', $product->name);
        // Admin is the approver — an admin edit must not bounce an
        // already-approved, other-seller's product back into review.
        $this->assertSame('approved', $product->status);
        $this->assertSame('150.00', $product->variants()->first()->price);
    }

    public function test_admin_can_delete_a_product_with_no_order_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();

        $this->actingAs($admin)->delete("/admin/products/{$product->slug}");

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_admin_cannot_delete_a_product_with_order_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $product = Product::factory()->withVariant(stock: 5, price: 100)->create();
        $variant = $product->variants()->first();

        $order = Order::create([
            'order_number'     => 'SA-TEST0099',
            'user_id'          => $customer->id,
            'total'            => 100,
            'status'           => 'processing',
            'payment_method'   => 'bkash',
            'payment_status'   => 'paid',
            'shipping_name'    => 'Buyer',
            'shipping_phone'   => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'product_variant_id' => $variant->id,
            'product_name'       => $product->name,
            'variant_size'       => $variant->size,
            'price'              => $variant->price,
            'quantity'           => 1,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/products/{$product->slug}");

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $response->assertSessionHas('error');
    }
}
