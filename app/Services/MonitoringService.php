<?php

namespace App\Services;

use App\DTOs\CycleResult;
use App\DTOs\CycleState;
use App\DTOs\SiteCheckResult;
use App\Enums\SiteStatus;
use App\Enums\TriggerType;
use App\Models\CheckingCycle;
use App\Models\CheckResult;
use App\Models\Site;
use App\Models\SystemConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitoringService implements MonitoringServiceInterface
{
    /** Default cycle interval in minutes */
    private const DEFAULT_CYCLE_INTERVAL = 10;

    /** Minimum cycle interval in minutes */
    private const MIN_CYCLE_INTERVAL = 5;

    /** Maximum cycle interval in minutes */
    private const MAX_CYCLE_INTERVAL = 1440;

    /** Cache key for tracking cycle in progress */
    private const CYCLE_IN_PROGRESS_KEY = 'monitoring_cycle_in_progress';

    public function __construct(
        private readonly HealthCheckServiceInterface $healthCheckService,
        private readonly StatusDeterminationServiceInterface $statusDeterminationService,
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    /**
     * Execute a full checking cycle for all sites.
     *
     * Flow:
     * 1. Fetch all sites with pages
     * 2. Call HealthCheckService::checkAllSites()
     * 3. Use StatusDeterminationService::determineStatus() for each site
     * 4. Persist CheckResult records
     * 5. Update site consecutive_down_count (increment on down, reset on up)
     * 6. Update site status and first_down_at
     * 7. Call NotificationService::evaluateAndNotify()
     * 8. Create CheckingCycle record
     * 9. Update cycle timestamp
     */
    public function executeCycle(): CycleResult
    {
        $startedAt = now();
        Cache::put(self::CYCLE_IN_PROGRESS_KEY, true, 300); // 5 minute max lock (safety net)

        try {
            // 1. Fetch all sites with pages
            $sites = Site::with('pages')->get();

            // 2. Execute health checks for all sites
            $siteResults = $this->healthCheckService->checkAllSites($sites);

            // 3. Create CheckingCycle record
            $cycle = CheckingCycle::create([
                'started_at' => $startedAt,
                'completed_at' => null,
                'trigger_type' => TriggerType::Automatic,
                'sites_checked' => $sites->count(),
                'sites_down' => 0,
            ]);

            $sitesDown = 0;

            // 4. Process each site result
            foreach ($siteResults as $siteResult) {
                $site = $sites->firstWhere('id', $siteResult->siteId);

                if (!$site) {
                    continue;
                }

                // Persist check results
                $this->persistCheckResults($siteResult, $cycle->id);

                // Determine status using StatusDeterminationService
                $pageResults = $this->buildPageResultsCollection($siteResult);
                $newStatus = $this->statusDeterminationService->determineStatus($site, $pageResults);

                // Calculate average response time
                $avgResponseTime = $this->statusDeterminationService->calculateAverageResponseTime($pageResults);

                // Update consecutive_down_count
                $this->updateSiteStatus($site, $newStatus, $avgResponseTime);

                if ($newStatus !== SiteStatus::Up) {
                    $sitesDown++;
                }
            }

            // Update cycle record with completion data
            $completedAt = now();
            $cycle->update([
                'completed_at' => $completedAt,
                'sites_down' => $sitesDown,
            ]);

            // 5. Evaluate notifications
            $this->notificationService->evaluateAndNotify($siteResults);

            // 6. Update last cycle timestamp
            $this->updateLastCycleTimestamp($completedAt);

            return new CycleResult(
                cycleId: $cycle->id,
                sitesChecked: $sites->count(),
                sitesDown: $sitesDown,
                siteResults: $siteResults,
                startedAt: $startedAt,
                completedAt: $completedAt,
            );
        } finally {
            Cache::forget(self::CYCLE_IN_PROGRESS_KEY);
        }
    }

    /**
     * Execute checks for a single site.
     *
     * Does NOT reset the global timer.
     * Still updates consecutive_down_count.
     */
    public function refreshSite(int $siteId): SiteCheckResult
    {
        $site = Site::with('pages')->findOrFail($siteId);

        $startedAt = now();

        // Execute health check for single site
        $siteResult = $this->healthCheckService->checkSite($site);

        // Create a cycle record for this individual refresh
        $cycle = CheckingCycle::create([
            'started_at' => $startedAt,
            'completed_at' => now(),
            'trigger_type' => TriggerType::ManualSite,
            'sites_checked' => 1,
            'sites_down' => 0,
        ]);

        // Persist check results
        $this->persistCheckResults($siteResult, $cycle->id);

        // Determine status
        $pageResults = $this->buildPageResultsCollection($siteResult);
        $newStatus = $this->statusDeterminationService->determineStatus($site, $pageResults);

        // Calculate average response time
        $avgResponseTime = $this->statusDeterminationService->calculateAverageResponseTime($pageResults);

        // Update site status and consecutive_down_count
        $this->updateSiteStatus($site, $newStatus, $avgResponseTime);

        // Update cycle sites_down
        if ($newStatus !== SiteStatus::Up) {
            $cycle->update(['sites_down' => 1]);
        }

        // Evaluate notification for this single site
        $this->notificationService->evaluateAndNotify(collect([$siteResult]));

        return $siteResult;
    }

    /**
     * Get current cycle state (countdown, last check, etc.).
     */
    public function getCycleState(): CycleState
    {
        $lastCheckAt = $this->getLastCycleTimestamp();
        $intervalMinutes = $this->getCycleInterval();
        $cycleInProgress = $this->isCycleInProgress();

        $countdownSeconds = 0;

        if ($lastCheckAt && !$cycleInProgress) {
            $nextCycleAt = $lastCheckAt->copy()->addMinutes($intervalMinutes);
            $countdownSeconds = max(0, (int) now()->diffInSeconds($nextCycleAt, false));
        } elseif (!$lastCheckAt && !$cycleInProgress) {
            // No cycle has ever run; countdown is the full interval
            $countdownSeconds = $intervalMinutes * 60;
        }

        return new CycleState(
            countdownSeconds: $countdownSeconds,
            lastCheckAt: $lastCheckAt,
            cycleInProgress: $cycleInProgress,
            cycleIntervalMinutes: $intervalMinutes,
        );
    }

    /**
     * Check if a cycle is currently running.
     */
    public function isCycleInProgress(): bool
    {
        return (bool) Cache::get(self::CYCLE_IN_PROGRESS_KEY, false);
    }

    /**
     * Get configured cycle interval in minutes.
     */
    public function getCycleInterval(): int
    {
        $value = SystemConfig::getValue('cycle_interval_minutes');

        if ($value === null) {
            return self::DEFAULT_CYCLE_INTERVAL;
        }

        return (int) $value;
    }

    /**
     * Update cycle interval (validates [5, 1440]).
     *
     * @throws \InvalidArgumentException if value is outside [5, 1440]
     */
    public function setCycleInterval(int $minutes): void
    {
        if ($minutes < self::MIN_CYCLE_INTERVAL || $minutes > self::MAX_CYCLE_INTERVAL) {
            throw new \InvalidArgumentException(
                "Cycle interval must be between " . self::MIN_CYCLE_INTERVAL . " and " . self::MAX_CYCLE_INTERVAL . " minutes."
            );
        }

        SystemConfig::updateOrCreate(
            ['key' => 'cycle_interval_minutes'],
            ['value' => (string) $minutes, 'updated_at' => now()],
        );
    }

    /**
     * Persist check results for a site to the database.
     */
    private function persistCheckResults(SiteCheckResult $siteResult, int $cycleId): void
    {
        foreach ($siteResult->pageResults as $pageResult) {
            CheckResult::create([
                'site_id' => $pageResult->siteId,
                'page_id' => $pageResult->pageId,
                'cycle_id' => $cycleId,
                'http_code' => $pageResult->httpCode,
                'response_time_ms' => $pageResult->responseTimeMs,
                'error_type' => $pageResult->errorType,
                'checked_at' => now(),
            ]);
        }
    }

    /**
     * Build a page results collection compatible with StatusDeterminationService.
     *
     * The StatusDeterminationService expects a collection of arrays with
     * 'http_code' and 'response_time_ms' keys.
     */
    private function buildPageResultsCollection(SiteCheckResult $siteResult): Collection
    {
        return $siteResult->pageResults->map(fn($pageResult) => [
            'http_code' => $pageResult->httpCode,
            'response_time_ms' => $pageResult->responseTimeMs,
        ]);
    }

    /**
     * Update site status and consecutive_down_count.
     *
     * - Increment consecutive_down_count on down (partially_down or totally_down)
     * - Reset to 0 on up
     * - Track first_down_at
     */
    private function updateSiteStatus(Site $site, SiteStatus $newStatus, float $avgResponseTime): void
    {
        $updates = [
            'status' => $newStatus,
            'avg_response_time' => $avgResponseTime,
        ];

        if ($newStatus === SiteStatus::Up) {
            // Only update avg_response_time here; notification service handles the full reset
            // (consecutive_down_count, notification_sent, etc. are managed by NotificationService)
        }

        // Track when site first went down (if not already tracked)
        if ($newStatus !== SiteStatus::Up && $site->first_down_at === null) {
            $updates['first_down_at'] = now();
        }

        $site->update($updates);
    }

    /**
     * Update the last cycle completion timestamp in system_configs.
     * Also updates last_cycle_run_at so the scheduler doesn't double-trigger.
     */
    private function updateLastCycleTimestamp(\DateTimeInterface $completedAt): void
    {
        $formatted = $completedAt->format('Y-m-d H:i:s');

        SystemConfig::updateOrCreate(
            ['key' => 'last_cycle_completed_at'],
            ['value' => $formatted, 'updated_at' => now()],
        );

        // Keep scheduler's key in sync so it doesn't re-trigger immediately
        SystemConfig::updateOrCreate(
            ['key' => 'last_cycle_run_at'],
            ['value' => $formatted, 'updated_at' => now()],
        );
    }

    /**
     * Get the last cycle completion timestamp.
     */
    private function getLastCycleTimestamp(): ?Carbon
    {
        $value = SystemConfig::getValue('last_cycle_completed_at');

        if ($value === null) {
            return null;
        }

        return Carbon::parse($value);
    }
}
