@extends('layouts.app')
@section('title', 'Payment')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-4">
                @php $brand = $order->payment_method === 'bkash' ? ['bKash', 'danger'] : ['Nagad', 'warning']; @endphp
                <div class="display-6 text-{{ $brand[1] }} fw-bold mb-2">{{ $brand[0] }}</div>
                <p class="text-muted">Simulated payment gateway</p>

                <div class="border rounded p-3 mb-3 bg-light">
                    <div class="d-flex justify-content-between"><span>Order</span><strong>{{ $order->order_number }}</strong></div>
                    <div class="d-flex justify-content-between"><span>Amount</span><strong>৳{{ number_format($order->total, 2) }}</strong></div>
                </div>

                <p class="small text-muted">
                    In a live integration this screen would redirect to {{ $brand[0] }}'s hosted page.
                    Here, choose an outcome to simulate the provider callback.
                </p>

                <form method="POST" action="{{ route('payment.process', $order) }}" class="d-grid gap-2">
                    @csrf
                    <button name="outcome" value="success" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle"></i> Simulate successful payment
                    </button>
                    <button name="outcome" value="failure" class="btn btn-outline-danger">
                        <i class="bi bi-x-circle"></i> Simulate failed payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
