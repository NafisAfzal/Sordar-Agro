@extends('layouts.app')
@section('title', 'Complete Payment')
@section('content')
@php
    $isBkash = $order->payment_method === 'bkash';
    $brand   = $isBkash ? 'bKash' : 'Nagad';
    $colour  = $isBkash ? 'danger' : 'warning';
    $number  = config('app.payment_number');
@endphp

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <div class="text-center mb-4">
                    <h3 class="display-6 fw-bold text-{{ $colour }}">{{ $brand }}</h3>
                    <p class="text-muted mb-0">Complete your payment to confirm the order</p>
                </div>

                <div class="border rounded p-3 bg-light mb-4">
                    <div class="d-flex justify-content-between"><span>Order</span><strong>{{ $order->order_number }}</strong></div>
                    <div class="d-flex justify-content-between fs-5">
                        <span>Amount to send</span>
                        <strong class="text-{{ $colour }}">৳{{ number_format($order->total, 2) }}</strong>
                    </div>
                </div>

                <div class="row g-4 align-items-center mb-4">
                    <div class="col-md-5 text-center">
                        @if (file_exists(public_path('img/payment-qr.png')))
                            <img src="{{ asset('img/payment-qr.png') }}" alt="Payment QR code"
                                 class="img-fluid rounded border p-2 bg-white" style="max-width:200px;">
                        @else
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($number) }}"
                                 alt="QR code containing the payment number"
                                 class="img-fluid rounded border p-2 bg-white" style="max-width:200px;">
                        @endif
                        <p class="small fw-semibold mt-2 mb-0 text-sa">Scan with your phone camera to copy the number</p>
                    </div>

                    <div class="col-md-7">
                        <p class="mb-1 text-muted small">Or send money to this number:</p>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="fs-4 fw-bold" id="payNumber">{{ $number }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="copyNumber" title="Copy number">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>

                        <h6 class="fw-bold">How to pay</h6>
                        <ol class="small mb-0 ps-3">
                            <li class="mb-1">Open your <strong>{{ $brand }}</strong> app.</li>
                            <li class="mb-1">Choose <strong>Send Money</strong>.</li>
                            <li class="mb-1">Scan the QR, or type the number <strong>{{ $number }}</strong>.</li>
                            <li class="mb-1">Send exactly <strong>৳{{ number_format($order->total, 2) }}</strong>.</li>
                            <li class="mb-1">Copy the <strong>Transaction ID</strong> (TrxID) from the confirmation message.</li>
                            <li>Enter it below and submit.</li>
                        </ol>
                    </div>
                </div>

                <hr>

                <form method="POST" action="{{ route('payment.process', $order) }}">
                    @csrf
                    <label class="form-label fw-semibold">Transaction ID (TrxID)</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                        <input type="text" name="transaction_id" class="form-control @error('transaction_id') is-invalid @enderror"
                               placeholder="e.g. 9F7A2K1B3C" value="{{ old('transaction_id') }}" required>
                    </div>
                    @error('transaction_id')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror
                    <div class="alert alert-warning small fw-semibold mb-2">
                        <i class="bi bi-exclamation-circle"></i>
                        Enter the exact TrxID from your {{ $brand }} confirmation SMS. Your order is confirmed once submitted
                        and verified by our team.
                    </div>
                    <button class="btn btn-sa btn-lg w-100">
                        <i class="bi bi-check-circle"></i> Submit payment details
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('copyNumber')?.addEventListener('click', function () {
        const n = document.getElementById('payNumber').textContent.trim();
        navigator.clipboard?.writeText(n);
        this.innerHTML = '<i class="bi bi-check2"></i>';
        setTimeout(() => { this.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
    });
</script>
@endpush