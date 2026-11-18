<?php

namespace App\Services;

use App\DTOs\PageCheckResult;
use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Support\Collection;

class StatusDeterminationService implements StatusDeterminationServiceInterface
{
    /**
     * Determine site availability status from page results.
     *
     * A page result is considered successful per PageCheckResult::isSuccessfulCode() (2xx, 3xx, 429).
     * Any other code, or 0 (unreachable), is a failure.
     */
    public function determineStatus(Site $site, Collection $pageResults): SiteStatus
    {
        // No pages defined: defaults to "up"
        if ($pageResults->isEmpty()) {
            return SiteStatus::Up;
        }

        $totalPages = $pageResults->count();
        $successfulPages = $pageResults->filter(function ($result) {
            return PageCheckResult::isSuccessfulCode($result['http_code'] ?? 0);
        })->count();

        if ($successfulPages === $totalPages) {
            return SiteStatus::Up;
        }

        if ($successfulPages === 0) {
            return SiteStatus::TotallyDown;
        }

        return SiteStatus::PartiallyDown;
    }

    /**
     * Calculate average response time for reachable pages.
     *
     * A page is considered reachable per PageCheckResult::isSuccessfulCode().
     * Returns 0 if all pages are unreachable.
     */
    public function calculateAverageResponseTime(Collection $pageResults): float
    {
        if ($pageResults->isEmpty()) {
            return 0.0;
        }

        $reachablePages = $pageResults->filter(function ($result) {
            return PageCheckResult::isSuccessfulCode($result['http_code'] ?? 0);
        });

        if ($reachablePages->isEmpty()) {
            return 0.0;
        }

        $totalResponseTime = $reachablePages->sum(function ($result) {
            return $result['response_time_ms'] ?? 0.0;
        });

        return $totalResponseTime / $reachablePages->count();
    }
}
