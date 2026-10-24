<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
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
        $order->load('items', 'user', 'paymentVerifier');
        return view('admin.orders.show', compact('order'));
    }

    /** Update fulfilment status and assign a courier + tracking code. */
    public function update(Request $request, Order $order, InventoryService $inventory)
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

        $releasedStock = false;

        DB::transaction(function () use ($order, &$data, &$releasedStock) {
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            // Cancelling an order must return the stock reserved when the
            // payment was submitted (or when it was verified).
            if ($data['status'] === 'cancelled'
                && $locked->status !== 'cancelled'
                && ($locked->isPaid() || $locked->isAwaitingPaymentVerification())) {
                $releasedStock = true;

                foreach ($locked->items()->get() as $item) {
                    $variant = $item->variant()->lockForUpdate()->first();

                    if ($variant) {
                        $variant->increment('stock', $item->quantity);
                    }
                }

                // A pending payment that never completed is no longer pending.
                if ($locked->isAwaitingPaymentVerification()) {
                    $data['payment_status']       = Order::PAYMENT_FAILED;
                    $data['payment_verified_at']  = now();
                    $data['payment_verified_by']  = auth()->id();
                }
            }

            $locked->update($data);
        });

        if ($releasedStock) {
            $this->notifyRestockedProducts($order, $inventory);
        }

        return back()->with('success', 'Order updated.');
    }

    /** Confirm that the customer's reported TrxID really was paid. */
    public function verifyPayment(Order $order)
    {
        $updated = Order::whereKey($order->getKey())
            ->where('payment_status', Order::PAYMENT_UNPAID)
            ->whereNotNull('transaction_id')
            ->where('status', '!=', 'cancelled')
            ->update([
                'payment_status'      => Order::PAYMENT_PAID,
                'payment_verified_at' => now(),
                'payment_verified_by' => auth()->id(),
            ]);

        if (! $updated) {
            return back()->with('error', 'This order is not awaiting payment verification.');
        }

        return back()->with('success', 'Payment verified and order marked as paid.');
    }

    /** Reject an unverified payment and release the reserved stock. */
    public function rejectPayment(Order $order, InventoryService $inventory)
    {
        try {
            DB::transaction(function () use ($order) {
                $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

                if (! $locked->isAwaitingPaymentVerification() || $locked->status === 'cancelled') {
                    throw new \LogicException('not_awaiting_verification');
                }

                foreach ($locked->items()->get() as $item) {
                    $variant = $item->variant()->lockForUpdate()->first();

                    if ($variant) {
                        $variant->increment('stock', $item->quantity);
                    }
                }

                $locked->forceFill([
                    'payment_status'      => Order::PAYMENT_FAILED,
                    'payment_verified_at' => now(),
                    'payment_verified_by' => auth()->id(),
                ])->save();
            });
        } catch (\LogicException $e) {
            return back()->with('error', 'This order is not awaiting payment verification.');
        }

        $this->notifyRestockedProducts($order, $inventory);

        return back()->with('success', 'Payment rejected and reserved stock released.');
    }

    /** Send back-in-stock notifications for products whose stock increased. */
    private function notifyRestockedProducts(Order $order, InventoryService $inventory): void
    {
        $products = $order->items()->with('variant.product')->get()
            ->pluck('variant.product')
            ->filter()
            ->unique('id');

        foreach ($products as $product) {
            $inventory->handleRestock($product);
        }
    }
}
