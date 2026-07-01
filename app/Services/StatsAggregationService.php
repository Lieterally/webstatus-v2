<?php

namespace App\Services;

use App\Models\CheckResult;
use App\Models\DailyStat;
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
