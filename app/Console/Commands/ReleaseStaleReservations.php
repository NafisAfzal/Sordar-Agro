<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

/**
 * Releases reserved stock for orders that submitted a TrxID but were never
 * verified within the configured TTL. Transitions them to failed/cancelled
 * and restores stock EXACTLY ONCE — safe to re-run (idempotent).
 */
class ReleaseStaleReservations extends Command
{
    protected $signature = 'orders:release-stale-reservations';

    protected $description = 'Release reserved stock for orders with unverified TrxIDs past the reservation TTL';

    public function handle(OrderService $orders): int
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
            // OrderService locks the order, re-checks its state, releases
            // stock, and sends restock notifications after commit.
            if ($orders->expireReservation($order)) {
                $released++;
            }
        }

        $this->info("Released {$released} stale reservation(s).");

        return self::SUCCESS;
    }
}
