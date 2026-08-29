<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    private function createPaidOrderForProduct(
        User $customer,
        Product $product,
        int $qty,
        string $orderNumber,
        string $paymentStatus = 'paid',
        string $orderStatus = 'processing'
    ): Order {
        $variant = $product->variants()->first();

        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $customer->id,
            'total' => $variant->price * $qty,
            'status' => $orderStatus,
            'payment_method' => 'bkash',
            'payment_status' => $paymentStatus,
            'transaction_id' => 'TRX-' . $orderNumber,
            'shipping_name' => 'Buyer',
            'shipping_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_size' => $variant->label,
            'price' => $variant->price,
            'quantity' => $qty,
            'marketplace_share_amount' => 0,
        ]);

        return $order;
    }

    private function getProductNamesInOrder($response): array
    {
        $content = $response->getContent();
        $names = [];
        foreach ($response->original->featured as $product) {
            $names[] = $product->name;
        }

        return $names;
    }

    public function test_homepage_returns_products_ordered_by_paid_quantity(): void
    {
        $customer = User::factory()->create();

        $productA = Product::factory()->create(['name' => 'Alpha Fish']);
        $productA->variants()->create(['size' => 'standard', 'sku' => 'A1', 'price' => 100, 'stock' => 10]);

        $productB = Product::factory()->create(['name' => 'Beta Fish']);
        $productB->variants()->create(['size' => 'standard', 'sku' => 'B1', 'price' => 150, 'stock' => 10]);

        $this->createPaidOrderForProduct($customer, $productA, 5, 'SA-A1');
        $this->createPaidOrderForProduct($customer, $productB, 3, 'SA-B1');

        $response = $this->get('/');

        $response->assertOk();
        $names = $this->getProductNamesInOrder($response);
        $this->assertSame(['Alpha Fish', 'Beta Fish'], $names);
    }

    public function test_homepage_aggregates_sales_across_variants(): void
    {
        $customer = User::factory()->create();

        $productA = Product::factory()->create(['name' => 'Aggregated Fish']);
        $productA->variants()->create(['size' => 'small', 'sku' => 'A1', 'price' => 100, 'stock' => 10]);
        $productA->variants()->create(['size' => 'large', 'sku' => 'A2', 'price' => 150, 'stock' => 10]);

        $productB = Product::factory()->create(['name' => 'Single Variant Fish']);
        $productB->variants()->create(['size' => 'standard', 'sku' => 'B1', 'price' => 120, 'stock' => 10]);

        $this->createPaidOrderForProduct($customer, $productA, 3, 'SA-A1');
        $this->createPaidOrderForProduct($customer, $productA, 7, 'SA-A2');
        $this->createPaidOrderForProduct($customer, $productB, 9, 'SA-B1');

        $response = $this->get('/');

        $response->assertOk();
        $names = $this->getProductNamesInOrder($response);
        $this->assertSame(['Aggregated Fish', 'Single Variant Fish'], $names);
    }

    public function test_unpaid_orders_excluded_from_ranking(): void
    {
        $customer = User::factory()->create();

        $productA = Product::factory()->create(['name' => 'Paid Fish']);
        $productA->variants()->create(['size' => 'standard', 'sku' => 'A1', 'price' => 100, 'stock' => 10]);

        $productB = Product::factory()->create(['name' => 'Unpaid Fish']);
        $productB->variants()->create(['size' => 'standard', 'sku' => 'B1', 'price' => 100, 'stock' => 10]);

        $this->createPaidOrderForProduct($customer, $productA, 10, 'SA-A1');
        $this->createPaidOrderForProduct($customer, $productB, 50, 'SA-B1', 'unpaid');

        $response = $this->get('/');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString($productA->name, $content);
        $this->assertStringNotContainsString($productB->name, $content);
    }

    public function test_cancelled_orders_excluded_from_ranking(): void
    {
        $customer = User::factory()->create();

        $productA = Product::factory()->create(['name' => 'Shipped Fish']);
        $productA->variants()->create(['size' => 'standard', 'sku' => 'A1', 'price' => 100, 'stock' => 10]);

        $productB = Product::factory()->create(['name' => 'Cancelled Fish']);
        $productB->variants()->create(['size' => 'standard', 'sku' => 'B1', 'price' => 100, 'stock' => 10]);

        $this->createPaidOrderForProduct($customer, $productA, 8, 'SA-A1');
        $this->createPaidOrderForProduct($customer, $productB, 20, 'SA-B1', 'paid', 'cancelled');

        $response = $this->get('/');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString($productA->name, $content);
        $this->assertStringNotContainsString($productB->name, $content);
    }

    public function test_only_approved_products_appear(): void
    {
        $customer = User::factory()->create();

        $approved = Product::factory()->create(['name' => 'Approved Fish']);
        $approved->variants()->create(['size' => 'standard', 'sku' => 'A1', 'price' => 100, 'stock' => 10]);

        $pending = Product::factory()->create(['name' => 'Pending Fish', 'status' => 'pending']);
        $pending->variants()->create(['size' => 'standard', 'sku' => 'P1', 'price' => 100, 'stock' => 10]);

        $rejected = Product::factory()->create(['name' => 'Rejected Fish', 'status' => 'rejected']);
        $rejected->variants()->create(['size' => 'standard', 'sku' => 'R1', 'price' => 100, 'stock' => 10]);

        $this->createPaidOrderForProduct($customer, $approved, 5, 'SA-A1');
        $this->createPaidOrderForProduct($customer, $pending, 100, 'SA-P1');
        $this->createPaidOrderForProduct($customer, $rejected, 100, 'SA-R1');

        $response = $this->get('/');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString($approved->name, $content);
        $this->assertStringNotContainsString($pending->name, $content);
        $this->assertStringNotContainsString($rejected->name, $content);
    }

    public function test_only_four_products_returned(): void
    {
        $customer = User::factory()->create();

        $names = ['First', 'Second', 'Third', 'Fourth', 'Fifth'];
        $products = [];

        foreach ($names as $i => $name) {
            $product = Product::factory()->create(['name' => $name . ' Fish']);
            $product->variants()->create(['size' => 'standard', 'sku' => strtoupper($name) . '-' . $i, 'price' => 100, 'stock' => 10]);
            $products[] = $product;
        }

        foreach ($products as $i => $product) {
            $this->createPaidOrderForProduct($customer, $product, 5 - $i, 'SA-' . strtoupper($names[$i]));
        }

        $response = $this->get('/');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('First Fish', $content);
        $this->assertStringContainsString('Second Fish', $content);
        $this->assertStringContainsString('Third Fish', $content);
        $this->assertStringContainsString('Fourth Fish', $content);
        $this->assertStringNotContainsString('Fifth Fish', $content);
    }

    public function test_ranking_changes_when_sales_change(): void
    {
        $customer = User::factory()->create();

        $productA = Product::factory()->create(['name' => 'Fast Seller']);
        $productA->variants()->create(['size' => 'standard', 'sku' => 'A1', 'price' => 100, 'stock' => 10]);

        $productB = Product::factory()->create(['name' => 'Slow Seller']);
        $productB->variants()->create(['size' => 'standard', 'sku' => 'B1', 'price' => 100, 'stock' => 10]);

        $this->createPaidOrderForProduct($customer, $productA, 2, 'SA-A1');
        $this->createPaidOrderForProduct($customer, $productB, 5, 'SA-B1');

        $response = $this->get('/');
        $content = $response->getContent();
        $posB1 = strpos($content, 'Slow Seller');
        $posA1 = strpos($content, 'Fast Seller');
        $this->assertTrue($posB1 < $posA1);

        $this->createPaidOrderForProduct($customer, $productA, 10, 'SA-A2');

        $response = $this->get('/');
        $content = $response->getContent();
        $posB1 = strpos($content, 'Slow Seller');
        $posA1 = strpos($content, 'Fast Seller');
        $this->assertTrue($posA1 < $posB1);
    }

    public function test_equal_sales_have_deterministic_ordering(): void
    {
        $customer = User::factory()->create();

        $productA = Product::factory()->create(['name' => 'AAA Fish']);
        $productA->variants()->create(['size' => 'standard', 'sku' => 'A1', 'price' => 100, 'stock' => 10]);

        $productB = Product::factory()->create(['name' => 'BBB Fish']);
        $productB->variants()->create(['size' => 'standard', 'sku' => 'B1', 'price' => 100, 'stock' => 10]);

        $this->createPaidOrderForProduct($customer, $productA, 5, 'SA-A1');
        $this->createPaidOrderForProduct($customer, $productB, 5, 'SA-B1');

        $response = $this->get('/');
        $response->assertOk();
        $names = $this->getProductNamesInOrder($response);
        $this->assertCount(2, $names);
        $this->assertSame('BBB Fish', $names[0]);
        $this->assertSame('AAA Fish', $names[1]);

        $response2 = $this->get('/');
        $names2 = $this->getProductNamesInOrder($response2);
        $this->assertSame($names, $names2);
    }
}