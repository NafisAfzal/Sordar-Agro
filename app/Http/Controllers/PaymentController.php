<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Payment confirmation flow. The customer sends money manually via
 * bKash/Nagad "Send Money" and submits the resulting Transaction ID here.
 * There is no live gateway API integration — this records what the
 * customer reports and confirms the order.
 */
class PaymentController extends Controller
{
    public function show(Order $order)
    {
        $this->authorizeOwner($order);

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order);
        }

        return view('checkout.payment', compact('order'));
    }

    public function process(Request $request, Order $order, InventoryService $inventory)
    {
        $this->authorizeOwner($order);

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order);
        }

        // A TrxID may only be used once across all orders.
        $data = $request->validate([
            'transaction_id' => [
                'required', 'string', 'min:6', 'max:50',
                Rule::unique('orders', 'transaction_id')->ignore($order->id),
            ],
        ], [
            'transaction_id.required' => 'Please enter the Transaction ID from your confirmation message.',
            'transaction_id.min'      => 'That Transaction ID looks too short — please check your SMS.',
            'transaction_id.unique'   => 'This Transaction ID has already been used for another order.',
        ]);

        // Eager-load once to avoid N+1 in the loops below.
        $order->loadMissing('items.variant.product');

        // Stock can change between checkout and payment — re-check before accepting.
        foreach ($order->items as $item) {
            if (! $item->variant || $item->quantity > $item->variant->stock) {
                $name = $item->product_name.' ('.$item->variant_size.')';
                return redirect()->route('payment.show', $order)
                    ->with('error', "Sorry, \"{$name}\" is no longer available in the quantity you ordered. Please adjust your cart.");
            }
        }

        try {
            DB::transaction(function () use ($order, $data) {
                $order->update([
                    'payment_status' => 'paid',
                    'transaction_id' => strtoupper(trim($data['transaction_id'])),
                ]);

                foreach ($order->items as $item) {
                    // Row lock prevents two concurrent payments overselling.
                    $variant = $item->variant()->lockForUpdate()->first();
                    if (! $variant || $variant->stock < $item->quantity) {
                        throw new \RuntimeException('insufficient_stock');
                    }
                    $variant->decrement('stock', $item->quantity);
                }

                Cart::where('user_id', $order->user_id)->delete();
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('payment.show', $order)
                ->with('error', 'Some items sold out while we processed your payment. Please contact support with your Transaction ID.');
        }

        foreach ($order->items as $item) {
            if ($item->variant) {
                $inventory->resetNotificationsIfDepleted($item->variant->product);
            }
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Thank you! Your payment details have been submitted and your order is confirmed.');
    }

    private function authorizeOwner(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 403);
    }
}