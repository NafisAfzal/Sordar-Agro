<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareGuide extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'image', 'author_id', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function getRouteKeyName(): string { return 'slug'; }

    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }
}
