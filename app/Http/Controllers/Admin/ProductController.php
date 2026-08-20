<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $product->update([
            'status' => 'approved',
            'admin_feedback' => null,
            'rejection_reason_category' => null,
        ]);
        return back()->with('success', "“{$product->name}” approved and now live.");
    }

    public function reject(Request $request, Product $product)
    {
        $data = $request->validate([
            'rejection_reason_category' => ['required', 'in:profit_share,price,quantity,product_quality,other'],
            'admin_feedback' => ['required', 'string', 'max:1000'],
        ]);

        $product->update([
            'status' => 'rejected',
            'admin_feedback' => $data['admin_feedback'],
            'rejection_reason_category' => $data['rejection_reason_category'],
        ]);

        return back()->with('success', 'Product rejected with feedback sent to the seller.');
    }

    /**
     * Unlike Seller\ProductController::edit(), there is no ownership check
     * and no "approved products are locked" restriction — an admin may
     * edit any product, from any seller, at any status.
     */
    public function edit(Product $product)
    {
        $categories = Category::all();
        $product->load('variants');

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
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
                // Status is intentionally left untouched: the admin is the
                // approver, so an admin edit of an approved product stays
                // approved instead of being bounced back into review.
            ]);

            $product->variants()->delete();
            $this->saveVariants($product, $request);
        });

        return redirect()->route('admin.products.show', $product)
            ->with('success', "“{$product->name}” updated.");
    }

    /**
     * Blocked if any of the product's variants has ever been ordered —
     * order_items keeps a nullable, nullOnDelete link to product_variants,
     * so the database itself would silently allow this (cascading the
     * product/variants away and nulling the link) without this check.
     */
    public function destroy(Product $product)
    {
        $variantIds = $product->variants()->pluck('id');
        $hasOrderHistory = OrderItem::whereIn('product_variant_id', $variantIds)->exists();

        if ($hasOrderHistory) {
            return back()->with('error',
                'Cannot delete — this product has order history; consider marking it unavailable instead.');
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', "“{$product->name}” deleted.");
    }

    // ---- Helpers (mirrors Seller\ProductController's validation/save
    // logic — same shared form/view, but no ownership or status gate) ---
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
}
