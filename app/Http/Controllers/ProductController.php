<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /** Catalogue with keyword search + filters. */
    public function index(Request $request)
    {
        $query = Product::approved()->with('variants', 'category');

        // Keyword search across name + description.
        if ($term = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Category filter (by slug).
        if ($slug = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
        }

        // Temperament filter (fish).
        if ($temperament = $request->query('temperament')) {
            $query->where('temperament', $temperament);
        }

        // Tank-size filter: products needing <= the given litres.
        if ($tank = (int) $request->query('tank_size')) {
            $query->where('min_tank_size_litres', '<=', $tank);
        }

        // Price-range filter against the cheapest variant.
        $min = $request->query('min_price');
        $max = $request->query('max_price');
        if ($min !== null || $max !== null) {
            $query->whereHas('variants', function ($q) use ($min, $max) {
                if ($min !== null) $q->where('price', '>=', (float) $min);
                if ($max !== null) $q->where('price', '<=', (float) $max);
            });
        }

        // Availability filter.
        if ($request->query('availability') === 'in_stock') {
            $query->whereHas('variants', fn ($q) => $q->where('stock', '>', 0));
        }

        // Sorting.
        match ($request->query('sort')) {
            'newest'      => $query->latest(),
            'name'        => $query->orderBy('name'),
            default       => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::whereNull('parent_id')->get();

        return view('storefront.index', compact('products', 'categories'));
    }

    /** JSON endpoint powering the navbar live‑search dropdown. */
    public function suggestions(Request $request)
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $products = Product::approved()
            ->with('variants', 'category')
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name')
            ->take(8)
            ->get()
            ->map(fn ($p) => [
                'name'     => $p->name,
                'url'      => route('products.show', $p),
                'price'    => number_format($p->starting_price, 2),
                'category' => $p->category->name ?? '',
            ]);

        return response()->json($products);
    }

    /** Detailed product view with per-size variant switching. */
    public function show(Product $product)
    {
        abort_unless($product->status === 'approved', 404);

        $product->load('variants', 'images', 'category', 'seller');

        $related = Product::approved()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('variants')
            ->take(4)->get();

        return view('storefront.show', compact('product', 'related'));
    }
}