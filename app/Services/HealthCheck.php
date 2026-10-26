<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Application health checks used by the queue:check-health command and the
 * /up health route listener. The framework handles framework-level health at
 * /up; this class covers application health: failed jobs and stale payment
 * reservations.
 */
class HealthCheck
{
    public const CACHE_KEY = 'health:failed_jobs:timestamp';

    public const CACHE_COUNT_KEY = 'health:failed_jobs:count';

    /**
     * Run all checks and return an associative status array.
     * Returns ['healthy' => false, 'warnings' => [...]] when degraded.
     */
    public static function check(): array
    {
        $warnings = [];

        $failureCount = self::failedJobCount();
        $previousFailures = self::previousFailureCount();
        $lastRunAt = self::previousRunAt();

        self::storeFailureSnapshot($failureCount);

        if ($failureCount > 0) {
            $warnings[] = "queue: {$failureCount} job(s) have failed and are pending re-processing";
            Log::warning('queue degraded', [
                'failed_jobs' => $failureCount,
                'previous_run_at' => $lastRunAt,
            ]);
        }

        $growth = $failureCount - $previousFailures;

        if ($growth > 0 && $lastRunAt !== null) {
            Log::warning('failed jobs grew', [
                'failed_jobs' => $failureCount,
                'previous_jobs' => $previousFailures,
                'growth' => $growth,
            ]);
            $warnings[] = "queue: failed jobs grew by {$growth} since last check";
        }

        $stale = self::staleReservationCount();

        if ($stale > 0) {
            $warnings[] = "reservations: {$stale} TrxID reservation(s) past TTL — ensure orders:release-stale-reservations is scheduled";
            Log::warning('stale payment reservations', ['count' => $stale]);
        }

        return [
            'healthy' => empty($warnings),
            'warnings' => $warnings,
            'jobs' => [
                'failed' => $failureCount,
                'previous' => $previousFailures,
            ],
            'reservations' => [
                'stale' => $stale,
            ],
        ];
    }

    /** Number of rows in the failed_jobs table; safe when the table is absent. */
    private static function failedJobCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    /** Timestamp of this check's last run, stored in cache. */
    private static function previousRunAt(): ?string
    {
        return cache()->get(self::CACHE_KEY);
    }

    /** Failed-job count captured on the previous check. */
    private static function previousFailureCount(): int
    {
        return (int) cache()->get(self::CACHE_COUNT_KEY, 0);
    }

    /** Snapshot this run's failure count and timestamp for comparison next time. */
    private static function storeFailureSnapshot(int $failedCount): void
    {
        cache()->put(self::CACHE_KEY, now()->toDateTimeString(), 3600);
        cache()->put(self::CACHE_COUNT_KEY, $failedCount, 3600);
    }

    /** Orders that submitted a TrxID but are still unverified after the TTL. */
    private static function staleReservationCount(): int
    {
        if (! Schema::hasTable('orders')) {
            return 0;
        }

        return (int) DB::table('orders')
            ->where('payment_status', 'unpaid')
            ->whereNotNull('transaction_id')
            ->where('status', '!=', 'cancelled')
            ->where('payment_submitted_at', '<', now()->subHours(
                (int) config('orders.reservation_ttl_hours', 24)
            ))
            ->count();
    }
}
