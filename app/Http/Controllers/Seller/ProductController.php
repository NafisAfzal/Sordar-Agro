<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::where('seller_id', auth()->id())
            ->with('category', 'variants')
            ->latest()->paginate(10);

        return view('seller.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('seller.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);

        DB::transaction(function () use ($data, $request) {
            $product = Product::create([
                'seller_id'   => auth()->id(),
                'category_id' => $data['category_id'],
                'name'        => $data['name'],
                'slug'        => $this->uniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'thumbnail'   => $this->storeThumb($request),
                'is_fish'     => $request->boolean('is_fish'),
                'min_tank_size_litres' => $data['min_tank_size_litres'] ?? null,
                'temperament' => $data['temperament'] ?? null,
                // Admin-submitted products skip the approval queue — the
                // admin is the approver, so requiring self-approval would
                // be illogical. Seller submissions still await approval.
                'status'      => auth()->user()->isAdmin() ? 'approved' : 'pending',
            ]);

            $this->saveVariants($product, $request);
        });

        return redirect()->route('seller.products.index')
            ->with('success', auth()->user()->isAdmin()
                ? 'Product created and published.'
                : 'Product submitted and awaiting admin approval.');
    }

    public function edit(Product $product)
    {
        $this->authorizeOwner($product);

        // Sellers may edit pending/rejected items; approved ones are locked.
        if ($product->status === 'approved') {
            return redirect()->route('seller.products.index')
                ->with('error', 'Approved products cannot be edited. Contact an admin.');
        }

        $categories = Category::all();
        $product->load('variants');

        return view('seller.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwner($product);
        abort_if($product->status === 'approved', 403, 'Approved products are locked.');

        $data = $this->validateProduct($request);

        DB::transaction(function () use ($product, $data, $request) {
            $product->update([
                'category_id' => $data['category_id'],
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'thumbnail'   => $this->storeThumb($request) ?? $product->thumbnail,
                'is_fish'     => $request->boolean('is_fish'),
                'min_tank_size_litres' => $data['min_tank_size_litres'] ?? null,
                'temperament' => $data['temperament'] ?? null,
                'status'      => 'pending',      // re-submit for approval
                'admin_feedback' => null,
            ]);

            $product->variants()->delete();
            $this->saveVariants($product, $request);
        });

        return redirect()->route('seller.products.index')
            ->with('success', 'Product updated and re-submitted for approval.');
    }

    public function destroy(Product $product)
    {
        $this->authorizeOwner($product);
        abort_if($product->status === 'approved', 403, 'Approved products cannot be deleted.');

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    // ---- Helpers ------------------------------------------------------
    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'thumbnail'   => ['nullable', 'image', 'max:2048'],
            'is_fish'     => ['nullable', 'boolean'],
            'min_tank_size_litres' => ['nullable', 'integer', 'min:0'],
            'temperament' => ['nullable', 'in:peaceful,semi-aggressive,aggressive'],

            // Fish: three size rows. Non-fish: a single "standard" row. The
            // form posts arrays keyed by size; we validate the prices/stock.
            'variants'              => ['required', 'array', 'min:1'],
            'variants.*.size'       => ['required', 'in:small,medium,large,standard'],
            'variants.*.price'      => ['required', 'numeric', 'min:0'],
            'variants.*.stock'      => ['required', 'integer', 'min:0'],
            'variants.*.size_description' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function saveVariants(Product $product, Request $request): void
    {
        foreach ($request->input('variants', []) as $v) {
            ProductVariant::create([
                'product_id'       => $product->id,
                'size'             => $v['size'],
                'price'            => $v['price'],
                'stock'            => $v['stock'],
                'size_description' => $v['size_description'] ?? null,
                'sku'              => strtoupper(Str::random(8)),
            ]);
        }
    }

    private function storeThumb(Request $request): ?string
    {
        if ($request->hasFile('thumbnail')) {
            return $request->file('thumbnail')->store('products', 'public');
        }
        return null;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }

    private function authorizeOwner(Product $product): void
    {
        abort_unless($product->seller_id === auth()->id(), 403);
    }
}
