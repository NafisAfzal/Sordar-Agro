<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'total', 'status',
        'payment_method', 'payment_status', 'transaction_id',
        'shipping_name', 'shipping_phone', 'shipping_address',
        'courier', 'tracking_code',
    ];

    protected function casts(): array
    {
        return ['total' => 'decimal:2'];
    }

    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function items(): HasMany   { return $this->hasMany(OrderItem::class); }

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
}
