<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id', 'category_id', 'name', 'slug', 'description', 'thumbnail',
        'is_fish', 'min_tank_size_litres', 'temperament',
        'status', 'admin_feedback', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'is_fish' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string { return 'slug'; }

    // ---- Relationships ------------------------------------------------
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function seller(): BelongsTo   { return $this->belongsTo(User::class, 'seller_id'); }
    public function variants(): HasMany   { return $this->hasMany(ProductVariant::class); }
    public function images(): HasMany     { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function wishlists(): HasMany  { return $this->hasMany(Wishlist::class); }

    // ---- Scopes -------------------------------------------------------
    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('status', 'approved');
    }

    // ---- Derived helpers ----------------------------------------------
    /** Lowest variant price, used for "from ৳X" display. */
    public function getStartingPriceAttribute(): float
    {
        return (float) ($this->variants->min('price') ?? 0);
    }

    /** Total stock across every variant. */
    public function getTotalStockAttribute(): int
    {
        return (int) $this->variants->sum('stock');
    }

    public function isOutOfStock(): bool
    {
        return $this->total_stock <= 0;
    }
}
