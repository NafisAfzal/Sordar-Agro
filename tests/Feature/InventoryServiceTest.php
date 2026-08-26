<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_handleRestock_does_nothing_when_product_still_out_of_stock(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 0]);
        $product->variants()->create(['size' => 'medium', 'sku' => 'SKU2', 'price' => 150, 'stock' => 0]);

        $user = User::factory()->create(['email' => 'customer@example.com']);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id, 'notified' => false]);

        $service = new InventoryService();
        $service->handleRestock($product);

        // Notified flag should remain false since product is still out of stock
        $this->assertFalse(Wishlist::where('product_id', $product->id)->first()->notified);
    }

    public function test_handleRestock_updates_notified_flag_when_product_comes_back_in_stock(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 0]);
        $product->variants()->create(['size' => 'medium', 'sku' => 'SKU2', 'price' => 150, 'stock' => 5]);

        $user = User::factory()->create(['email' => 'customer@example.com']);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id, 'notified' => false]);

        $service = new InventoryService();
        $service->handleRestock($product);

        // Notified flag should be updated to true
        $this->assertTrue(Wishlist::where('product_id', $product->id)->first()->notified);
    }

    public function test_handleRestock_does_not_renotify_already_notified_users(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 0]);
        $product->variants()->create(['size' => 'medium', 'sku' => 'SKU2', 'price' => 150, 'stock' => 5]);

        $user = User::factory()->create(['email' => 'customer@example.com']);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id, 'notified' => true]);

        $service = new InventoryService();
        $service->handleRestock($product);

        // Notified flag should remain true (no change)
        $this->assertTrue(Wishlist::where('product_id', $product->id)->first()->notified);
    }

    public function test_resetNotificationsIfDepleted_resets_notified_flag_when_out_of_stock(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 0]);
        $product->variants()->create(['size' => 'medium', 'sku' => 'SKU2', 'price' => 150, 'stock' => 0]);

        $user = User::factory()->create();
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id, 'notified' => true]);

        $service = new InventoryService();
        $service->resetNotificationsIfDepleted($product);

        $this->assertFalse(Wishlist::where('product_id', $product->id)->first()->notified);
    }

    public function test_resetNotificationsIfDepleted_does_nothing_when_still_in_stock(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        $product->variants()->create(['size' => 'standard', 'sku' => 'SKU1', 'price' => 100, 'stock' => 0]);
        $product->variants()->create(['size' => 'medium', 'sku' => 'SKU2', 'price' => 150, 'stock' => 5]);

        $user = User::factory()->create();
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id, 'notified' => true]);

        $service = new InventoryService();
        $service->resetNotificationsIfDepleted($product);

        $this->assertTrue(Wishlist::where('product_id', $product->id)->first()->notified);
    }
}