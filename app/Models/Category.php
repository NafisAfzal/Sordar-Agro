<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'parent_id'];

    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function parent(): BelongsTo { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(Category::class, 'parent_id'); }

    public function getRouteKeyName(): string { return 'slug'; }

    /** Bootstrap Icons class used for this category's thumbnail placeholders and cards. */
    public function getIconClassAttribute(): string
    {
        return match ($this->slug) {
            'fish' => 'bi-water',
            'aquatic-plants' => 'bi-flower1',
            'fish-food' => 'bi-box-seam',
            'equipment' => 'bi-boxes',
            default => 'bi-water',
        };
    }
}
