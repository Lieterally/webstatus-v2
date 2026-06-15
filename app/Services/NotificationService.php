<?php

namespace App\Services;

use App\DTOs\SiteCheckResult;
use App\Enums\SiteStatus;
use App\Models\NotificationLog;
use App\Models\Site;
use App\Models\SystemConfig;
use App\Models\TelegramTarget;
use Illuminate\Support\Carbon;
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

    /** Default notification cycle threshold */
    private const DEFAULT_NOTIFICATION_CYCLE_THRESHOLD = 6;

    public function __construct(
        private readonly TelegramBotServiceInterface $telegramBotService,
    ) {}

    /**
     * Evaluate all sites and send notifications as needed.
     *
     * Logic flow:
     * 1. For each site result, load the site from DB
     * 2. Determine current status from the check results
     * 3. Apply notification rules based on consecutive_down_count and status transitions
     * 4. Send a single consolidated message listing ALL currently down sites
     */
    public function evaluateAndNotify(Collection $siteResults): void
    {
        $shouldSendConsolidated = false;

        /** @var SiteCheckResult $siteResult */
        foreach ($siteResults as $siteResult) {
            $site = Site::find($siteResult->siteId);

            if (!$site) {
                continue;
            }

            $result = $this->evaluateSite($site, $siteResult);

            if ($result === 'send_down' || $result === 'send_recovery') {
                $shouldSendConsolidated = true;
            }
        }

        // Send consolidated down notification if any notification was triggered
        if ($shouldSendConsolidated) {
            $this->sendConsolidatedDownNotification();
        }
    }

    /**
     * Send a consolidated down notification listing ALL currently down sites.
     */
    private function sendConsolidatedDownNotification(): void
    {
        $downSites = Site::where('status', SiteStatus::PartiallyDown)
            ->orWhere('status', SiteStatus::TotallyDown)
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
     *
     * Sends to all active Telegram targets with retry logic.
     */
    public function sendDownNotification(Site $site, string $status, array $downPages): void
    {
        // Kept for interface compatibility, but consolidated notification is now used
        $this->sendConsolidatedDownNotification();
    }

    /**
     * Send a recovery notification for a specific site.
     *
     * Sends to all active Telegram targets with retry logic.
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
     * Evaluate a single site for notification conditions.
     *
     * @return string|null 'send_down', 'send_recovery', or null
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
        return $this->handleDownState($site, $currentStatus, $previousStatus, $siteResult);
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

        // Reset all counters on recovery (Req 14.4)
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
     * @return string|null 'send_down' if a consolidated notification should be sent
     */
    private function handleDownState(
        Site $site,
        SiteStatus $currentStatus,
        SiteStatus $previousStatus,
        SiteCheckResult $siteResult,
    ): ?string {
        $consecutiveDownCount = $site->consecutive_down_count + 1;
        $notificationCycleCounter = $site->notification_cycle_counter + 1;
        $notificationSent = $site->notification_sent;
        $firstDownAt = $site->first_down_at ?? now();

        $shouldNotify = false;

        // Check if we should send initial notification (Req 13.1)
        if ($consecutiveDownCount === self::FALSE_POSITIVE_THRESHOLD && !$notificationSent) {
            $notificationSent = true;
            $notificationCycleCounter = 0;
            $shouldNotify = true;
        }
        // Check for status change notification (Req 13.6)
        elseif (
            $notificationSent
            && $consecutiveDownCount >= self::FALSE_POSITIVE_THRESHOLD
            && $this->isDownStatus($previousStatus)
            && $previousStatus !== $currentStatus
        ) {
            $shouldNotify = true;
        }
        // Check for repeated notification (Req 13.4, 13.5)
        elseif (
            $notificationSent
            && $consecutiveDownCount >= self::FALSE_POSITIVE_THRESHOLD
        ) {
            $threshold = $this->getNotificationCycleThreshold();

            if ($notificationCycleCounter >= $threshold && $notificationCycleCounter % $threshold === 0) {
                $shouldNotify = true;
            }
        }

        // Update site state
        $site->update([
            'status' => $currentStatus,
            'consecutive_down_count' => $consecutiveDownCount,
            'notification_cycle_counter' => $notificationCycleCounter,
            'notification_sent' => $notificationSent,
            'first_down_at' => $firstDownAt,
        ]);

        return $shouldNotify ? 'send_down' : null;
    }

    /**
     * Send a status change notification (between partially_down and totally_down).
     */
    private function sendStatusChangeNotification(Site $site, SiteStatus $newStatus, array $downPages): void
    {
        $message = $this->formatStatusChangeMessage($site, $newStatus->value, $downPages);

        $result = $this->broadcastWithRetry($message);

        NotificationLog::create([
            'site_id' => $site->id,
            'type' => 'status_change',
            'message' => $message,
            'targets_sent' => $result['sent'],
            'targets_failed' => $result['failed'],
            'sent_at' => now(),
        ]);
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

                // Wait before retrying (unless it's the last attempt)
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
     * Format a consolidated down notification message with all currently down sites in a table.
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
     * Format a down notification message (legacy, kept for reference).
     */
    private function formatDownMessage(Site $site, string $status, array $downPages): string
    {
        $statusLabel = $status === 'totally_down' ? '🔴 TOTALLY DOWN' : '🟡 PARTIALLY DOWN';

        $duration = '';
        if ($site->first_down_at) {
            $duration = "\n⏱ Down since: " . $site->first_down_at->format('Y-m-d H:i:s');
        }

        return "⚠️ Site Down Alert\n\n"
            . "📍 Site: {$site->name}\n"
            . "� URL: {$site->base_url}\n"
            . "� Status: {$statusLabel}"
            . $duration;
    }

    /**
     * Format a status change notification message.
     */
    private function formatStatusChangeMessage(Site $site, string $newStatus, array $downPages): string
    {
        $statusLabel = $newStatus === 'totally_down' ? '🔴 TOTALLY DOWN' : '🟡 PARTIALLY DOWN';

        return "🔄 Status Change Alert\n\n"
            . "📍 Site: {$site->name}\n"
            . "🔗 URL: {$site->base_url}\n"
            . "📊 New Status: {$statusLabel}";
    }

    /**
     * Format a recovery notification message.
     */
    private function formatRecoveryMessage(Site $site, string $downDuration): string
    {
        return "✅ Site Recovery\n\n"
            . "📍 Site: {$site->name}\n"
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
     * Get URLs of down pages from check results.
     */
    private function getDownPageUrls(SiteCheckResult $siteResult): array
    {
        return $siteResult->pageResults
            ->filter(fn($result) => !$result->isReachable())
            ->map(fn($result) => $result->url)
            ->values()
            ->all();
    }

    /**
     * Check if the given status is a down status.
     */
    private function isDownStatus(SiteStatus $status): bool
    {
        return $status === SiteStatus::PartiallyDown || $status === SiteStatus::TotallyDown;
    }
}
