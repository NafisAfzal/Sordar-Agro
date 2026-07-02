<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $table = 'carts';

    protected $fillable = ['user_id', 'product_variant_id', 'quantity'];

    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }

    /** Line subtotal = unit price x quantity. */
    public function subtotal(): float
    {
        return (float) $this->variant->price * $this->quantity;
    }
}
