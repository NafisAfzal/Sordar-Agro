<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Centralises stock-change side effects so both the admin inventory screen
 * and seller product editing trigger back-in-stock notifications the same way.
 */
class InventoryService
{
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
                    ."Visit the store to grab it before it sells out again.",
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
