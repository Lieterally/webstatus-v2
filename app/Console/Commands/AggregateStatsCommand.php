<?php

namespace App\Console\Commands;

use App\Services\StatsAggregationService;
use Illuminate\Console\Command;

class AggregateStatsCommand extends Command
{
    protected $signature = 'stats:aggregate';
    protected $description = 'Manually trigger aggregation of all rollup stats (hourly, daily, weekly, monthly) for current periods';

    public function handle(StatsAggregationService $service): int
    {
        $this->info('Aggregating all rollup stats for current periods...');

        $service->updateAllCurrentStats();

        $this->info('All rollup stats updated successfully.');

        return self::SUCCESS;
    }
}
