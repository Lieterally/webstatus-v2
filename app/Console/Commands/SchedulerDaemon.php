<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SchedulerDaemon extends Command
{
    protected $signature = 'schedule:daemon';
    protected $description = 'Run the Laravel scheduler continuously (replaces cron on Windows/Laragon)';

    public function handle(): int
    {
        $this->info('Scheduler daemon started. Press Ctrl+C to stop.');
        $this->info('This runs "schedule:run" every 60 seconds.');
        $this->newLine();

        while (true) {
            $this->line('[' . now()->format('H:i:s') . '] Running scheduler...');

            Artisan::call('schedule:run');
            $output = Artisan::output();

            if (trim($output)) {
                $this->line($output);
            }

            // Sleep 60 seconds (Laravel scheduler resolution is 1 minute)
            sleep(60);
        }

        return self::SUCCESS;
    }
}
