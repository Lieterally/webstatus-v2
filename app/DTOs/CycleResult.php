<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

/**
 * Represents the result of a complete checking cycle.
 */
class CycleResult
{
    public function __construct(
        public readonly int $cycleId,
        public readonly int $sitesChecked,
        public readonly int $sitesDown,
        /** @var Collection<int, SiteCheckResult> */
        public readonly Collection $siteResults,
        public readonly \DateTimeInterface $startedAt,
        public readonly \DateTimeInterface $completedAt,
    ) {}
}
