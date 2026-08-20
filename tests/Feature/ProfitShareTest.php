<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_cannot_submit_a_product_without_a_profit_share_amount(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($seller)->post('/seller/products', [
            'name'        => 'No Profit Share Product',
            'category_id' => $category->id,
            'variants'    => [
                ['size' => 'standard', 'price' => 100, 'stock' => 5],
            ],
        ]);

        $response->assertSessionHasErrors('profit_share_amount');
        $this->assertDatabaseMissing('products', ['name' => 'No Profit Share Product']);
    }

    public function test_admin_can_reject_a_product_with_a_specific_reason_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)->patch("/admin/products/{$product->slug}/reject", [
            'rejection_reason_category' => 'profit_share',
            'admin_feedback' => 'The offered TK amount per unit is too low.',
        ]);

        $response->assertSessionHas('success');
        $product->refresh();

        $this->assertSame('rejected', $product->status);
        $this->assertSame('profit_share', $product->rejection_reason_category);
        $this->assertSame('The offered TK amount per unit is too low.', $product->admin_feedback);
    }

    public function test_seller_sees_the_rejection_reason_category_and_feedback(): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'rejected',
            'rejection_reason_category' => 'profit_share',
            'admin_feedback' => 'The offered TK amount per unit is too low.',
        ]);

        $response = $this->actingAs($seller)->get("/seller/products/{$product->slug}/edit");

        $response->assertOk();
        $response->assertSee('Profit Share Amount');
        $response->assertSee('The offered TK amount per unit is too low.');
    }
}
