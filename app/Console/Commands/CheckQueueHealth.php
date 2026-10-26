<?php

namespace App\Console\Commands;

use App\Services\HealthCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled queue health check. Logs an error when failed_jobs count > 0,
 * indicating a stuck worker or downstream failure. Works with the database
 * driver in production and degrades gracefully with the sync driver in tests.
 */
class CheckQueueHealth extends Command
{
    protected $signature = 'queue:check-health';

    protected $description = 'Check queue health and log errors when failed jobs are detected';

    public function handle(): int
    {
        $result = HealthCheck::check();

        if ($result['healthy']) {
            $this->info('Queue and application health: OK.');

            return self::SUCCESS;
        }

        foreach ($result['warnings'] as $warning) {
            Log::error("Health check: {$warning}");
            $this->warn($warning);
        }

        return self::FAILURE;
    }
}
