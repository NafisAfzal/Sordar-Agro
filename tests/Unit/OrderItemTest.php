<?php

namespace Tests\Unit;

use App\Models\OrderItem;
use PHPUnit\Framework\TestCase;

class OrderItemTest extends TestCase
{
    public function test_line_total_multiplies_price_by_quantity(): void
    {
        $item = new OrderItem();
        $item->price = 250.00;
        $item->quantity = 3;

        $this->assertSame(750.0, $item->lineTotal());
    }

    public function test_line_total_is_zero_when_quantity_is_zero(): void
    {
        $item = new OrderItem();
        $item->price = 500.00;
        $item->quantity = 0;

        $this->assertSame(0.0, $item->lineTotal());
    }

    public function test_line_total_handles_single_quantity(): void
    {
        $item = new OrderItem();
        $item->price = 99.99;
        $item->quantity = 1;

        $this->assertSame(99.99, $item->lineTotal());
    }
}