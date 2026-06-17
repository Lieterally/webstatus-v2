<?php

namespace App\Services;

use App\DTOs\SiteCheckResult;
use App\Enums\SiteStatus;
use App\Models\NotificationLog;
use App\Models\Site;
use App\Models\SystemConfig;
use App\Models\TelegramTarget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService implements NotificationServiceInterface
{
    /** Number of consecutive down cycles before sending notification */
    private const FALSE_POSITIVE_THRESHOLD = 3;

    /** Maximum retry attempts for notification delivery */
    private const MAX_RETRY_ATTEMPTS = 3;

    /** Seconds between retry attempts */
    private const RETRY_INTERVAL_SECONDS = 5;

    /** Default notification cycle threshold for repeat notifications */
    private const DEFAULT_NOTIFICATION_CYCLE_THRESHOLD = 6;

    public function __construct(
        private readonly TelegramBotServiceInterface $telegramBotService,
    ) {}

    /**
     * Evaluate all sites and send notifications as needed.
     *
     * Uses a global repeat timer: one consolidated notification every N cycles
     * as long as any site remains down. Individual sites still track their own
     * consecutive_down_count for the initial 3-cycle false positive threshold.
     *
     * Flow:
     * 1. Evaluate each site for initial alert (3 consecutive down) or recovery
     * 2. If any site triggers initial alert → send consolidated + reset global timer
     * 3. If no initial alert triggered but sites are still down → increment global timer
     * 4. If global timer hits threshold → send consolidated repeat + reset timer
     */
    public function evaluateAndNotify(Collection $siteResults): void
    {
        $initialAlertTriggered = false;
        $recoveryTriggered = false;

        /** @var SiteCheckResult $siteResult */
        foreach ($siteResults as $siteResult) {
            $site = Site::find($siteResult->siteId);

            if (!$site) {
                continue;
            }

            $result = $this->evaluateSite($site, $siteResult);

            if ($result === 'send_down') {
                $initialAlertTriggered = true;
            } elseif ($result === 'send_recovery') {
                $recoveryTriggered = true;
            }
        }

        // If an initial alert was triggered (new site hit threshold), send consolidated and reset global timer
        if ($initialAlertTriggered) {
            $this->sendConsolidatedDownNotification();
            $this->resetGlobalNotificationTimer();
            return;
        }

        // If a recovery happened but there are still other down sites, send a consolidated update
        if ($recoveryTriggered) {
            $downSitesExist = Site::where('status', SiteStatus::PartiallyDown)
                ->orWhere('status', SiteStatus::TotallyDown)
                ->exists();

            // Don't send consolidated if all sites are now up (recovery message was already sent)
            if (!$downSitesExist) {
                $this->resetGlobalNotificationTimer();
                return;
            }
        }

        // Check global repeat timer: if any sites are still down with notification_sent=true
        $sitesWithActiveNotification = Site::where('notification_sent', true)
            ->where(function ($q) {
                $q->where('status', SiteStatus::PartiallyDown)
                    ->orWhere('status', SiteStatus::TotallyDown);
            })
            ->exists();

        if ($sitesWithActiveNotification) {
            $globalCounter = $this->incrementGlobalNotificationTimer();
            $threshold = $this->getNotificationCycleThreshold();

            if ($globalCounter >= $threshold) {
                $this->sendConsolidatedDownNotification();
                $this->resetGlobalNotificationTimer();
            }
        }
    }

    /**
     * Send a consolidated down notification listing ALL currently down sites.
     */
    private function sendConsolidatedDownNotification(): void
    {
        $downSites = Site::where(function ($q) {
            $q->where('status', SiteStatus::PartiallyDown)
                ->orWhere('status', SiteStatus::TotallyDown);
        })
            ->whereNotNull('first_down_at')
            ->orderBy('first_down_at')
            ->get();

        if ($downSites->isEmpty()) {
            return;
        }

        $message = $this->formatConsolidatedDownMessage($downSites);

        $result = $this->broadcastWithRetry($message);

        NotificationLog::create([
            'site_id' => null,
            'type' => 'down',
            'message' => $message,
            'targets_sent' => $result['sent'],
            'targets_failed' => $result['failed'],
            'sent_at' => now(),
        ]);
    }

    /**
     * Send a down notification for a specific site.
     */
    public function sendDownNotification(Site $site, string $status, array $downPages): void
    {
        $this->sendConsolidatedDownNotification();
    }

    /**
     * Send a recovery notification for a specific site.
     */
    public function sendRecoveryNotification(Site $site, string $downDuration): void
    {
        $message = $this->formatRecoveryMessage($site, $downDuration);

        $result = $this->broadcastWithRetry($message);

        NotificationLog::create([
            'site_id' => $site->id,
            'type' => 'recovery',
            'message' => $message,
            'targets_sent' => $result['sent'],
            'targets_failed' => $result['failed'],
            'sent_at' => now(),
        ]);
    }

    /**
     * Get configured notification cycle threshold.
     */
    public function getNotificationCycleThreshold(): int
    {
        $value = SystemConfig::getValue('notification_cycle_threshold');

        if ($value === null) {
            return self::DEFAULT_NOTIFICATION_CYCLE_THRESHOLD;
        }

        return (int) $value;
    }

    /**
     * Set notification cycle threshold.
     */
    public function setNotificationCycleThreshold(int $cycles): void
    {
        SystemConfig::updateOrCreate(
            ['key' => 'notification_cycle_threshold'],
            ['value' => (string) $cycles, 'updated_at' => now()],
        );
    }

    /**
     * Get the current global notification timer value.
     */
    private function getGlobalNotificationTimer(): int
    {
        $value = SystemConfig::getValue('global_notification_counter');
        return $value !== null ? (int) $value : 0;
    }

    /**
     * Increment the global notification timer and return the new value.
     */
    private function incrementGlobalNotificationTimer(): int
    {
        $current = $this->getGlobalNotificationTimer();
        $newValue = $current + 1;

        SystemConfig::updateOrCreate(
            ['key' => 'global_notification_counter'],
            ['value' => (string) $newValue, 'updated_at' => now()],
        );

        return $newValue;
    }

    /**
     * Reset the global notification timer to 0.
     */
    private function resetGlobalNotificationTimer(): void
    {
        SystemConfig::updateOrCreate(
            ['key' => 'global_notification_counter'],
            ['value' => '0', 'updated_at' => now()],
        );
    }

    /**
     * Evaluate a single site for notification conditions.
     *
     * @return string|null 'send_down' (initial alert), 'send_recovery', or null
     */
    private function evaluateSite(Site $site, SiteCheckResult $siteResult): ?string
    {
        $previousStatus = $site->status;
        $previousNotificationSent = $site->notification_sent;

        // Determine current status from page results
        $currentStatus = $this->determineStatusFromResults($siteResult);

        // Handle recovery: site is now up
        if ($currentStatus === SiteStatus::Up) {
            return $this->handleRecovery($site, $previousStatus, $previousNotificationSent);
        }

        // Site is down (partially or totally)
        return $this->handleDownState($site, $currentStatus);
    }

    /**
     * Handle the case when a site transitions to "up".
     *
     * @return string|null 'send_recovery' if notification should be sent
     */
    private function handleRecovery(Site $site, SiteStatus $previousStatus, bool $previousNotificationSent): ?string
    {
        $shouldNotify = false;

        // Only send recovery notification if:
        // 1. Site was previously down (partially or totally)
        // 2. A down notification was previously sent
        if ($previousNotificationSent && $this->isDownStatus($previousStatus)) {
            $downDuration = $this->calculateDownDuration($site);
            $this->sendRecoveryNotification($site, $downDuration);
            $shouldNotify = true;
        }

        // Reset all counters on recovery
        $site->update([
            'status' => SiteStatus::Up,
            'consecutive_down_count' => 0,
            'notification_cycle_counter' => 0,
            'notification_sent' => false,
            'first_down_at' => null,
        ]);

        return $shouldNotify ? 'send_recovery' : null;
    }

    /**
     * Handle the case when a site is in a down state.
     *
     * Only triggers 'send_down' when a site first hits the false positive threshold.
     * Repeat notifications are handled by the global timer in evaluateAndNotify().
     *
     * @return string|null 'send_down' if initial alert should be sent
     */
    private function handleDownState(Site $site, SiteStatus $currentStatus): ?string
    {
        $consecutiveDownCount = $site->consecutive_down_count + 1;
        $notificationSent = $site->notification_sent;
        $firstDownAt = $site->first_down_at ?? now();

        $shouldNotify = false;

        // Send initial notification when site hits false positive threshold
        if ($consecutiveDownCount === self::FALSE_POSITIVE_THRESHOLD && !$notificationSent) {
            $notificationSent = true;
            $shouldNotify = true;
        }

        // Update site state
        $site->update([
            'status' => $currentStatus,
            'consecutive_down_count' => $consecutiveDownCount,
            'notification_sent' => $notificationSent,
            'first_down_at' => $firstDownAt,
        ]);

        return $shouldNotify ? 'send_down' : null;
    }

    /**
     * Broadcast a message to all active Telegram targets with retry logic.
     *
     * @return array{sent: int, failed: int}
     */
    private function broadcastWithRetry(string $message): array
    {
        $targets = TelegramTarget::where('is_active', true)->get();
        $sent = 0;
        $failed = 0;

        foreach ($targets as $target) {
            $delivered = false;

            for ($attempt = 1; $attempt <= self::MAX_RETRY_ATTEMPTS; $attempt++) {
                try {
                    $success = $this->telegramBotService->sendMessage($target->chat_id, $message);

                    if ($success) {
                        $delivered = true;
                        break;
                    }
                } catch (\Throwable $e) {
                    Log::warning("Notification delivery attempt {$attempt} failed for chat_id {$target->chat_id}", [
                        'error' => $e->getMessage(),
                    ]);
                }

                if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                    sleep(self::RETRY_INTERVAL_SECONDS);
                }
            }

            if ($delivered) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Format a consolidated down notification message with all currently down sites.
     */
    private function formatConsolidatedDownMessage(Collection $downSites): string
    {
        $count = $downSites->count();
        $header = "⚠️ <b>Site Down Alert ({$count} site" . ($count > 1 ? 's' : '') . ")</b>\n\n";

        $table = "";
        $index = 1;
        foreach ($downSites as $site) {
            $statusIcon = $site->status === SiteStatus::TotallyDown ? '🔴' : '🟡';
            $downSince = $site->first_down_at ? $site->first_down_at->format('d M H:i') : '-';

            $table .= "{$index}. {$statusIcon} <b>{$site->name}</b>\n";
            $table .= "   🔗 {$site->base_url}\n";
            $table .= "   ⏱ Down since: {$downSince}\n\n";
            $index++;
        }

        $table .= "🕐 Checked at: " . now()->format('Y-m-d H:i:s');

        return $header . $table;
    }

    /**
     * Format a recovery notification message.
     */
    private function formatRecoveryMessage(Site $site, string $downDuration): string
    {
        return "✅ Site Recovery\n\n"
            . "📍 Site: {$site->name}\n"
            . "🔗 URL: {$site->base_url}\n"
            . "📊 Status: 🟢 UP\n"
            . "⏱ Total Down Duration: {$downDuration}";
    }

    /**
     * Calculate the down duration as a human-readable string.
     */
    private function calculateDownDuration(Site $site): string
    {
        if (!$site->first_down_at) {
            return '0m';
        }

        $downSince = $site->first_down_at;
        $now = now();

        $totalMinutes = (int) $downSince->diffInMinutes($now);
        $days = intdiv($totalMinutes, 1440);
        $hours = intdiv($totalMinutes % 1440, 60);
        $minutes = $totalMinutes % 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = "{$days}d";
        }
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        $parts[] = "{$minutes}m";

        return implode(' ', $parts);
    }

    /**
     * Determine site status from check results.
     */
    private function determineStatusFromResults(SiteCheckResult $siteResult): SiteStatus
    {
        $pageResults = $siteResult->pageResults;

        if ($pageResults->isEmpty()) {
            return SiteStatus::Up;
        }

        $totalPages = $pageResults->count();
        $reachablePages = $pageResults->filter(fn($result) => $result->isReachable())->count();

        if ($reachablePages === $totalPages) {
            return SiteStatus::Up;
        }

        if ($reachablePages === 0) {
            return SiteStatus::TotallyDown;
        }

        return SiteStatus::PartiallyDown;
    }

    /**
     * Check if the given status is a down status.
     */
    private function isDownStatus(SiteStatus $status): bool
    {
        return $status === SiteStatus::PartiallyDown || $status === SiteStatus::TotallyDown;
    }
}
