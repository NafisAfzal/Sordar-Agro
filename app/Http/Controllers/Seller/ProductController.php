<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreProductRequest;
use App\Http\Requests\Seller\UpdateProductRequest;
use App\Models\Category;
use App\Models\OrderItem;
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

        $sellerProductIds = Product::where('seller_id', auth()->id())->pluck('id');

        $totals = OrderItem::whereHas('variant', fn ($q) => $q->whereIn('product_id', $sellerProductIds))
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid')->where('status', '!=', 'cancelled'))
            ->selectRaw('COALESCE(SUM(quantity), 0) as units_sold, COALESCE(SUM(quantity * marketplace_share_amount), 0) as share_earned')
            ->first();

        return view('seller.products.index', [
            'products' => $products,
            'totalUnitsSold' => (int) $totals->units_sold,
            'totalShareEarned' => (float) $totals->share_earned,
        ]);
    }

    public function create()
    {
        $categories = Category::all();

        return view('seller.products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $data = $request->validated();

        DB::transaction(function () use ($data, $request) {
            $product = Product::create([
                'seller_id' => auth()->id(),
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'thumbnail' => $this->storeThumb($request),
                'is_fish' => $request->boolean('is_fish'),
                'min_tank_size_litres' => $data['min_tank_size_litres'] ?? null,
                'temperament' => $data['temperament'] ?? null,
                'profit_share_amount' => $data['profit_share_amount'],
                // Admin-submitted products skip the approval queue — the
                // admin is the approver, so requiring self-approval would
                // be illogical. Seller submissions still await approval.
                'status' => auth()->user()->isAdmin() ? 'approved' : 'pending',
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
        $this->authorize('view', $product);

        // Sellers may edit pending/rejected items; approved ones are locked.
        if ($product->status === 'approved') {
            return redirect()->route('seller.products.index')
                ->with('error', 'Approved products cannot be edited. Contact an admin.');
        }

        $categories = Category::all();
        $product->load('variants');

        return view('seller.products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        DB::transaction(function () use ($product, $data, $request) {
            $product->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'thumbnail' => $this->storeThumb($request) ?? $product->thumbnail,
                'is_fish' => $request->boolean('is_fish'),
                'min_tank_size_litres' => $data['min_tank_size_litres'] ?? null,
                'temperament' => $data['temperament'] ?? null,
                'profit_share_amount' => $data['profit_share_amount'],
                'status' => 'pending',      // re-submit for approval
                'admin_feedback' => null,
                'rejection_reason_category' => null,
            ]);

            $product->variants()->delete();
            $this->saveVariants($product, $request);
        });

        return redirect()->route('seller.products.index')
            ->with('success', 'Product updated and re-submitted for approval.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    // ---- Helpers ------------------------------------------------------
    private function saveVariants(Product $product, Request $request): void
    {
        foreach ($request->input('variants', []) as $v) {
            ProductVariant::create([
                'product_id' => $product->id,
                'size' => $v['size'],
                'price' => $v['price'],
                'stock' => $v['stock'],
                'size_description' => $v['size_description'] ?? null,
                'sku' => strtoupper(Str::random(8)),
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
}
