<?php

namespace Tests\Unit;

use App\Models\Order;
use PHPUnit\Framework\TestCase;

class OrderStatusColorTest extends TestCase
{
    public function test_processing_status_is_warning(): void
    {
        $order = new Order();
        $order->status = 'processing';

        $this->assertSame('warning', $order->statusColor());
    }

    public function test_shipped_status_is_info(): void
    {
        $order = new Order();
        $order->status = 'shipped';

        $this->assertSame('info', $order->statusColor());
    }

    public function test_delivered_status_is_success(): void
    {
        $order = new Order();
        $order->status = 'delivered';

        $this->assertSame('success', $order->statusColor());
    }

    public function test_cancelled_status_is_danger(): void
    {
        $order = new Order();
        $order->status = 'cancelled';

        $this->assertSame('danger', $order->statusColor());
    }

    public function test_unknown_status_falls_back_to_secondary(): void
    {
        $order = new Order();
        $order->status = 'something_else';

        $this->assertSame('secondary', $order->statusColor());
    }
}