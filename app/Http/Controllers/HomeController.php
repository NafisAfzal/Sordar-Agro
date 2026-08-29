<?php

namespace App\Http\Controllers;

use App\Models\CareGuide;
use App\Models\Category;
use App\Models\CommunitySubmission;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $featured = Product::where('products.status', 'approved')
            ->select('products.*')
            ->with('variants', 'category')
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->join('order_items', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('products.id')
            ->orderByRaw('SUM(order_items.quantity) DESC')
            ->orderByDesc('products.id')
            ->take(4)
            ->get();

        $categories = Category::whereNull('parent_id')->withCount('products')->get();

        $guides = CareGuide::whereNotNull('published_at')
            ->latest('published_at')->take(3)->get();

        $submissions = CommunitySubmission::with('user')
            ->where('status', 'approved')
            ->latest()
            ->take(2)
            ->get();

        return view('storefront.home', compact('featured', 'categories', 'guides', 'submissions'));
    }
}
