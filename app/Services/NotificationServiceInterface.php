<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Collection;

interface NotificationServiceInterface
{
    /** Evaluate all sites and send notifications as needed */
    public function evaluateAndNotify(Collection $siteResults): void;

    /** Send a down notification for a specific site */
    public function sendDownNotification(Site $site, string $status, array $downPages): void;

    /** Send a recovery notification for a specific site */
    public function sendRecoveryNotification(Site $site, string $downDuration): void;

    /** Get configured notification cycle threshold */
    public function getNotificationCycleThreshold(): int;

    /** Set notification cycle threshold */
    public function setNotificationCycleThreshold(int $cycles): void;
}
