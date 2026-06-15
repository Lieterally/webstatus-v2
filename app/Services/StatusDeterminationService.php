<?php

namespace App\Services;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Support\Collection;

class StatusDeterminationService implements StatusDeterminationServiceInterface
{
    /**
     * Determine site availability status from page results.
     *
     * A page result is considered successful if its http_code is in the 2xx or 3xx range.
     * A page result is considered failed if its http_code is outside 2xx/3xx or is 0 (unreachable).
     */
    public function determineStatus(Site $site, Collection $pageResults): SiteStatus
    {
        // No pages defined: defaults to "up"
        if ($pageResults->isEmpty()) {
            return SiteStatus::Up;
        }

        $totalPages = $pageResults->count();
        $successfulPages = $pageResults->filter(function ($result) {
            return $this->isSuccessfulResponse($result['http_code'] ?? 0);
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
     * A page is considered reachable if its http_code is in the 2xx or 3xx range.
     * Returns 0 if all pages are unreachable.
     */
    public function calculateAverageResponseTime(Collection $pageResults): float
    {
        if ($pageResults->isEmpty()) {
            return 0.0;
        }

        $reachablePages = $pageResults->filter(function ($result) {
            return $this->isSuccessfulResponse($result['http_code'] ?? 0);
        });

        if ($reachablePages->isEmpty()) {
            return 0.0;
        }

        $totalResponseTime = $reachablePages->sum(function ($result) {
            return $result['response_time_ms'] ?? 0.0;
        });

        return $totalResponseTime / $reachablePages->count();
    }

    /**
     * Determine if an HTTP response code indicates success (2xx or 3xx).
     */
    private function isSuccessfulResponse(int $httpCode): bool
    {
        return $httpCode >= 200 && $httpCode < 400;
    }
}
