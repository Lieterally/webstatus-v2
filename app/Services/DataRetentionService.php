<?php

namespace App\Services;

use App\Models\CheckingCycle;
use App\Models\CheckResult;
use App\Models\HourlyStat;
use App\Models\SystemConfig;
use Illuminate\Support\Carbon;

class DataRetentionService
{
    public const DEFAULT_RAW_RETENTION_DAYS = 30;
    public const MIN_RAW_RETENTION_DAYS = 7;
    public const HOURLY_RETENTION_DAYS = 365;
    public const PRUNED_BEFORE_KEY = 'check_results_pruned_before';

    private const BATCH_SIZE = 5000;

    /**
     * Get the configured number of days to keep raw check results.
     */
    public function getRawRetentionDays(): int
    {
        return (int) SystemConfig::getValue('check_results_retention_days', (string) self::DEFAULT_RAW_RETENTION_DAYS);
    }

    /**
     * Delete expired monitoring data, or only count it when $dryRun is true.
     *
     * Daily, weekly and monthly stats and downtime histories are kept forever.
     *
     * @return array{check_results: int, checking_cycles: int, hourly_stats: int}
     */
    public function prune(int $rawRetentionDays, bool $dryRun = false): array
    {
        $rawCutoff = Carbon::now()->subDays($rawRetentionDays);
        $hourlyCutoff = Carbon::now()->subDays(self::HOURLY_RETENTION_DAYS);

        $counts = [
            'check_results' => $this->pruneCheckResults($rawCutoff, $dryRun),
            'checking_cycles' => $this->pruneCheckingCycles($rawCutoff, $dryRun),
            'hourly_stats' => $this->pruneHourlyStats($hourlyCutoff, $dryRun),
        ];

        if (!$dryRun && $counts['check_results'] > 0) {
            SystemConfig::updateOrCreate(
                ['key' => self::PRUNED_BEFORE_KEY],
                ['value' => $rawCutoff->toDateTimeString(), 'updated_at' => now()]
            );
        }

        return $counts;
    }

    /**
     * Delete check results older than the cutoff, keeping the newest result of
     * each page so sites that are no longer monitored still show their last check.
     */
    private function pruneCheckResults(Carbon $cutoff, bool $dryRun): int
    {
        // Results are inserted in time order, so deleting by id avoids needing an index on checked_at
        $firstKeptId = CheckResult::where('checked_at', '>=', $cutoff)->orderBy('id')->value('id')
            ?? (int) CheckResult::max('id') + 1;

        $latestIds = $this->latestResultIds();

        $query = fn() => CheckResult::where('id', '<', $firstKeptId)->whereNotIn('id', $latestIds);

        return $dryRun ? $query()->count() : $this->deleteInBatches($query);
    }

    /**
     * Delete old cycles that have no results left. Deleting a cycle cascades to its
     * results, so cycles still holding a page's newest result must stay.
     */
    private function pruneCheckingCycles(Carbon $cutoff, bool $dryRun): int
    {
        if ($dryRun) {
            // Expired results still exist on a dry run, so only the results that would be kept can block a cycle
            return CheckingCycle::where('started_at', '<', $cutoff)
                ->whereDoesntHave('checkResults', fn($query) => $query
                    ->where('checked_at', '>=', $cutoff)
                    ->orWhereIn('id', $this->latestResultIds()))
                ->count();
        }

        return $this->deleteInBatches(
            fn() => CheckingCycle::where('started_at', '<', $cutoff)->whereDoesntHave('checkResults')
        );
    }

    /**
     * Delete hourly stats older than the cutoff.
     */
    private function pruneHourlyStats(Carbon $cutoff, bool $dryRun): int
    {
        $query = fn() => HourlyStat::where('period_start', '<', $cutoff);

        return $dryRun ? $query()->count() : $this->deleteInBatches($query);
    }

    /**
     * Get the id of the newest check result of each page.
     */
    private function latestResultIds(): \Illuminate\Support\Collection
    {
        return CheckResult::selectRaw('MAX(id) AS id')->groupBy('page_id')->pluck('id');
    }

    /**
     * Delete in small batches so table locks stay short while monitoring cycles are writing.
     */
    private function deleteInBatches(callable $query): int
    {
        $total = 0;

        do {
            $deleted = $query()->orderBy('id')->limit(self::BATCH_SIZE)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        return $total;
    }
}
