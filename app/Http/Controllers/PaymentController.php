<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderStateException;
use App\Http\Requests\Payment\SubmitPaymentRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Database\QueryException;

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
        $this->authorize('view', $order);

        if ($order->status === 'cancelled') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'This order has been cancelled, so it cannot be paid.');
        }

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

    public function process(SubmitPaymentRequest $request, Order $order, OrderService $orders)
    {
        $this->authorize('view', $order);

        if ($order->status === 'cancelled') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'This order has been cancelled, so it cannot be paid.');
        }

        if ($order->isPaid()) {
            return redirect()->route('orders.show', $order)
                ->with('info', 'This order has already been paid.');
        }

        if ($order->isAwaitingPaymentVerification()) {
            return redirect()->route('orders.show', $order)
                ->with('info', 'Your payment details are already awaiting admin verification.');
        }

        try {
            $orders->submitPayment($order, (string) $request->validated('transaction_id'));
        } catch (OrderStateException $e) {
            if ($e->reason === OrderStateException::CANCELLED) {
                return redirect()->route('orders.show', $order)
                    ->with('error', 'This order has been cancelled, so it cannot be paid.');
            }

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
        } catch (InsufficientStockException) {
            return redirect()->route('payment.show', $order)
                ->with('error', 'Some items sold out before we could reserve them. Please contact support before sending money.');
        }

        $orders->resetDepletedNotifications($order);

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
}
