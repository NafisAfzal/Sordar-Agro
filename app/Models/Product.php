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

    public const REJECTION_REASONS = [
        'profit_share'    => 'Profit Share Amount',
        'price'           => 'Price',
        'quantity'        => 'Quantity',
        'product_quality' => 'Product Quality',
        'other'           => 'Other',
    ];

    protected $fillable = [
        'seller_id', 'category_id', 'name', 'slug', 'description', 'thumbnail',
        'is_fish', 'min_tank_size_litres', 'temperament', 'profit_share_amount',
        'status', 'admin_feedback', 'is_featured', 'rejection_reason_category',
    ];

    protected function casts(): array
    {
        return [
            'is_fish' => 'boolean',
            'is_featured' => 'boolean',
            'profit_share_amount' => 'decimal:2',
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

    /** Units of this product sold across paid orders (any of its variants). */
    public function unitsSold(): int
    {
        return (int) OrderItem::whereHas('variant', fn (Builder $q) => $q->where('product_id', $this->id))
            ->whereHas('order', fn (Builder $q) => $q->where('payment_status', 'paid'))
            ->sum('quantity');
    }

    /** Total marketplace share earned from this product's paid sales, using each sale's snapshotted amount. */
    public function marketplaceShareEarned(): float
    {
        return (float) OrderItem::whereHas('variant', fn (Builder $q) => $q->where('product_id', $this->id))
            ->whereHas('order', fn (Builder $q) => $q->where('payment_status', 'paid'))
            ->selectRaw('COALESCE(SUM(quantity * marketplace_share_amount), 0) as total')
            ->value('total');
    }
}
