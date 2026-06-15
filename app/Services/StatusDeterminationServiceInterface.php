<?php

namespace App\Services;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Support\Collection;

interface StatusDeterminationServiceInterface
{
    /**
     * Determine site availability status from page results.
     *
     * Status Rules:
     * - "up": All pages return 2xx or 3xx
     * - "partially_down": Some pages fail, some succeed
     * - "totally_down": All pages fail or are unreachable
     * - No pages defined: defaults to "up"
     */
    public function determineStatus(Site $site, Collection $pageResults): SiteStatus;

    /**
     * Calculate average response time for reachable pages.
     *
     * Returns the sum of reachable page response times divided by
     * the count of reachable pages. Returns 0 if all pages are unreachable.
     */
    public function calculateAverageResponseTime(Collection $pageResults): float;
}
