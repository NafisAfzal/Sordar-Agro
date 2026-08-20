<?php

namespace App\Http\Controllers;

use App\Models\CareGuide;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $featured = Product::approved()
            ->with('variants', 'category')
            ->orderByDesc('is_featured')
            ->latest()
            ->take(4)
            ->get();

        $categories = Category::whereNull('parent_id')->withCount('products')->get();

        $guides = CareGuide::whereNotNull('published_at')
            ->latest('published_at')->take(3)->get();

        return view('storefront.home', compact('featured', 'categories', 'guides'));
    }
}
