<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache keys for expensive read models.
 *
 * Cache tags are not available on the file/database stores used in
 * production, so a monotonically increasing version is folded into every
 * dashboard key. A single bump invalidates all dashboard variants at once.
 */
class CacheService
{
    public const SITEMAP_KEY = 'sitemap.xml.urls.v2';

    private const DASHBOARD_VERSION_KEY = 'dashboard.cache.version';

    /** Build a cache key for one dashboard variant. */
    public function dashboardKey(string $scope, array $context = []): string
    {
        return $scope.'.'.sha1(json_encode([
            'version' => $this->dashboardVersion(),
            'context' => $context,
        ]));
    }

    /** Invalidate every cached dashboard variant. */
    public function invalidateDashboards(): void
    {
        if (Cache::add(self::DASHBOARD_VERSION_KEY, 2, now()->addYears(10))) {
            return;
        }

        Cache::increment(self::DASHBOARD_VERSION_KEY);
    }

    /** Invalidate the public XML sitemap. */
    public function invalidateSitemap(): void
    {
        Cache::forget(self::SITEMAP_KEY);
    }

    private function dashboardVersion(): int
    {
        return (int) Cache::get(self::DASHBOARD_VERSION_KEY, 1);
    }
}
