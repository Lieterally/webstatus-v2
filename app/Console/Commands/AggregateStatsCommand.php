<?php

namespace App\Console\Commands;

use App\Services\StatsAggregationService;
use Illuminate\Console\Command;

class AggregateStatsCommand extends Command
{
    protected $signature = 'stats:aggregate {--backfill : Backfill all historical data from raw check_results}';
    protected $description = 'Aggregate monitoring stats into rollup tables (current periods, or full historical backfill)';

    public function handle(StatsAggregationService $service): int
    {
        if ($this->option('backfill')) {
            $this->info('Starting full historical backfill from raw check_results...');
            $this->info('This may take a while depending on how much data you have.');
            $this->newLine();

            $service->backfillAll(fn(string $msg) => $this->line($msg));

            $this->newLine();
            $this->info('Backfill complete.');
        } else {
            $this->info('Aggregating all rollup stats for current periods...');
            $service->updateAllCurrentStats();
            $this->info('Done.');
        }

        return self::SUCCESS;
    }
}
