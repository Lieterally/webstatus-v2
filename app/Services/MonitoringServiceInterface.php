<?php

namespace App\Services;

use App\DTOs\CycleResult;
use App\DTOs\CycleState;
use App\DTOs\SiteCheckResult;
use App\Models\Site;

interface MonitoringServiceInterface
{
    /** Execute a full checking cycle for all sites */
    public function executeCycle(): CycleResult;

    /** Execute checks for a single site */
    public function refreshSite(int $siteId): SiteCheckResult;

    /** Turn monitoring on or off for a site, closing any open outage and clearing its down state */
    public function setMonitored(Site $site, bool $isMonitored): void;

    /** Turn monitoring cycles on or off globally, closing all open outages when turned off */
    public function setMonitoringEnabled(bool $isEnabled): void;

    /** Get current cycle state (countdown, last check, etc.) */
    public function getCycleState(): CycleState;

    /** Check if a cycle is currently running */
    public function isCycleInProgress(): bool;

    /** Get configured cycle interval in minutes */
    public function getCycleInterval(): int;

    /** Update cycle interval (validates [5, 1440]) */
    public function setCycleInterval(int $minutes): void;
}
