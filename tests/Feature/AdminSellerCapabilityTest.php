<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSellerCapabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_submitted_product_is_auto_approved(): void
    {
        // Admin is the approver, so a self-submitted product must skip the
        // pending-review queue entirely instead of requiring self-approval.
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $this->actingAs($admin)->post('/seller/products', [
            'name'        => 'Admin Test Betta',
            'category_id' => $category->id,
            'description' => 'Added directly by an administrator.',
            'is_fish'     => 1,
            'profit_share_amount' => 50,
            'variants'    => [
                ['size' => 'standard', 'price' => 300, 'stock' => 10],
            ],
        ]);

        $product = Product::where('name', 'Admin Test Betta')->firstOrFail();

        $this->assertSame('approved', $product->status);
    }

    public function test_admin_can_add_product_to_cart_and_view_it(): void
    {
        // canShop() now includes admin, and the shopping route group
        // (role:customer,seller,admin) must actually let them through —
        // not just show buyer UI that 403s on click.
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->withVariant(stock: 5, price: 250)->create();
        $variant = $product->variants()->first();

        $this->actingAs($admin)->post("/cart/{$variant->id}", ['quantity' => 1]);

        $response = $this->actingAs($admin)->get('/cart');

        $response->assertStatus(200);
        $response->assertSee($product->name);
    }
}
