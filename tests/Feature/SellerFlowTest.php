<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_access_dashboard(): void
    {
        $seller = User::factory()->seller()->create();

        $response = $this->actingAs($seller)->get('/seller/dashboard');
        $response->assertOk();
    }

    public function test_seller_can_access_product_list(): void
    {
        $seller = User::factory()->seller()->create();

        $response = $this->actingAs($seller)->get('/seller/products');
        $response->assertOk();
    }

    public function test_seller_can_access_product_create_form(): void
    {
        $seller = User::factory()->seller()->create();

        $response = $this->actingAs($seller)->get('/seller/products/create');
        $response->assertOk();
    }

    public function test_customer_cannot_access_seller_product_create(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get('/seller/products/create');
        $response->assertForbidden();
    }

    public function test_customer_cannot_access_seller_dashboard(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get('/seller/dashboard');
        $response->assertForbidden();
    }

    public function test_seller_with_must_change_password_is_blocked_from_seller_routes(): void
    {
        $seller = User::factory()->seller()->create([
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($seller)->get('/seller/dashboard');
        $response->assertRedirect(route('password.change'));
    }

    public function test_seller_with_must_change_password_can_still_access_change_password(): void
    {
        $seller = User::factory()->seller()->create([
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($seller)->get('/change-password');
        $response->assertOk();
    }

    public function test_seller_can_update_own_pending_product(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'pending',
            'category_id' => $category->id,
            'name' => 'Original Name',
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($seller)->put("/seller/products/{$product->slug}", [
            'name' => 'Updated Name',
            'category_id' => $category->id,
            'description' => 'Updated',
            'variants' => [
                ['size' => 'standard', 'price' => 150, 'stock' => 8],
            ],
            'profit_share_amount' => 50,
        ]);

        $product->refresh();
        $this->assertSame('Updated Name', $product->name);
        $this->assertSame('pending', $product->status);
    }

    public function test_seller_cannot_update_approved_product(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'approved',
            'category_id' => $category->id,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $response = $this->actingAs($seller)->put("/seller/products/{$product->slug}", [
            'name' => 'Attempted Update',
            'category_id' => $category->id,
            'description' => 'Updated',
            'variants' => [
                ['size' => 'standard', 'price' => 150, 'stock' => 8],
            ],
            'profit_share_amount' => 50,
        ]);

        $response->assertForbidden();
        $this->assertSame('approved', $product->fresh()->status);
    }

    public function test_seller_cannot_delete_approved_product(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'approved',
            'category_id' => $category->id,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($seller)->delete("/seller/products/{$product->slug}")->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_seller_can_delete_own_pending_product(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'pending',
            'category_id' => $category->id,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($seller)->delete("/seller/products/{$product->slug}");
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_seller_cannot_edit_other_sellers_product(): void
    {
        $sellerA = User::factory()->seller()->create();
        $sellerB = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $sellerA->id,
            'status' => 'pending',
            'category_id' => $category->id,
        ]);
        $originalName = $product->name;
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($sellerB)->put("/seller/products/{$product->slug}", [
            'name' => 'Hacked Name',
            'category_id' => $category->id,
            'description' => 'Hacked',
            'variants' => [
                ['size' => 'standard', 'price' => 150, 'stock' => 8],
            ],
            'profit_share_amount' => 50,
        ])->assertForbidden();

        $this->assertSame($originalName, $product->fresh()->name);
    }

    public function test_seller_cannot_create_product_without_name(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($seller)->post('/seller/products', [
            'category_id' => $category->id,
            'profit_share_amount' => 50,
            'variants' => [
                ['size' => 'standard', 'price' => 100, 'stock' => 5],
            ],
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_seller_cannot_create_product_without_category(): void
    {
        $seller = User::factory()->seller()->create();

        $response = $this->actingAs($seller)->post('/seller/products', [
            'name' => 'No Category Product',
            'profit_share_amount' => 50,
            'variants' => [
                ['size' => 'standard', 'price' => 100, 'stock' => 5],
            ],
        ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_seller_cannot_create_product_with_zero_profit_share(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($seller)->post('/seller/products', [
            'name' => 'Zero Profit',
            'category_id' => $category->id,
            'profit_share_amount' => 0,
            'variants' => [
                ['size' => 'standard', 'price' => 100, 'stock' => 5],
            ],
        ]);

        $response->assertSessionHasErrors('profit_share_amount');
    }

    public function test_seller_cannot_create_product_with_negative_price(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($seller)->post('/seller/products', [
            'name' => 'Negative Price',
            'category_id' => $category->id,
            'profit_share_amount' => 50,
            'variants' => [
                ['size' => 'standard', 'price' => -100, 'stock' => 5],
            ],
        ]);

        $response->assertSessionHasErrors('variants.0.price');
    }

    public function test_seller_cannot_create_product_with_negative_stock(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($seller)->post('/seller/products', [
            'name' => 'Negative Stock',
            'category_id' => $category->id,
            'profit_share_amount' => 50,
            'variants' => [
                ['size' => 'standard', 'price' => 100, 'stock' => -5],
            ],
        ]);

        $response->assertSessionHasErrors('variants.0.stock');
    }

    public function test_seller_only_sees_own_products_in_list(): void
    {
        $sellerA = User::factory()->seller()->create();
        $sellerB = User::factory()->seller()->create();
        $category = Category::factory()->create();

        Product::factory()->create([
            'seller_id' => $sellerA->id,
            'status' => 'pending',
            'category_id' => $category->id,
            'name' => 'SellerA Product',
        ]);

        Product::factory()->create([
            'seller_id' => $sellerB->id,
            'status' => 'pending',
            'category_id' => $category->id,
            'name' => 'SellerB Product',
        ]);

        $response = $this->actingAs($sellerA)->get('/seller/products');
        $response->assertSee('SellerA Product');
        $response->assertDontSee('SellerB Product');
    }
}
