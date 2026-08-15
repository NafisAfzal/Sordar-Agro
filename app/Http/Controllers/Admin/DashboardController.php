<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunitySubmission;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

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
        ];

        $recentOrders = Order::with('user')->latest()->take(8)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }
}
