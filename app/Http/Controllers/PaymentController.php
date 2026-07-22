<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SIMULATED bKash / Nagad gateway. A real integration needs merchant
 * credentials; here a button stands in for the provider callback so the full
 * order lifecycle can be demonstrated end to end.
 */
class PaymentController extends Controller
{
    public function show(Order $order)
    {
        $this->authorizeOwner($order);

        // Already-paid orders skip the gateway.
        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order);
        }

        return view('checkout.payment', compact('order'));
    }

    public function process(Request $request, Order $order, InventoryService $inventory)
    {
        $this->authorizeOwner($order);
        $request->validate(['outcome' => ['required', 'in:success,failure']]);

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order);
        }

        // Eager-load items + their variants/products once (avoids N+1 in the
        // loops below).
        $order->loadMissing('items.variant.product');

        // Simulated failure path — order stays placed, customer can retry.
        if ($request->outcome === 'failure') {
            $order->update(['payment_status' => 'failed']);
            return redirect()->route('payment.show', $order)
                ->with('error', 'Payment failed. You can try again.');
        }

        // Stock can change between checkout and payment (another buyer may have
        // bought the last units). Re-validate BEFORE taking payment so we never
        // oversell or drive a variant's stock negative.
        foreach ($order->items as $item) {
            if (! $item->variant || $item->quantity > $item->variant->stock) {
                $name = $item->product_name.' ('.$item->variant_size.')';
                return redirect()->route('payment.show', $order)
                    ->with('error', "Sorry, \"{$name}\" is no longer available in the quantity you ordered. Please adjust your cart.");
            }
        }

        // Success path: mark paid, decrement stock, clear the cart — all atomic.
        // A lock on each variant row prevents two concurrent payments from
        // reading the same stock and both decrementing it.
        try {
            DB::transaction(function () use ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'transaction_id' => strtoupper($order->payment_method).Str::random(10),
                ]);

                foreach ($order->items as $item) {
                    $variant = $item->variant()->lockForUpdate()->first();
                    if (! $variant || $variant->stock < $item->quantity) {
                        // Stock vanished after our pre-check — roll the whole
                        // transaction back rather than overselling.
                        throw new \RuntimeException('insufficient_stock');
                    }
                    $variant->decrement('stock', $item->quantity);
                }

                Cart::where('user_id', $order->user_id)->delete();
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('payment.show', $order)
                ->with('error', 'Some items just sold out while we processed your payment. Your card was not charged — please review your cart and try again.');
        }

        // If anything sold out, reset its wishlist notification flags.
        foreach ($order->items as $item) {
            if ($item->variant) {
                $inventory->resetNotificationsIfDepleted($item->variant->product);
            }
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment successful — your order is confirmed!');
    }

    private function authorizeOwner(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 403);
    }
}
