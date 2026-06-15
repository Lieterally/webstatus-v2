<?php

namespace App\Console\Commands;

use App\Models\NotificationLog;
use App\Models\SystemConfig;
use App\Services\MonitoringServiceInterface;
use App\Services\TelegramBotServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunMonitoringCycleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:run-monitoring-cycle';

    /**
     * The console command description.
     */
    protected $description = 'Execute a monitoring cycle to check all registered websites';

    /** System config key for tracking consecutive cycle failures */
    private const CONSECUTIVE_FAILURES_KEY = 'consecutive_cycle_failures';

    /** System config key for last cycle run timestamp */
    private const LAST_CYCLE_RUN_KEY = 'last_cycle_run_at';

    /** Number of consecutive failures before sending system health alert */
    private const FAILURE_ALERT_THRESHOLD = 3;

    public function __construct(
        private readonly MonitoringServiceInterface $monitoringService,
        private readonly TelegramBotServiceInterface $telegramBotService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting monitoring cycle...');

        try {
            // Execute the monitoring cycle
            $result = $this->monitoringService->executeCycle();

            // Persist cycle state
            $this->persistCycleState($result->completedAt);

            // Reset consecutive failure count on success
            $this->resetConsecutiveFailures();

            $this->info("Monitoring cycle completed. Sites checked: {$result->sitesChecked}, Sites down: {$result->sitesDown}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->handleCycleFailure($e);

            return self::FAILURE;
        }
    }

    /**
     * Persist the cycle state (last run timestamp) to the database.
     */
    private function persistCycleState(\DateTimeInterface $completedAt): void
    {
        SystemConfig::updateOrCreate(
            ['key' => self::LAST_CYCLE_RUN_KEY],
            ['value' => $completedAt->format('Y-m-d H:i:s'), 'updated_at' => now()],
        );
    }

    /**
     * Reset consecutive cycle failure count.
     */
    private function resetConsecutiveFailures(): void
    {
        SystemConfig::updateOrCreate(
            ['key' => self::CONSECUTIVE_FAILURES_KEY],
            ['value' => '0', 'updated_at' => now()],
        );
    }

    /**
     * Handle a cycle failure: log, increment failure counter, and potentially send health alert.
     */
    private function handleCycleFailure(\Throwable $e): void
    {
        $cycleIdentifier = 'cycle_' . now()->format('Ymd_His');

        // Log the failure with timestamp and cycle identifier (Req 28.2)
        Log::error("Monitoring cycle failed [{$cycleIdentifier}]", [
            'cycle_id' => $cycleIdentifier,
            'timestamp' => now()->toIso8601String(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->error("Monitoring cycle failed [{$cycleIdentifier}]: {$e->getMessage()}");

        // Increment consecutive failure count
        $failureCount = $this->incrementConsecutiveFailures();

        // Check if we need to send system health alert (Req 28.5)
        if ($failureCount >= self::FAILURE_ALERT_THRESHOLD) {
            $this->sendSystemHealthAlert($failureCount, $cycleIdentifier);
        }
    }

    /**
     * Increment the consecutive cycle failure count and return the new value.
     */
    private function incrementConsecutiveFailures(): int
    {
        $current = (int) SystemConfig::getValue(self::CONSECUTIVE_FAILURES_KEY, '0');
        $newCount = $current + 1;

        SystemConfig::updateOrCreate(
            ['key' => self::CONSECUTIVE_FAILURES_KEY],
            ['value' => (string) $newCount, 'updated_at' => now()],
        );

        return $newCount;
    }

    /**
     * Send a system health alert to all active Telegram targets (Req 28.5).
     *
     * Only sends once when the threshold is exactly reached to avoid spamming.
     */
    private function sendSystemHealthAlert(int $failureCount, string $cycleIdentifier): void
    {
        // Only send when we first reach the threshold (exactly 3)
        if ($failureCount !== self::FAILURE_ALERT_THRESHOLD) {
            return;
        }

        $message = "🚨 <b>System Health Alert</b>\n\n"
            . "The monitoring system has failed to complete {$failureCount} consecutive checking cycles.\n\n"
            . "⚠️ The monitoring system requires immediate attention.\n"
            . "🔍 Last failed cycle: {$cycleIdentifier}\n"
            . "🕐 Time: " . now()->format('Y-m-d H:i:s');

        try {
            $this->telegramBotService->broadcastToActiveTargets($message);

            // Log the notification
            NotificationLog::create([
                'site_id' => null,
                'type' => 'system_health',
                'message' => $message,
                'targets_sent' => 1,
                'targets_failed' => 0,
                'sent_at' => now(),
            ]);

            Log::warning('System health alert sent: 3 consecutive cycle failures', [
                'failure_count' => $failureCount,
                'cycle_id' => $cycleIdentifier,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send system health alert', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
