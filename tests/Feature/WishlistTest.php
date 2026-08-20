<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_a_product_to_wishlist(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create();

        $this->actingAs($customer)->post("/wishlist/{$product->slug}");

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_customer_cannot_add_the_same_product_twice(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create();

        $this->actingAs($customer)->post("/wishlist/{$product->slug}");
        $this->actingAs($customer)->post("/wishlist/{$product->slug}");

        $this->assertSame(1, Wishlist::where('user_id', $customer->id)
            ->where('product_id', $product->id)->count());
    }

    public function test_customer_can_remove_a_product_from_wishlist(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create();
        $wishlist = Wishlist::create(['user_id' => $customer->id, 'product_id' => $product->id]);

        $this->actingAs($customer)->delete("/wishlist/{$wishlist->id}");

        $this->assertDatabaseMissing('wishlists', ['id' => $wishlist->id]);
    }
}
