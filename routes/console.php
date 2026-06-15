<?php

use App\Models\SystemConfig;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
|
| The monitoring cycle runs at the configured interval (default 10 minutes).
| The scheduler runs every minute via cron, and we use everyMinute() combined
| with a custom "when" constraint to check the elapsed time against the
| configured cycle interval. This ensures the first cycle triggers within
| 60 seconds of system startup (Req 1.1).
|
*/

Schedule::command('app:run-monitoring-cycle')
    ->everyMinute()
    ->when(function () {
        $intervalMinutes = (int) SystemConfig::getValue('cycle_interval_minutes', '10');
        $lastRunValue = SystemConfig::getValue('last_cycle_run_at');

        // If no cycle has ever run, trigger immediately (within 60s of startup - Req 1.1)
        if ($lastRunValue === null) {
            return true;
        }

        $lastRun = \Illuminate\Support\Carbon::parse($lastRunValue);
        $elapsedMinutes = (int) abs(now()->diffInMinutes($lastRun));

        return $elapsedMinutes >= $intervalMinutes;
    })
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer();
