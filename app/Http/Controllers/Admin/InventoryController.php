<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\Request;

/**
 * Manual stock adjustment. Admins can increase stock (e.g. new shipment) or
 * decrease it (e.g. fish died) per variant. Increases trigger back-in-stock
 * notifications for wishlisted products via InventoryService.
 */
class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('variants', 'category')->orderBy('name');

        if ($term = $request->query('q')) {
            $query->where('name', 'like', "%{$term}%");
        }

        $products = $query->paginate(15)->withQueryString();

        return view('admin.products.inventory', compact('products'));
    }

    public function adjust(Request $request, ProductVariant $variant, InventoryService $inventory)
    {
        $data = $request->validate([
            // Signed delta: e.g. +10 new stock, -2 if two fish died.
            'delta' => ['required', 'integer'],
        ]);

        $newStock = max(0, $variant->stock + $data['delta']);
        $variant->update(['stock' => $newStock]);

        $product = $variant->product;

        // Fire restock notifications if this brought the product back in stock.
        if ($data['delta'] > 0) {
            $inventory->handleRestock($product);
        } else {
            $inventory->resetNotificationsIfDepleted($product);
        }

        return back()->with('success',
            "Stock for “{$product->name}” ({$variant->label}) set to {$newStock}.");
    }
}
