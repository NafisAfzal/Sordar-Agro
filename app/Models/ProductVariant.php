<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'size', 'sku', 'price', 'stock', 'size_description',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function inStock(): bool { return $this->stock > 0; }

    /** Human label: fish sizes are title-cased; "standard" hides itself. */
    public function getLabelAttribute(): string
    {
        return $this->size === 'standard' ? 'Standard' : ucfirst($this->size);
    }
}
