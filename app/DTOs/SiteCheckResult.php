<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

/**
 * Represents the aggregated check results for a single site.
 */
class SiteCheckResult
{
    public function __construct(
        public readonly int $siteId,
        public readonly string $siteName,
        /** @var Collection<int, PageCheckResult> */
        public readonly Collection $pageResults,
    ) {}

    /**
     * Get the average response time of reachable pages (ms).
     * Returns 0 if no pages are reachable.
     */
    public function getAverageResponseTime(): float
    {
        $reachable = $this->pageResults->filter(fn (PageCheckResult $r) => $r->isReachable());

        if ($reachable->isEmpty()) {
            return 0.0;
        }

        return $reachable->avg(fn (PageCheckResult $r) => $r->responseTimeMs);
    }
}
