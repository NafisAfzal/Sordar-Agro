<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Models\Cart;
use App\Services\OrderService;

class CheckoutController extends Controller
{
    public function show()
    {
        $items = Cart::with('variant.product')->where('user_id', auth()->id())->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $total = $items->sum(fn ($i) => $i->subtotal());

        return view('checkout.show', compact('items', 'total'));
    }

    /**
     * Create the order and snapshot its line items atomically. Stock is
     * re-checked under row locks inside OrderService, but is not reserved
     * until the customer submits a TrxID (see PaymentController).
     */
    public function place(PlaceOrderRequest $request, OrderService $orders)
    {
        try {
            $order = $orders->placeOrder($request->user(), $request->validated());
        } catch (EmptyCartException) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('payment.show', $order);
    }
}
