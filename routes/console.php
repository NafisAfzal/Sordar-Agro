<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hourly: release reserved stock for orders whose TrxID was never verified
// within the configured TTL (default 24 hours, config: orders.reservation_ttl_hours).
Schedule::command('orders:release-stale-reservations')->hourly();

// Hourly: check queue health and log errors when failed jobs are detected.
Schedule::command('queue:check-health')->hourly();
