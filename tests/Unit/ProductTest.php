<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariant;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    private function variant(float $price, int $stock): ProductVariant
    {
        $variant = new ProductVariant();
        $variant->price = $price;
        $variant->stock = $stock;

        return $variant;
    }

    public function test_starting_price_is_minimum_across_variants(): void
    {
        $product = new Product();
        $product->setRelation('variants', collect([
            $this->variant(500.00, 5),
            $this->variant(300.00, 5),
            $this->variant(800.00, 5),
        ]));

        $this->assertSame(300.0, $product->starting_price);
    }

    public function test_starting_price_with_single_variant(): void
    {
        $product = new Product();
        $product->setRelation('variants', collect([
            $this->variant(450.00, 5),
        ]));

        $this->assertSame(450.0, $product->starting_price);
    }

    public function test_total_stock_sums_across_variants_including_zero_stock_ones(): void
    {
        $product = new Product();
        $product->setRelation('variants', collect([
            $this->variant(500.00, 5),
            $this->variant(300.00, 0),
            $this->variant(800.00, 10),
        ]));

        $this->assertSame(15, $product->total_stock);
    }

    public function test_is_out_of_stock_true_when_total_stock_is_zero(): void
    {
        $product = new Product();
        $product->setRelation('variants', collect([
            $this->variant(500.00, 0),
            $this->variant(300.00, 0),
        ]));

        $this->assertTrue($product->isOutOfStock());
    }

    public function test_is_out_of_stock_false_when_any_variant_has_stock(): void
    {
        $product = new Product();
        $product->setRelation('variants', collect([
            $this->variant(500.00, 0),
            $this->variant(300.00, 5),
        ]));

        $this->assertFalse($product->isOutOfStock());
    }
}
