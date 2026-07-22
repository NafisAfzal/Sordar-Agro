<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('items', 'user');
        return view('admin.orders.show', compact('order'));
    }

    /** Update fulfilment status and assign a courier + tracking code. */
    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status'        => ['required', 'in:processing,shipped,delivered,cancelled'],
            'courier'       => ['nullable', 'in:pathao,steadfast'],
            'tracking_code' => ['nullable', 'string', 'max:100'],
        ]);

        // Auto-generate a tracking code on first shipment if none provided.
        if ($data['status'] === 'shipped' && empty($data['tracking_code']) && $data['courier']) {
            $data['tracking_code'] = strtoupper($data['courier']).'-'.strtoupper(Str::random(10));
        }

        $order->update($data);

        return back()->with('success', 'Order updated.');
    }
}
