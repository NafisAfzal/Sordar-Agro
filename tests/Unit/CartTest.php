<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\ProductVariant;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    public function test_subtotal_multiplies_variant_price_by_quantity(): void
    {
        $variant = new ProductVariant();
        $variant->price = 250.00;

        $cart = new Cart();
        $cart->quantity = 3;
        $cart->setRelation('variant', $variant);

        $this->assertSame(750.0, $cart->subtotal());
    }
}
