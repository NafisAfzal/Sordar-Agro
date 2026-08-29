<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunitySubmission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'all');
        $dateFrom = $this->resolveDateFrom($period, $request->input('date_from'));
        $dateTo   = $this->resolveDateTo($period, $request->input('date_to'));

        // All-time stats (sidebar cards — always visible regardless of filter).
        $allTimeStats = [
            'customers'         => User::where('role', 'customer')->count(),
            'sellers'           => User::where('role', 'seller')->count(),
            'products'          => Product::count(),
            'pending_products'  => Product::where('status', 'pending')->count(),
            'pending_community' => CommunitySubmission::where('status', 'pending')->count(),
        ];

        // Base query scoped to paid, non-cancelled orders within the selected period.
        $paidOrders = Order::where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled');

        if ($dateFrom) {
            $paidOrders->where('orders.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $paidOrders->where('orders.created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        // Period-scoped summary stats.
        $periodOrders = (clone $paidOrders)->count();
        $periodRevenue = (float) (clone $paidOrders)->sum('total');

        // Marketplace/admin earnings from seller products.
        $marketplaceEarnings = (float) (clone $paidOrders)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->sum(DB::raw('order_items.quantity * order_items.marketplace_share_amount'));

        // Breakdown: admin own-product sales vs seller-product sales.
        $orderItemQuery = OrderItem::whereHas('order', fn ($q) => $q
            ->where('payment_status', 'paid')
            ->where('orders.status', '!=', 'cancelled')
        );
        if ($dateFrom) {
            $orderItemQuery->whereHas('order', fn ($q) => $q->where('orders.created_at', '>=', $dateFrom));
        }
        if ($dateTo) {
            $orderItemQuery->whereHas('order', fn ($q) => $q->where('orders.created_at', '<=', Carbon::parse($dateTo)->endOfDay()));
        }

        $ownProductSales = (float) (clone $orderItemQuery)
            ->whereHas('variant.product', fn ($q) => $q->whereNull('seller_id'))
            ->selectRaw('COALESCE(SUM(order_items.price * order_items.quantity), 0) as total')
            ->value('total');

        $sellerProductSales = (float) (clone $orderItemQuery)
            ->whereHas('variant.product', fn ($q) => $q->whereNotNull('seller_id'))
            ->selectRaw('COALESCE(SUM(order_items.price * order_items.quantity), 0) as total')
            ->value('total');

        $totalUnitsSold = (int) (clone $orderItemQuery)->sum('quantity');

        // Product-level sales — grouped by product with seller info.
        $salesByProduct = DB::table('order_items')
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->leftJoin('users', 'products.seller_id', '=', 'users.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.status', '!=', 'cancelled');

        if ($dateFrom) {
            $salesByProduct->where('orders.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $salesByProduct->where('orders.created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $salesByProduct = $salesByProduct
            ->groupBy('products.id', 'products.name')
            ->groupBy('users.name')
            ->selectRaw('
                products.name as product_name,
                products.seller_id,
                COALESCE(users.name, "Marketplace") as seller_name,
                SUM(order_items.quantity) as units_sold,
                SUM(order_items.quantity * order_items.price) as revenue,
                SUM(order_items.quantity * order_items.marketplace_share_amount) as marketplace_earned
            ')
            ->orderByDesc('revenue')
            ->get();

        // Marketplace share breakdown by seller and product.
        $shareBreakdown = DB::table('order_items')
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->leftJoin('users', 'products.seller_id', '=', 'users.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.status', '!=', 'cancelled');

        if ($dateFrom) {
            $shareBreakdown->where('orders.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $shareBreakdown->where('orders.created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $shareBreakdown = $shareBreakdown
            ->groupBy('products.id', 'products.name')
            ->groupBy('users.name')
            ->selectRaw('products.name as product_name, COALESCE(users.name, "Marketplace") as seller_name,
                SUM(order_items.quantity) as units_sold,
                SUM(order_items.quantity * order_items.marketplace_share_amount) as share_earned')
            ->orderByDesc('share_earned')
            ->get();

        $recentOrders = Order::with('user')->latest()->take(8)->get();

        return view('admin.dashboard', array_merge($allTimeStats, [
            'period'             => $period,
            'dateFrom'           => $dateFrom?->format('Y-m-d'),
            'dateTo'             => $dateTo?->format('Y-m-d'),
            'periodOrders'       => $periodOrders,
            'periodRevenue'      => $periodRevenue,
            'marketplaceEarnings'=> $marketplaceEarnings,
            'ownProductSales'    => $ownProductSales,
            'sellerProductSales' => $sellerProductSales,
            'totalUnitsSold'     => $totalUnitsSold,
            'salesByProduct'     => $salesByProduct,
            'shareBreakdown'     => $shareBreakdown,
            'recentOrders'       => $recentOrders,
        ]));
    }

    private function resolveDateFrom(string $period, ?string $customFrom): ?Carbon
    {
        return match ($period) {
            'today'  => Carbon::today(),
            'week'   => Carbon::now()->startOfWeek(),
            'month'  => Carbon::now()->startOfMonth(),
            'custom' => $customFrom ? Carbon::parse($customFrom) : null,
            default  => null,
        };
    }

    private function resolveDateTo(string $period, ?string $customTo): ?Carbon
    {
        return match ($period) {
            'today'  => Carbon::today(),
            'week'   => Carbon::now()->endOfWeek(),
            'month'  => Carbon::now()->endOfMonth(),
            'custom' => $customTo ? Carbon::parse($customTo) : null,
            default  => null,
        };
    }
}
