<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $sellerId = auth()->id();

        // ── Catalogue status counts (always, independent of period) ──
        $sellerProducts = Product::where('seller_id', $sellerId)->with('variants')->get();
        $stats = [
            'total'    => $sellerProducts->count(),
            'approved' => $sellerProducts->where('status', 'approved')->count(),
            'pending'  => $sellerProducts->where('status', 'pending')->count(),
            'rejected' => $sellerProducts->where('status', 'rejected')->count(),
        ];
        $lowThreshold = 5;
        $lowStockProducts = $sellerProducts->filter(fn ($p) => $p->total_stock > 0 && $p->total_stock <= $lowThreshold)->values();
        $outOfStockProducts = $sellerProducts->filter(fn ($p) => $p->total_stock <= 0)->values();
        $lowStockCount = $lowStockProducts->count();
        $outOfStockCount = $outOfStockProducts->count();

        // ── Period handling (same semantics as Admin/DashboardController) ──
        $period = $request->input('period', 'all');
        if (! in_array($period, ['all', 'today', 'week', 'month', 'custom'], true)) {
            $period = 'all';
        }
        $dateFrom = $this->resolveDateFrom($period, $request->input('date_from'));
        $dateTo   = $this->resolveDateTo($period, $request->input('date_to'));

        $sellerProductIds = $sellerProducts->pluck('id')->all();

        // Base order-item query scoped to this seller, paid, non-cancelled
        $sellerItemQuery = function () use ($sellerProductIds, $dateFrom, $dateTo) {
            $q = \App\Models\OrderItem::whereHas('variant', fn ($vq) => $vq->whereIn('product_id', $sellerProductIds))
                ->whereHas('order', fn ($oq) => $oq->where('payment_status', 'paid')->where('status', '!=', 'cancelled'));
            if ($dateFrom) {
                $q->whereHas('order', fn ($oq) => $oq->where('orders.created_at', '>=', $dateFrom));
            }
            if ($dateTo) {
                $q->whereHas('order', fn ($oq) => $oq->where('orders.created_at', '<=', Carbon::parse($dateTo)->endOfDay()));
            }
            return $q;
        };

        if (empty($sellerProductIds)) {
            $periodGross = 0; $periodShare = 0; $periodUnits = 0; $periodOrders = 0; $pendingOrders = 0;
            $chartRows = collect(); $productPerformance = collect(); $recentOrdersRows = collect();
        } else {
            $base = $sellerItemQuery();
            $periodGross  = (float) (clone $base)->selectRaw('COALESCE(SUM(price * quantity),0) as v')->value('v');
            $periodShare  = (float) (clone $base)->selectRaw('COALESCE(SUM(quantity * marketplace_share_amount),0) as v')->value('v');
            $periodUnits  = (int)   (clone $base)->sum('quantity');
            $periodOrders = (int)   (clone $base)->selectRaw('COUNT(DISTINCT order_id) as c')->value('c');

            // Pending orders needing fulfillment (processing) for this seller in this period
            $pendingBase = \App\Models\OrderItem::whereHas('variant', fn ($vq) => $vq->whereIn('product_id', $sellerProductIds))
                ->whereHas('order', fn ($oq) => $oq->where('payment_status','paid')->where('status','processing'));
            if ($dateFrom) $pendingBase->whereHas('order', fn ($oq) => $oq->where('orders.created_at','>=',$dateFrom));
            if ($dateTo) $pendingBase->whereHas('order', fn ($oq) => $oq->where('orders.created_at','<=', Carbon::parse($dateTo)->endOfDay()));
            $pendingOrders = (int) $pendingBase->selectRaw('COUNT(DISTINCT order_id) as c')->value('c');

            // ── Sales trend grouped by date ──
            $trendQuery = DB::table('order_items')
                ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereIn('product_variants.product_id', $sellerProductIds)
                ->where('orders.payment_status', 'paid')
                ->where('orders.status', '!=', 'cancelled');
            if ($dateFrom) $trendQuery->where('orders.created_at', '>=', $dateFrom);
            if ($dateTo)   $trendQuery->where('orders.created_at', '<=', Carbon::parse($dateTo)->endOfDay());
            $chartRows = $trendQuery
                ->groupBy(DB::raw('DATE(orders.created_at)'))
                ->selectRaw('DATE(orders.created_at) as d, SUM(order_items.price * order_items.quantity) as gross, SUM(order_items.quantity * order_items.marketplace_share_amount) as share, SUM(order_items.quantity) as units')
                ->orderBy('d')
                ->get();

            // ── Product performance aggregates ──
            $salesAgg = DB::table('order_items')
                ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'products.id', '=', 'product_variants.product_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('products.seller_id', $sellerId)
                ->where('orders.payment_status', 'paid')
                ->where('orders.status', '!=', 'cancelled');
            if ($dateFrom) $salesAgg->where('orders.created_at', '>=', $dateFrom);
            if ($dateTo)   $salesAgg->where('orders.created_at', '<=', Carbon::parse($dateTo)->endOfDay());
            $salesAggRows = $salesAgg
                ->groupBy('products.id', 'products.name', 'products.status')
                ->selectRaw('products.id as product_id, products.name as product_name, products.status as status, SUM(order_items.quantity) as units_sold, SUM(order_items.price * order_items.quantity) as gross, SUM(order_items.quantity * order_items.marketplace_share_amount) as share')
                ->get()->keyBy('product_id');

            $productPerformance = $sellerProducts->map(function ($p) use ($salesAggRows) {
                $row = $salesAggRows->get($p->id);
                $units = (int) ($row->units_sold ?? 0);
                $gross = (float) ($row->gross ?? 0);
                $share = (float) ($row->share ?? 0);
                return [
                    'product' => $p,
                    'units' => $units,
                    'gross' => $gross,
                    'share' => $share,
                    'earnings' => $gross - $share,
                    'stock' => $p->total_stock,
                    'status' => $p->status,
                ];
            })->sortByDesc('gross')->values();

            // ── Recent seller-relevant orders ──
            $recentQuery = DB::table('orders')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'products.id', '=', 'product_variants.product_id')
                ->leftJoin('users', 'users.id', '=', 'orders.user_id')
                ->where('products.seller_id', $sellerId)
                ->where('orders.payment_status', 'paid')
                ->where('orders.status', '!=', 'cancelled');
            if ($dateFrom) $recentQuery->where('orders.created_at', '>=', $dateFrom);
            if ($dateTo)   $recentQuery->where('orders.created_at', '<=', Carbon::parse($dateTo)->endOfDay());
            $recentOrdersRows = $recentQuery
                ->groupBy('orders.id', 'orders.order_number', 'orders.created_at', 'orders.status', 'orders.payment_status', 'users.name')
                ->selectRaw('orders.id as order_id, orders.order_number, orders.created_at, orders.status, orders.payment_status, COALESCE(users.name,"—") as customer_name, SUM(order_items.quantity) as seller_units, SUM(order_items.price * order_items.quantity) as seller_gross, SUM(order_items.quantity * order_items.marketplace_share_amount) as seller_share')
                ->orderByDesc('orders.created_at')
                ->limit(8)
                ->get();
        }

        $periodEarnings = $periodGross - $periodShare;

        // For chart JS: labels + two series
        $chartLabels = $chartRows->pluck('d')->map(fn ($d) => Carbon::parse($d)->format('d M'))->all();
        $chartGross  = $chartRows->pluck('gross')->map(fn ($v) => (float) $v)->all();
        $chartEarnings = $chartRows->map(fn ($r) => (float) $r->gross - (float) $r->share)->all();

        $recent = $sellerProducts->sortByDesc('created_at')->take(5);

        return view('seller.dashboard', compact(
            'stats', 'recent',
            'period', 'dateFrom', 'dateTo',
            'periodGross', 'periodShare', 'periodEarnings', 'periodUnits', 'periodOrders', 'pendingOrders',
            'lowStockCount', 'outOfStockCount', 'lowStockProducts', 'outOfStockProducts',
            'chartRows', 'chartLabels', 'chartGross', 'chartEarnings',
            'productPerformance', 'recentOrdersRows'
        ));
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
