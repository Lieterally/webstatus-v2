<?php

namespace App\Services;

use App\Models\CheckResult;
use App\Models\DailyStat;
use App\Models\DowntimeHistory;
use App\Models\HourlyStat;
use App\Models\MonthlyStat;
use App\Models\Site;
use App\Models\WeeklyStat;
use Illuminate\Support\Carbon;

class StatsAggregationService
{
    /**
     * Update all rollup stats for the current time periods.
     * Called after every monitoring cycle completes.
     */
    public function updateAllCurrentStats(): void
    {
        $sites = Site::all();

        foreach ($sites as $site) {
            $this->updateHourlyForSite($site->id);
            $this->updateDailyForSite($site->id);
            $this->updateWeeklyForSite($site->id);
            $this->updateMonthlyForSite($site->id);
        }
    }

    /**
     * Backfill all rollup tables from existing raw check_results data.
     * Processes every hour, day, week, and month that has raw data.
     *
     * @param callable|null $progress Optional callback for progress reporting: fn(string $message)
     */
    public function backfillAll(?callable $progress = null): void
    {
        $sites = Site::all();

        $log = fn(string $msg) => $progress ? ($progress)($msg) : null;

        // Determine the earliest check result date
        $earliest = CheckResult::min('checked_at');
        if (!$earliest) {
            $log('No check results found. Nothing to backfill.');
            return;
        }

        $earliest = Carbon::parse($earliest);
        $now = Carbon::now();

        $log("Backfilling from {$earliest->toDateTimeString()} to now...");

        // --- Hourly backfill ---
        $log('Backfilling hourly stats...');
        $hourlyStart = $earliest->copy()->startOfHour();
        $hoursTotal = (int) $hourlyStart->diffInHours($now->copy()->startOfHour()) + 1;
        $hoursProcessed = 0;

        while ($hourlyStart->lte($now)) {
            $periodStart = $hourlyStart->copy();
            $periodEnd = $periodStart->copy()->addHour();

            foreach ($sites as $site) {
                $results = CheckResult::where('site_id', $site->id)
                    ->where('checked_at', '>=', $periodStart)
                    ->where('checked_at', '<', $periodEnd)
                    ->get();

                if ($results->isEmpty()) {
                    continue;
                }

                $successfulResults = $results->where('http_code', '>', 0);
                $avgResponseTime = $successfulResults->isNotEmpty()
                    ? $successfulResults->avg('response_time_ms')
                    : 0;

                $downtimeSeconds = $this->calculateDowntimeForPeriod($site->id, $periodStart, $periodEnd);

                HourlyStat::updateOrCreate(
                    ['site_id' => $site->id, 'period_start' => $periodStart],
                    [
                        'avg_response_time_ms' => round($avgResponseTime, 2),
                        'downtime_seconds' => $downtimeSeconds,
                        'checks_count' => $results->count(),
                    ]
                );
            }

            $hoursProcessed++;
            if ($hoursProcessed % 24 === 0) {
                $log("  Hourly: {$hoursProcessed}/{$hoursTotal} hours processed...");
            }

            $hourlyStart->addHour();
        }

        $log("  Hourly backfill complete ({$hoursProcessed} hours).");

        // --- Daily backfill (aggregated from hourly) ---
        $log('Backfilling daily stats...');
        $dailyStart = $earliest->copy()->startOfDay();
        $daysTotal = (int) $dailyStart->diffInDays($now->copy()->startOfDay()) + 1;
        $daysProcessed = 0;

        while ($dailyStart->lte($now)) {
            $dayStart = $dailyStart->copy();
            $dayEnd = $dayStart->copy()->endOfDay();

            foreach ($sites as $site) {
                $hourlyRecords = HourlyStat::where('site_id', $site->id)
                    ->where('period_start', '>=', $dayStart)
                    ->where('period_start', '<=', $dayEnd)
                    ->get();

                if ($hourlyRecords->isEmpty()) {
                    continue;
                }

                $totalChecks = $hourlyRecords->sum('checks_count');
                $weightedResponseTime = $hourlyRecords->sum(fn($r) => $r->avg_response_time_ms * $r->checks_count);
                $avgResponseTime = $totalChecks > 0 ? $weightedResponseTime / $totalChecks : 0;
                $totalDowntime = $hourlyRecords->sum('downtime_seconds');

                DailyStat::updateOrCreate(
                    ['site_id' => $site->id, 'period_start' => $dayStart->toDateString()],
                    [
                        'avg_response_time_ms' => round($avgResponseTime, 2),
                        'downtime_seconds' => $totalDowntime,
                        'checks_count' => $totalChecks,
                    ]
                );
            }

            $daysProcessed++;
            $dailyStart->addDay();
        }

        $log("  Daily backfill complete ({$daysProcessed} days).");

        // --- Weekly backfill (aggregated from daily) ---
        $log('Backfilling weekly stats...');
        $weeklyStart = $earliest->copy()->startOfWeek();
        $weeksProcessed = 0;

        while ($weeklyStart->lte($now)) {
            $weekStart = $weeklyStart->copy();
            $weekEnd = $weekStart->copy()->endOfWeek();

            foreach ($sites as $site) {
                $dailyRecords = DailyStat::where('site_id', $site->id)
                    ->where('period_start', '>=', $weekStart->toDateString())
                    ->where('period_start', '<=', $weekEnd->toDateString())
                    ->get();

                if ($dailyRecords->isEmpty()) {
                    continue;
                }

                $totalChecks = $dailyRecords->sum('checks_count');
                $weightedResponseTime = $dailyRecords->sum(fn($r) => $r->avg_response_time_ms * $r->checks_count);
                $avgResponseTime = $totalChecks > 0 ? $weightedResponseTime / $totalChecks : 0;
                $totalDowntime = $dailyRecords->sum('downtime_seconds');

                WeeklyStat::updateOrCreate(
                    ['site_id' => $site->id, 'period_start' => $weekStart->toDateString()],
                    [
                        'avg_response_time_ms' => round($avgResponseTime, 2),
                        'downtime_seconds' => $totalDowntime,
                        'checks_count' => $totalChecks,
                    ]
                );
            }

            $weeksProcessed++;
            $weeklyStart->addWeek();
        }

        $log("  Weekly backfill complete ({$weeksProcessed} weeks).");

        // --- Monthly backfill (aggregated from daily) ---
        $log('Backfilling monthly stats...');
        $monthlyStart = $earliest->copy()->startOfMonth();
        $monthsProcessed = 0;

        while ($monthlyStart->lte($now)) {
            $monthStart = $monthlyStart->copy();
            $monthEnd = $monthStart->copy()->endOfMonth();

            foreach ($sites as $site) {
                $dailyRecords = DailyStat::where('site_id', $site->id)
                    ->where('period_start', '>=', $monthStart->toDateString())
                    ->where('period_start', '<=', $monthEnd->toDateString())
                    ->get();

                if ($dailyRecords->isEmpty()) {
                    continue;
                }

                $totalChecks = $dailyRecords->sum('checks_count');
                $weightedResponseTime = $dailyRecords->sum(fn($r) => $r->avg_response_time_ms * $r->checks_count);
                $avgResponseTime = $totalChecks > 0 ? $weightedResponseTime / $totalChecks : 0;
                $totalDowntime = $dailyRecords->sum('downtime_seconds');

                MonthlyStat::updateOrCreate(
                    ['site_id' => $site->id, 'period_start' => $monthStart->toDateString()],
                    [
                        'avg_response_time_ms' => round($avgResponseTime, 2),
                        'downtime_seconds' => $totalDowntime,
                        'checks_count' => $totalChecks,
                    ]
                );
            }

            $monthsProcessed++;
            $monthlyStart->addMonth();
        }

        $log("  Monthly backfill complete ({$monthsProcessed} months).");

        // --- Downtime history backfill ---
        $log('Backfilling downtime histories...');
        $sites = Site::all(); // refresh for this section

        foreach ($sites as $site) {
            // Delete any existing backfilled records for this site to avoid duplicates
            DowntimeHistory::where('site_id', $site->id)->delete();

            $results = CheckResult::where('site_id', $site->id)
                ->orderBy('checked_at')
                ->select('cycle_id', 'page_id', 'http_code', 'checked_at')
                ->get();

            if ($results->isEmpty()) {
                continue;
            }

            $pages = $site->pages->pluck('path', 'id')->toArray();

            // Group by cycle
            $cycles = $results->groupBy('cycle_id');
            $cycleEntries = [];

            foreach ($cycles as $cycleResults) {
                $downPages = [];
                foreach ($cycleResults as $result) {
                    $code = $result->http_code;
                    if ($code === 0 || $code < 200 || $code >= 400) {
                        $downPages[] = $pages[$result->page_id] ?? '/unknown';
                    }
                }

                $cycleEntries[] = [
                    'time' => Carbon::parse($cycleResults->first()->checked_at),
                    'down' => !empty($downPages),
                    'downPages' => array_unique($downPages),
                ];
            }

            usort($cycleEntries, fn($a, $b) => $a['time']->timestamp - $b['time']->timestamp);

            $outageStart = null;
            $outagePages = [];

            foreach ($cycleEntries as $entry) {
                if ($entry['down'] && $outageStart === null) {
                    $outageStart = $entry['time'];
                    $outagePages = $entry['downPages'];
                } elseif ($entry['down'] && $outageStart !== null) {
                    $outagePages = array_values(array_unique(array_merge($outagePages, $entry['downPages'])));
                } elseif (!$entry['down'] && $outageStart !== null) {
                    $endedAt = $entry['time'];
                    DowntimeHistory::create([
                        'site_id' => $site->id,
                        'started_at' => $outageStart,
                        'ended_at' => $endedAt,
                        'duration_seconds' => (int) $outageStart->diffInSeconds($endedAt),
                        'affected_pages' => array_values($outagePages),
                        'status' => 'resolved',
                    ]);
                    $outageStart = null;
                    $outagePages = [];
                }
            }

            // If still in outage at end of data
            if ($outageStart !== null) {
                DowntimeHistory::create([
                    'site_id' => $site->id,
                    'started_at' => $outageStart,
                    'ended_at' => null,
                    'duration_seconds' => null,
                    'affected_pages' => array_values($outagePages),
                    'status' => 'active',
                ]);
            }
        }

        $log('  Downtime history backfill complete.');
        $log('Backfill finished.');
    }

    /**
     * Update hourly stats for the current hour for a given site.
     */
    private function updateHourlyForSite(int $siteId): void
    {
        $periodStart = Carbon::now()->startOfHour();
        $periodEnd = $periodStart->copy()->addHour();

        $results = CheckResult::where('site_id', $siteId)
            ->where('checked_at', '>=', $periodStart)
            ->where('checked_at', '<', $periodEnd)
            ->get();

        if ($results->isEmpty()) {
            return;
        }

        $successfulResults = $results->where('http_code', '>', 0);
        $avgResponseTime = $successfulResults->isNotEmpty()
            ? $successfulResults->avg('response_time_ms')
            : 0;

        $downtimeSeconds = $this->calculateDowntimeForPeriod($siteId, $periodStart, $periodEnd);

        HourlyStat::updateOrCreate(
            ['site_id' => $siteId, 'period_start' => $periodStart],
            [
                'avg_response_time_ms' => round($avgResponseTime, 2),
                'downtime_seconds' => $downtimeSeconds,
                'checks_count' => $results->count(),
            ]
        );
    }

    /**
     * Update daily stats for today for a given site.
     * Aggregates from all hourly records of the current day.
     */
    private function updateDailyForSite(int $siteId): void
    {
        $dayStart = Carbon::today();
        $dayEnd = $dayStart->copy()->endOfDay();

        $hourlyRecords = HourlyStat::where('site_id', $siteId)
            ->where('period_start', '>=', $dayStart)
            ->where('period_start', '<=', $dayEnd)
            ->get();

        if ($hourlyRecords->isEmpty()) {
            return;
        }

        $totalChecks = $hourlyRecords->sum('checks_count');
        $weightedResponseTime = $hourlyRecords->sum(fn($r) => $r->avg_response_time_ms * $r->checks_count);
        $avgResponseTime = $totalChecks > 0 ? $weightedResponseTime / $totalChecks : 0;
        $totalDowntime = $hourlyRecords->sum('downtime_seconds');

        DailyStat::updateOrCreate(
            ['site_id' => $siteId, 'period_start' => $dayStart->toDateString()],
            [
                'avg_response_time_ms' => round($avgResponseTime, 2),
                'downtime_seconds' => $totalDowntime,
                'checks_count' => $totalChecks,
            ]
        );
    }

    /**
     * Update weekly stats for the current week for a given site.
     * Aggregates from all daily records of the current week.
     */
    private function updateWeeklyForSite(int $siteId): void
    {
        $weekStart = Carbon::now()->startOfWeek(); // Monday
        $weekEnd = $weekStart->copy()->endOfWeek();

        $dailyRecords = DailyStat::where('site_id', $siteId)
            ->where('period_start', '>=', $weekStart->toDateString())
            ->where('period_start', '<=', $weekEnd->toDateString())
            ->get();

        if ($dailyRecords->isEmpty()) {
            return;
        }

        $totalChecks = $dailyRecords->sum('checks_count');
        $weightedResponseTime = $dailyRecords->sum(fn($r) => $r->avg_response_time_ms * $r->checks_count);
        $avgResponseTime = $totalChecks > 0 ? $weightedResponseTime / $totalChecks : 0;
        $totalDowntime = $dailyRecords->sum('downtime_seconds');

        WeeklyStat::updateOrCreate(
            ['site_id' => $siteId, 'period_start' => $weekStart->toDateString()],
            [
                'avg_response_time_ms' => round($avgResponseTime, 2),
                'downtime_seconds' => $totalDowntime,
                'checks_count' => $totalChecks,
            ]
        );
    }

    /**
     * Update monthly stats for the current month for a given site.
     * Aggregates from all daily records of the current month.
     */
    private function updateMonthlyForSite(int $siteId): void
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $dailyRecords = DailyStat::where('site_id', $siteId)
            ->where('period_start', '>=', $monthStart->toDateString())
            ->where('period_start', '<=', $monthEnd->toDateString())
            ->get();

        if ($dailyRecords->isEmpty()) {
            return;
        }

        $totalChecks = $dailyRecords->sum('checks_count');
        $weightedResponseTime = $dailyRecords->sum(fn($r) => $r->avg_response_time_ms * $r->checks_count);
        $avgResponseTime = $totalChecks > 0 ? $weightedResponseTime / $totalChecks : 0;
        $totalDowntime = $dailyRecords->sum('downtime_seconds');

        MonthlyStat::updateOrCreate(
            ['site_id' => $siteId, 'period_start' => $monthStart->toDateString()],
            [
                'avg_response_time_ms' => round($avgResponseTime, 2),
                'downtime_seconds' => $totalDowntime,
                'checks_count' => $totalChecks,
            ]
        );
    }

    /**
     * Calculate downtime seconds for a site within a given time period.
     * Uses cycle-based outage detection from raw check_results.
     */
    private function calculateDowntimeForPeriod(int $siteId, Carbon $start, Carbon $end): int
    {
        $results = CheckResult::where('site_id', $siteId)
            ->where('checked_at', '>=', $start)
            ->where('checked_at', '<', $end)
            ->orderBy('checked_at')
            ->select('cycle_id', 'http_code', 'checked_at')
            ->get();

        if ($results->isEmpty()) {
            return 0;
        }

        $cycles = $results->groupBy('cycle_id');
        $cycleTimestamps = [];

        foreach ($cycles as $cycleId => $cycleResults) {
            $someDown = $cycleResults->contains(function ($result) {
                $code = $result->http_code;
                return $code === 0 || $code < 200 || $code >= 400;
            });

            $cycleTimestamps[] = [
                'time' => Carbon::parse($cycleResults->first()->checked_at),
                'down' => $someDown,
            ];
        }

        usort($cycleTimestamps, fn($a, $b) => $a['time']->timestamp - $b['time']->timestamp);

        $downtimeSeconds = 0;
        $outageStart = null;

        foreach ($cycleTimestamps as $current) {
            if ($current['down'] && $outageStart === null) {
                $outageStart = $current['time'];
            } elseif (!$current['down'] && $outageStart !== null) {
                $downtimeSeconds += $outageStart->diffInSeconds($current['time']);
                $outageStart = null;
            }
        }

        if ($outageStart !== null) {
            $closeTime = Carbon::now()->lt($end) ? Carbon::now() : $end;
            $downtimeSeconds += $outageStart->diffInSeconds($closeTime);
        }

        return $downtimeSeconds;
    }
}
