<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Payment confirmation flow. The customer sends money manually via
 * bKash/Nagad "Send Money" and submits the resulting Transaction ID here.
 *
 * A submitted TrxID is only a claim that money was sent: the order is NOT
 * trusted as paid until an admin verifies it in the dashboard. Stock is
 * reserved at submission time so a verified order can always be fulfilled,
 * and is released again if the admin rejects the payment.
 */
class PaymentController extends Controller
{
    public function show(Order $order)
    {
        $this->authorizeOwner($order);

        if ($order->isPaid()) {
            return redirect()->route('orders.show', $order)
                ->with('info', 'This order has already been paid.');
        }

        if ($order->isAwaitingPaymentVerification()) {
            return redirect()->route('orders.show', $order)
                ->with('info', 'Your payment details are already awaiting admin verification.');
        }

        return view('checkout.payment', compact('order'));
    }

    public function process(Request $request, Order $order, InventoryService $inventory)
    {
        $this->authorizeOwner($order);

        if ($order->isPaid()) {
            return redirect()->route('orders.show', $order)
                ->with('info', 'This order has already been paid.');
        }

        if ($order->isAwaitingPaymentVerification()) {
            return redirect()->route('orders.show', $order)
                ->with('info', 'Your payment details are already awaiting admin verification.');
        }

        // A TrxID may only be used once across all orders. Normalise before
        // validation so the same code cannot be registered twice by casing.
        $transactionId = $request->input('transaction_id');

        if (is_string($transactionId)) {
            $request->merge(['transaction_id' => strtoupper(trim($transactionId))]);
        }

        $data = $request->validate([
            'transaction_id' => [
                'required', 'string', 'min:6', 'max:50', 'regex:/^[A-Z0-9-]+$/',
                Rule::unique('orders', 'transaction_id')->ignore($order->id),
            ],
        ], [
            'transaction_id.required' => 'Please enter the Transaction ID from your confirmation message.',
            'transaction_id.min'      => 'That Transaction ID looks too short — please check your SMS.',
            'transaction_id.max'      => 'That Transaction ID looks too long — please check your SMS.',
            'transaction_id.regex'    => 'A Transaction ID may contain only letters, numbers, and hyphens.',
            'transaction_id.unique'   => 'This Transaction ID has already been used for another order.',
        ]);

        try {
            DB::transaction(function () use ($order, $data) {
                // Lock the order so two tabs cannot both reserve stock for it.
                $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

                if ($locked->isPaid() || $locked->isAwaitingPaymentVerification()) {
                    throw new \LogicException('already_processed');
                }

                $items = $locked->items()->get();

                // Reserve stock now, under row locks, rather than trusting
                // that it will still exist when an admin verifies later.
                foreach ($items as $item) {
                    $variant = $item->variant()->lockForUpdate()->first();

                    if (! $variant || $variant->stock < $item->quantity) {
                        throw new \RuntimeException('insufficient_stock');
                    }

                    $variant->decrement('stock', $item->quantity);
                }

                $locked->forceFill([
                    'payment_status'       => Order::PAYMENT_UNPAID,
                    'transaction_id'       => $data['transaction_id'],
                    'payment_submitted_at' => now(),
                    'payment_verified_at'  => null,
                    'payment_verified_by'  => null,
                ])->save();

                Cart::where('user_id', $locked->user_id)->delete();
            });
        } catch (\LogicException $e) {
            return redirect()->route('orders.show', $order)
                ->with('info', 'Your payment details are already awaiting admin verification.');
        } catch (QueryException $e) {
            // The unique index can still fire if two requests race past the
            // validation check. Treat it as a normal duplicate-TrxID error.
            if ($this->isUniqueConstraintViolation($e)) {
                return redirect()->route('payment.show', $order)
                    ->withErrors(['transaction_id' => 'This Transaction ID has already been used for another order.'])
                    ->withInput();
            }

            throw $e;
        } catch (\RuntimeException $e) {
            return redirect()->route('payment.show', $order)
                ->with('error', 'Some items sold out before we could reserve them. Please contact support before sending money.');
        }

        foreach ($order->items()->with('variant.product')->get() as $item) {
            if ($item->variant?->product) {
                $inventory->resetNotificationsIfDepleted($item->variant->product);
            }
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment details received. Your items are reserved while an admin verifies the transaction.');
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return $e->getCode() === '23000'
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry');
    }

    private function authorizeOwner(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 403);
    }
}
