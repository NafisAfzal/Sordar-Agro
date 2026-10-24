<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';

    protected $fillable = [
        'order_number', 'user_id', 'total', 'status',
        'payment_method', 'payment_status', 'transaction_id',
        'payment_submitted_at', 'payment_verified_at', 'payment_verified_by',
        'shipping_name', 'shipping_phone', 'shipping_address',
        'courier', 'tracking_code',
    ];

    protected function casts(): array
    {
        return [
            'total'                => 'decimal:2',
            'payment_submitted_at' => 'datetime',
            'payment_verified_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function items(): HasMany   { return $this->hasMany(OrderItem::class); }
    public function paymentVerifier(): BelongsTo { return $this->belongsTo(User::class, 'payment_verified_by'); }

    /** Bootstrap badge colour per status, used in views. */
    public function statusColor(): string
    {
        return match ($this->status) {
            'processing' => 'warning',
            'shipped'    => 'info',
            'delivered'  => 'success',
            'cancelled'  => 'danger',
            default      => 'secondary',
        };
    }

    /**
     * A transaction ID has been supplied but an admin has not yet confirmed
     * that the money actually arrived. Stock is reserved while in this state.
     */
    public function isAwaitingPaymentVerification(): bool
    {
        return $this->payment_status === self::PAYMENT_UNPAID
            && $this->transaction_id !== null;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    /** Human label for the payment state shown in order views. */
    public function paymentStatusLabel(): string
    {
        return match (true) {
            $this->isPaid()                        => 'Paid',
            $this->isAwaitingPaymentVerification() => 'Awaiting verification',
            $this->payment_status === self::PAYMENT_FAILED => 'Payment rejected',
            default                                => 'Unpaid',
        };
    }

    /** Bootstrap badge colour matching {@see paymentStatusLabel()}. */
    public function paymentStatusColor(): string
    {
        return match (true) {
            $this->isPaid()                        => 'success',
            $this->isAwaitingPaymentVerification() => 'warning',
            $this->payment_status === self::PAYMENT_FAILED => 'danger',
            default                                => 'secondary',
        };
    }
}
