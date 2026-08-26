<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_cannot_edit_another_sellers_product(): void
    {
        $sellerA = User::factory()->seller()->create();
        $sellerB = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $sellerA->id,
            'status'    => 'pending',
            'category_id' => $category->id,
            'name'      => 'Original Name',
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($sellerB)
            ->put("/seller/products/{$product->slug}", [
                'name'        => 'Hacked Name',
                'category_id' => $category->id,
                'description' => 'Updated',
                'variants'    => [
                    ['size' => 'standard', 'price' => 150, 'stock' => 8],
                ],
                'profit_share_amount' => 50,
            ])
            ->assertForbidden();

        $this->assertSame('Original Name', $product->fresh()->name);
    }

    public function test_seller_cannot_delete_another_sellers_product(): void
    {
        $sellerA = User::factory()->seller()->create();
        $sellerB = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $sellerA->id,
            'status'    => 'pending',
            'category_id' => $category->id,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($sellerB)->delete("/seller/products/{$product->slug}")
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_seller_cannot_update_approved_product(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'approved',
            'category_id' => $category->id,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($seller)
            ->put("/seller/products/{$product->slug}", [
                'name'        => 'Attempted Update',
                'category_id' => $category->id,
                'description' => 'Updated',
                'variants'    => [
                    ['size' => 'standard', 'price' => 150, 'stock' => 8],
                ],
                'profit_share_amount' => 50,
            ])
            ->assertForbidden();
    }

    public function test_seller_cannot_delete_approved_product(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'approved',
            'category_id' => $category->id,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($seller)->delete("/seller/products/{$product->slug}")
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_seller_can_create_product_with_fish_variants(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $this->actingAs($seller)->post('/seller/products', [
            'name'        => 'Premium Betta',
            'category_id' => $category->id,
            'description' => 'Beautiful betta fish',
            'is_fish'     => 1,
            'profit_share_amount' => 75,
            'variants'    => [
                ['size' => 'small', 'price' => 200, 'stock' => 10, 'size_description' => '2-3 cm'],
                ['size' => 'medium', 'price' => 300, 'stock' => 8, 'size_description' => '3-4 cm'],
                ['size' => 'large', 'price' => 450, 'stock' => 5, 'size_description' => '4-5 cm'],
            ],
        ]);

        $product = Product::where('name', 'Premium Betta')->firstOrFail();
        $this->assertSame('pending', $product->status);
        $this->assertSame($seller->id, $product->seller_id);
        $this->assertTrue($product->is_fish);
        $this->assertCount(3, $product->variants);
        $this->assertSame('200.00', (string) $product->variants()->where('size', 'small')->first()->price);
        $this->assertSame('300.00', (string) $product->variants()->where('size', 'medium')->first()->price);
        $this->assertSame('450.00', (string) $product->variants()->where('size', 'large')->first()->price);
    }

    public function test_seller_can_create_non_fish_product_with_standard_variant(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $this->actingAs($seller)->post('/seller/products', [
            'name'        => 'Aquarium Plant Food',
            'category_id' => $category->id,
            'description' => 'Liquid fertilizer',
            'is_fish'     => 0,
            'profit_share_amount' => 30,
            'variants'    => [
                ['size' => 'standard', 'price' => 150, 'stock' => 20],
            ],
        ]);

        $product = Product::where('name', 'Aquarium Plant Food')->firstOrFail();
        $this->assertSame('pending', $product->status);
        $this->assertFalse($product->is_fish);
        $this->assertCount(1, $product->variants);
        $this->assertSame('standard', $product->variants()->first()->size);
    }

    public function test_seller_can_update_own_pending_product_and_it_goes_back_to_pending(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'pending',
            'category_id' => $category->id,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($seller)->put("/seller/products/{$product->slug}", [
            'name'        => 'Updated Name',
            'category_id' => $category->id,
            'description' => 'Updated description',
            'variants'    => [
                ['size' => 'standard', 'price' => 150, 'stock' => 8],
            ],
            'profit_share_amount' => 50,
        ]);

        $product->refresh();
        $this->assertSame('Updated Name', $product->name);
        $this->assertSame('pending', $product->status);
        $this->assertSame('150.00', (string) $product->variants()->first()->price);
    }

    public function test_seller_can_update_own_rejected_product_and_it_goes_back_to_pending(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'rejected',
            'category_id' => $category->id,
            'admin_feedback' => 'Price too high',
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($seller)->put("/seller/products/{$product->slug}", [
            'name'        => 'Updated Name',
            'category_id' => $category->id,
            'description' => 'Updated description',
            'variants'    => [
                ['size' => 'standard', 'price' => 80, 'stock' => 8],
            ],
            'profit_share_amount' => 50,
        ]);

        $product->refresh();
        $this->assertSame('Updated Name', $product->name);
        $this->assertSame('pending', $product->status);
        $this->assertNull($product->admin_feedback);
        $this->assertNull($product->rejection_reason_category);
    }

    public function test_seller_can_delete_own_pending_product(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'pending',
            'category_id' => $category->id,
        ]);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 5]);

        $this->actingAs($seller)->delete("/seller/products/{$product->slug}");

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_seller_cannot_submit_product_without_variants(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($seller)->post('/seller/products', [
            'name'        => 'No Variants Product',
            'category_id' => $category->id,
            'description' => 'Missing variants',
            'is_fish'     => 0,
            'profit_share_amount' => 50,
            'variants'    => [],
        ]);

        $response->assertSessionHasErrors('variants');
        $this->assertDatabaseMissing('products', ['name' => 'No Variants Product']);
    }

    public function test_seller_cannot_submit_product_without_profit_share(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($seller)->post('/seller/products', [
            'name'        => 'No Profit Share Product',
            'category_id' => $category->id,
            'description' => 'Missing profit share',
            'is_fish'     => 0,
            'variants'    => [
                ['size' => 'standard', 'price' => 100, 'stock' => 5],
            ],
        ]);

        $response->assertSessionHasErrors('profit_share_amount');
        $this->assertDatabaseMissing('products', ['name' => 'No Profit Share Product']);
    }
}