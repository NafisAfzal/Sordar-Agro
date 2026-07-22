<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
     * Create the order in 'unpaid' state, snapshot the line items, then send
     * the customer to the simulated payment gateway. Stock is only decremented
     * once payment succeeds (see PaymentController).
     */
    public function place(Request $request)
    {
        $data = $request->validate([
            'shipping_name'    => ['required', 'string', 'max:255'],
            'shipping_phone'   => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'payment_method'   => ['required', 'in:bkash,nagad'],
        ]);

        $items = Cart::with('variant.product')->where('user_id', auth()->id())->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Guard against a variant selling out between cart and checkout.
        foreach ($items as $item) {
            if ($item->quantity > $item->variant->stock) {
                return back()->with('error',
                    "Not enough stock for {$item->variant->product->name} ({$item->variant->label}).");
            }
        }

        $order = DB::transaction(function () use ($items, $data) {
            $order = Order::create([
                'order_number'     => 'SA-'.strtoupper(Str::random(8)),
                'user_id'          => auth()->id(),
                'total'            => $items->sum(fn ($i) => $i->subtotal()),
                'status'           => 'processing',
                'payment_method'   => $data['payment_method'],
                'payment_status'   => 'unpaid',
                'shipping_name'    => $data['shipping_name'],
                'shipping_phone'   => $data['shipping_phone'],
                'shipping_address' => $data['shipping_address'],
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id'           => $order->id,
                    'product_variant_id' => $item->variant->id,
                    'product_name'       => $item->variant->product->name,
                    'variant_size'       => $item->variant->label,
                    'price'              => $item->variant->price,
                    'quantity'           => $item->quantity,
                ]);
            }

            return $order;
        });

        return redirect()->route('payment.show', $order);
    }
}
