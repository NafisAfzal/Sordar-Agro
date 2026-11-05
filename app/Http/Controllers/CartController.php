<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Models\Cart;
use App\Models\ProductVariant;

class CartController extends Controller
{
    public function index()
    {
        $items = Cart::with('variant.product')
            ->where('user_id', auth()->id())
            ->get();

        $total = $items->sum(fn ($i) => $i->subtotal());

        return view('cart.index', compact('items', 'total'));
    }

    /**
     * Add a chosen VARIANT to the cart. For fish the variant encodes the
     * selected size; quantity counts pairs.
     */
    public function add(AddToCartRequest $request, ProductVariant $variant)
    {
        $data = $request->validated();
        $qty = $data['quantity'] ?? 1;

        if ($variant->stock <= 0) {
            return back()->with('error', 'That option is out of stock — add it to your wishlist instead.');
        }

        $item = Cart::firstOrNew([
            'user_id' => auth()->id(),
            'product_variant_id' => $variant->id,
        ]);

        $item->quantity = min(($item->quantity ?? 0) + $qty, $variant->stock);
        $item->save();

        return back()->with('success', 'Added to your cart.');
    }

    public function update(UpdateCartRequest $request, Cart $cart)
    {
        $this->authorizeOwner($cart);

        $data = $request->validated();

        // Never let the cart exceed available stock.
        $cart->quantity = min($data['quantity'], $cart->variant->stock);
        $cart->save();

        // The +/- stepper calls this via fetch() and expects JSON, while the
        // old form-based flow still expects a redirect back to the cart page.
        if ($request->wantsJson()) {
            return response()->json(['quantity' => $cart->quantity]);
        }

        return back()->with('success', 'Cart updated.');
    }

    public function remove(Cart $cart)
    {
        $this->authorizeOwner($cart);
        $cart->delete();

        return back()->with('success', 'Item removed from cart.');
    }

    private function authorizeOwner(Cart $cart): void
    {
        abort_unless($cart->user_id === auth()->id(), 403);
    }
}
