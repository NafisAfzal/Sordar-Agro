<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $sellerId = auth()->id();

        $stats = [
            'total'    => Product::where('seller_id', $sellerId)->count(),
            'approved' => Product::where('seller_id', $sellerId)->where('status', 'approved')->count(),
            'pending'  => Product::where('seller_id', $sellerId)->where('status', 'pending')->count(),
            'rejected' => Product::where('seller_id', $sellerId)->where('status', 'rejected')->count(),
        ];

        $recent = Product::where('seller_id', $sellerId)->latest()->take(5)->get();

        return view('seller.dashboard', compact('stats', 'recent'));
    }
}
