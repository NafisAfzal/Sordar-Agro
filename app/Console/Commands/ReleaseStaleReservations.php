<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Releases reserved stock for orders that submitted a TrxID but were never
 * verified within the configured TTL. Transitions them to failed/cancelled
 * and restores stock EXACTLY ONCE — safe to re-run (idempotent).
 */
class ReleaseStaleReservations extends Command
{
    protected $signature = 'orders:release-stale-reservations';

    protected $description = 'Release reserved stock for orders with unverified TrxIDs past the reservation TTL';

    public function handle(InventoryService $inventory): int
    {
        $ttlHours = (int) config('orders.reservation_ttl_hours', 24);
        $cutoff = now()->subHours($ttlHours);

        $staleOrders = Order::query()
            ->where('payment_status', Order::PAYMENT_UNPAID)
            ->whereNotNull('transaction_id')
            ->where('status', '!=', 'cancelled')
            ->where('payment_submitted_at', '<', $cutoff)
            ->get();

        if ($staleOrders->isEmpty()) {
            $this->info('No stale reservations found.');

            return self::SUCCESS;
        }

        $released = 0;

        foreach ($staleOrders as $order) {
            DB::transaction(function () use ($order, $inventory, &$released) {
                // Row-lock to prevent concurrent runs from double-releasing.
                // SQLite (tests) does not support FOR UPDATE, so guard by driver.
                $query = Order::whereKey($order->getKey())
                    ->where('payment_status', Order::PAYMENT_UNPAID)
                    ->whereNotNull('transaction_id')
                    ->where('status', '!=', 'cancelled');

                if (DB::getDriverName() !== 'sqlite') {
                    $query->lockForUpdate();
                }

                $locked = $query->first();

                // Idempotency guard: if the order was already processed by a
                // concurrent run or admin action between our SELECT and this
                // transaction, skip it.
                if (! $locked) {
                    return;
                }

                // Restore reserved stock for each line item.
                foreach ($locked->items()->get() as $item) {
                    $variantQuery = ProductVariant::whereKey($item->product_variant_id);

                    if (DB::getDriverName() !== 'sqlite') {
                        $variantQuery->lockForUpdate();
                    }

                    $variant = $variantQuery->first();

                    if ($variant) {
                        $variant->increment('stock', $item->quantity);
                    }
                }

                // Transition to failed + cancelled so it can never be
                // picked up again by this command or verified by an admin.
                $locked->forceFill([
                    'payment_status' => Order::PAYMENT_FAILED,
                    'payment_verified_at' => now(),
                    'payment_verified_by' => null,
                    'status' => 'cancelled',
                ])->save();

                $released++;

                // Trigger restock notifications for affected products.
                $products = $locked->items()->with('variant.product')->get()
                    ->pluck('variant.product')
                    ->filter()
                    ->unique('id');

                foreach ($products as $product) {
                    $inventory->handleRestock($product);
                }
            });
        }

        $this->info("Released {$released} stale reservation(s).");

        return self::SUCCESS;
    }
}
