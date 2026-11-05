<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Dedicated queue for admin payment verification. "Pending" means a
        // TrxID has been submitted but the money hasn't been confirmed yet.
        $pendingPayment = $request->query('payment') === 'pending';

        if ($pendingPayment) {
            $query->where('payment_status', Order::PAYMENT_UNPAID)
                ->whereNotNull('transaction_id')
                ->where('status', '!=', 'cancelled');
        }

        $orders = $query->paginate(15)->withQueryString();

        $pendingPaymentCount = Order::where('payment_status', Order::PAYMENT_UNPAID)
            ->whereNotNull('transaction_id')
            ->where('status', '!=', 'cancelled')
            ->count();

        return view('admin.orders.index', compact('orders', 'pendingPaymentCount', 'pendingPayment'));
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load('items', 'user', 'paymentVerifier');

        return view('admin.orders.show', compact('order'));
    }

    /** Update fulfilment status and assign a courier + tracking code. */
    public function update(UpdateOrderRequest $request, Order $order, OrderService $orders)
    {
        $this->authorize('update', $order);

        $orders->updateFulfillment($order, $request->validated(), auth()->id());

        return back()->with('success', 'Order updated.');
    }

    /** Confirm that the customer's reported TrxID really was paid. */
    public function verifyPayment(Order $order, OrderService $orders)
    {
        $this->authorize('verifyPayment', $order);

        if (! $orders->verifyPayment($order, auth()->id())) {
            return back()->with('error', 'This order is not awaiting payment verification.');
        }

        return back()->with('success', 'Payment verified and order marked as paid.');
    }

    /** Reject an unverified payment and release the reserved stock. */
    public function rejectPayment(Order $order, OrderService $orders)
    {
        $this->authorize('rejectPayment', $order);

        if (! $orders->rejectPayment($order, auth()->id())) {
            return back()->with('error', 'This order is not awaiting payment verification.');
        }

        return back()->with('success', 'Payment rejected and reserved stock released.');
    }
}
