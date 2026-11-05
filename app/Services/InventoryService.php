<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Centralises stock mutations so every path that reserves, releases or
 * manually adjusts inventory takes the same row locks inside a transaction.
 */
class InventoryService
{
    /**
     * Reserve (decrement) stock for every line of an order.
     *
     * Each variant row is locked with SELECT ... FOR UPDATE before its stock
     * is read, eliminating the check-then-decrement race (TOCTOU) between
     * concurrent payment submissions. Must run inside an order-level
     * transaction so a later failure rolls the reservation back.
     */
    public function reserveForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items()->get() as $item) {
                $variant = ProductVariant::whereKey($item->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if (! $variant || $variant->stock < $item->quantity) {
                    throw InsufficientStockException::forItem(
                        $item->variant?->product?->name,
                        $item->variant_size,
                    );
                }

                $variant->decrement('stock', $item->quantity);
            }
        });
    }

    /**
     * Release previously reserved stock for every line of an order.
     *
     * Variant rows are locked before incrementing so concurrent release
     * paths (admin rejection, cancellation, stale-reservation cleanup)
     * cannot double-credit the same reservation.
     */
    public function releaseForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items()->get() as $item) {
                $variant = ProductVariant::whereKey($item->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if ($variant) {
                    $variant->increment('stock', $item->quantity);
                }
            }
        });
    }

    /**
     * Apply a signed manual stock delta under a row lock. Stock never drops
     * below zero even if two admins adjust the same variant concurrently.
     */
    public function adjust(ProductVariant $variant, int $delta): ProductVariant
    {
        return DB::transaction(function () use ($variant, $delta) {
            $locked = ProductVariant::whereKey($variant->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $locked->forceFill([
                'stock' => max(0, (int) $locked->stock + $delta),
            ])->save();

            return $locked;
        });
    }

    /**
     * Call AFTER a product's variant stock has been increased. If the product
     * moved from out-of-stock to in-stock, notify everyone who wishlisted it
     * and hasn't already been notified for this restock.
     */
    public function handleRestock(Product $product): void
    {
        $product->loadMissing('variants');

        if ($product->total_stock <= 0) {
            return; // still out of stock — nothing to do
        }

        $wishlists = Wishlist::with('user')
            ->where('product_id', $product->id)
            ->where('notified', false)
            ->get();

        foreach ($wishlists as $wishlist) {
            // With MAIL_MAILER=log this writes the "email" to the Laravel log,
            // which is enough to demonstrate the notification flow without SMTP.
            try {
                Mail::raw(
                    "Good news! \"{$product->name}\" is back in stock at Sordar Agro. "
                    .'Visit the store to grab it before it sells out again.',
                    function ($message) use ($wishlist, $product) {
                        $message->to($wishlist->user->email)
                            ->subject("Back in stock: {$product->name}");
                    }
                );
            } catch (\Throwable $e) {
                Log::warning('Restock notification failed: '.$e->getMessage());
            }

            $wishlist->update(['notified' => true]);
        }
    }

    /** Reset the notified flag when a product goes out of stock again. */
    public function resetNotificationsIfDepleted(Product $product): void
    {
        $product->loadMissing('variants');

        if ($product->total_stock <= 0) {
            Wishlist::where('product_id', $product->id)->update(['notified' => false]);
        }
    }
}
