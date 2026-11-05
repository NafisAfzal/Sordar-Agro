<?php

namespace App\Services;

use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderStateException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns the order lifecycle: placement, payment reservation/verification,
 * rejection, and fulfilment transitions.
 *
 * Every state transition locks the aggregate root (order + variant rows)
 * inside DB::transaction(), and every transition is idempotent: replaying a
 * payment callback or cancellation can never reserve/release stock twice.
 */
class OrderService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CommissionService $commission,
    ) {}

    /**
     * Create the order + line-item snapshots inside one transaction.
     *
     * Stock is intentionally NOT decremented here — the domain reserves it
     * when the customer submits a TrxID (see submitPayment()). Variant rows
     * are still locked to ensure the stock checked here is the stock that
     * exists at commit time, closing the cart-to-order TOCTOU window.
     */
    public function placeOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $items = Cart::with('variant.product')
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw new EmptyCartException;
            }

            $order = Order::create([
                'order_number' => 'SA-'.strtoupper(Str::random(8)),
                'user_id' => $user->id,
                'total' => 0,
                'status' => 'processing',
                'payment_method' => $data['payment_method'],
                'payment_status' => Order::PAYMENT_UNPAID,
                'shipping_name' => $data['shipping_name'],
                'shipping_phone' => $data['shipping_phone'],
                'shipping_address' => $data['shipping_address'],
            ]);

            $total = 0.0;

            foreach ($items as $item) {
                $variant = ProductVariant::whereKey($item->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if (! $variant || $variant->stock < $item->quantity) {
                    throw InsufficientStockException::forItem(
                        $item->variant?->product?->name,
                        $item->variant?->label,
                    );
                }

                $product = $variant->product;
                $total += (float) $variant->price * $item->quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product?->name ?? 'Unknown product',
                    'variant_size' => $variant->label,
                    'price' => $variant->price,
                    'quantity' => $item->quantity,
                    'marketplace_share_amount' => $product
                        ? $this->commission->marketplaceShareForProduct($product)
                        : 0,
                ]);
            }

            $order->forceFill(['total' => $total])->save();

            return $order;
        });
    }

    /**
     * Submit a TrxID: reserve stock and move the order into
     * "awaiting admin verification" atomically.
     *
     * The order row lock serialises duplicate tabs/callbacks. A second
     * submission sees the pending state and throws ALREADY_PROCESSED
     * without touching stock again.
     */
    public function submitPayment(Order $order, string $transactionId): void
    {
        DB::transaction(function () use ($order, $transactionId) {
            $locked = Order::whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'cancelled') {
                throw OrderStateException::cancelled();
            }

            if ($locked->isPaid() || $locked->isAwaitingPaymentVerification()) {
                throw OrderStateException::alreadyProcessed();
            }

            // Deduct stock under variant row locks. Nested in this
            // transaction, so a failed save/unique violation rolls it back.
            $this->inventory->reserveForOrder($locked);

            $locked->forceFill([
                'payment_status' => Order::PAYMENT_UNPAID,
                'transaction_id' => $transactionId,
                'payment_submitted_at' => now(),
                'payment_verified_at' => null,
                'payment_verified_by' => null,
            ])->save();

            Cart::where('user_id', $locked->user_id)->delete();
        });
    }

    /**
     * Mark an awaiting payment as paid exactly once. Stock was already
     * reserved at submission, so verification must never decrement again.
     */
    public function verifyPayment(Order $order, int $adminId): bool
    {
        return DB::transaction(function () use ($order, $adminId) {
            $locked = Order::whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isAwaitingPaymentVerification() || $locked->status === 'cancelled') {
                return false;
            }

            $locked->forceFill([
                'payment_status' => Order::PAYMENT_PAID,
                'payment_verified_at' => now(),
                'payment_verified_by' => $adminId,
            ])->save();

            return true;
        });
    }

    /**
     * Reject an awaiting payment exactly once, releasing the reservation.
     */
    public function rejectPayment(Order $order, int $adminId): bool
    {
        $products = collect();

        $rejected = DB::transaction(function () use ($order, $adminId, &$products) {
            $locked = Order::whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isAwaitingPaymentVerification() || $locked->status === 'cancelled') {
                return false;
            }

            $this->inventory->releaseForOrder($locked);
            $products = $this->productsForOrder($locked);

            $locked->forceFill([
                'payment_status' => Order::PAYMENT_FAILED,
                'payment_verified_at' => now(),
                'payment_verified_by' => $adminId,
            ])->save();

            return true;
        });

        if ($rejected) {
            $this->notifyRestock($products);
        }

        return $rejected;
    }

    /**
     * Expire an unverified reservation past its TTL: release stock and
     * cancel the order exactly once, then notify wishlisted customers.
     */
    public function expireReservation(Order $order): bool
    {
        $products = collect();

        $expired = DB::transaction(function () use ($order, &$products) {
            $locked = Order::whereKey($order->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked
                || $locked->status === 'cancelled'
                || ! $locked->isAwaitingPaymentVerification()) {
                return false;
            }

            $this->inventory->releaseForOrder($locked);
            $products = $this->productsForOrder($locked);

            $locked->forceFill([
                'payment_status' => Order::PAYMENT_FAILED,
                'payment_verified_at' => now(),
                'payment_verified_by' => null,
                'status' => 'cancelled',
            ])->save();

            return true;
        });

        if ($expired) {
            $this->notifyRestock($products);
        }

        return $expired;
    }

    /**
     * Apply an admin fulfilment update. Cancelling a paid/pending order
     * releases reserved stock exactly once; re-cancelling is a no-op.
     */
    public function updateFulfillment(Order $order, array $data, int $adminId): void
    {
        $products = collect();

        DB::transaction(function () use ($order, $data, $adminId, &$products) {
            $locked = Order::whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Auto-generate a tracking code on first shipment if none provided.
            if ($data['status'] === 'shipped'
                && empty($data['tracking_code'])
                && ! empty($data['courier'])) {
                $data['tracking_code'] = strtoupper($data['courier']).'-'.strtoupper(Str::random(10));
            }

            if ($data['status'] === 'cancelled'
                && $locked->status !== 'cancelled'
                && ($locked->isPaid() || $locked->isAwaitingPaymentVerification())) {
                $this->inventory->releaseForOrder($locked);
                $products = $this->productsForOrder($locked);

                // A pending payment that never completed is no longer pending.
                if ($locked->isAwaitingPaymentVerification()) {
                    $data['payment_status'] = Order::PAYMENT_FAILED;
                    $data['payment_verified_at'] = now();
                    $data['payment_verified_by'] = $adminId;
                }
            }

            $locked->update($data);
        });

        $this->notifyRestock($products);
    }

    /** Reset wishlist notification flags for products that sold out. */
    public function resetDepletedNotifications(Order $order): void
    {
        foreach ($order->items()->with('variant.product')->get() as $item) {
            if ($item->variant?->product) {
                $this->inventory->resetNotificationsIfDepleted($item->variant->product);
            }
        }
    }

    /** @return Collection<int, Product> */
    private function productsForOrder(Order $order): Collection
    {
        return $order->items()
            ->with('variant.product')
            ->get()
            ->pluck('variant.product')
            ->filter()
            ->unique('id')
            ->values();
    }

    /** @param Collection<int, Product> $products */
    private function notifyRestock(Collection $products): void
    {
        foreach ($products as $product) {
            $this->inventory->handleRestock($product);
        }
    }
}
