<?php

namespace App\Services;

use App\DTOs\SiteCheckResult;
use App\Models\Site;
use Illuminate\Support\Collection;

interface HealthCheckServiceInterface
{
    /**
     * Check all pages of all provided sites concurrently.
     *
     * Uses Laravel HTTP Client pool for concurrent requests.
     * Overall cycle timeout of 10 seconds - remaining checks marked unreachable.
     *
     * @param Collection<int, Site> $sites
     * @return Collection<int, SiteCheckResult>
     */
    public function checkAllSites(Collection $sites): Collection;

    /**
     * Check all pages of a single site.
     */
    public function checkSite(Site $site): SiteCheckResult;
}
