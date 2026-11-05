<?php

namespace App\Observers;

use App\Models\CareGuide;
use App\Models\Product;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalidates versioned read-model caches whenever source data changes.
 * Registered against the models that feed the dashboards and sitemap.
 */
class CacheInvalidationObserver
{
    public function __construct(private readonly CacheService $cache) {}

    public function saved(Model $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        $this->cache->invalidateDashboards();

        if ($model instanceof Product || $model instanceof CareGuide) {
            $this->cache->invalidateSitemap();
        }
    }
}
