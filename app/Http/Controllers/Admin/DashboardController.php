<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunitySubmission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'customers'        => User::where('role', 'customer')->count(),
            'sellers'          => User::where('role', 'seller')->count(),
            'products'         => Product::count(),
            'pending_products' => Product::where('status', 'pending')->count(),
            'orders'           => Order::count(),
            'revenue'          => (float) Order::where('payment_status', 'paid')->sum('total'),
            'pending_community'=> CommunitySubmission::where('status', 'pending')->count(),
            'marketplace_share' => (float) OrderItem::whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
                ->selectRaw('COALESCE(SUM(quantity * marketplace_share_amount), 0) as total')
                ->value('total'),
        ];

        $recentOrders = Order::with('user')->latest()->take(8)->get();

        // Marketplace share earned, broken down by seller and product — paid orders only.
        $shareBreakdown = DB::table('order_items')
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->leftJoin('users', 'products.seller_id', '=', 'users.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->groupBy('products.id', 'products.name')
            ->groupBy('users.name')
            ->selectRaw('products.name as product_name, COALESCE(users.name, "Marketplace") as seller_name,
                SUM(order_items.quantity) as units_sold,
                SUM(order_items.quantity * order_items.marketplace_share_amount) as share_earned')
            ->orderByDesc('share_earned')
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'shareBreakdown'));
    }
}
