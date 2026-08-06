<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('seller', 'category', 'variants')->latest();

        // Default to the review queue (pending) unless a status is chosen.
        $status = $request->query('status', 'pending');
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $products = $query->paginate(12)->withQueryString();

        return view('admin.products.index', compact('products', 'status'));
    }

    public function show(Product $product)
    {
        $product->load('seller', 'category', 'variants', 'images');
        return view('admin.products.show', compact('product'));
    }

    public function approve(Product $product)
    {
        $product->update(['status' => 'approved', 'admin_feedback' => null]);
        return back()->with('success', "“{$product->name}” approved and now live.");
    }

    public function reject(Request $request, Product $product)
    {
        $data = $request->validate([
            'admin_feedback' => ['required', 'string', 'max:1000'],
        ]);

        $product->update([
            'status' => 'rejected',
            'admin_feedback' => $data['admin_feedback'],
        ]);

        return back()->with('success', 'Product rejected with feedback sent to the seller.');
    }
}
