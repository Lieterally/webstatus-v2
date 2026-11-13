<?php

namespace App\Console\Commands;

use App\Services\DataRetentionService;
use Illuminate\Console\Command;

class PruneMonitoringDataCommand extends Command
{
    protected $signature = 'app:prune-monitoring-data
        {--days= : Days of raw check results to keep (defaults to check_results_retention_days)}
        {--dry-run : Show what would be deleted without deleting anything}';
    protected $description = 'Delete raw check results, cycles and hourly stats past their retention period';

    public function handle(DataRetentionService $service): int
    {
        $days = $this->option('days') !== null ? (int) $this->option('days') : $service->getRawRetentionDays();

        if ($days < DataRetentionService::MIN_RAW_RETENTION_DAYS) {
            $this->error('Retention must be at least ' . DataRetentionService::MIN_RAW_RETENTION_DAYS . " days, got {$days}.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $counts = $service->prune($days, $dryRun);

        $this->info(($dryRun ? 'Dry run, nothing deleted. Would delete: ' : 'Deleted: ')
            . "{$counts['check_results']} check results, {$counts['checking_cycles']} cycles, {$counts['hourly_stats']} hourly stats "
            . "(keeping {$days} days of raw results).");

        return self::SUCCESS;
    }
}
