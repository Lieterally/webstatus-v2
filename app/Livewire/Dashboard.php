<?php

namespace App\Livewire;

use App\Enums\SiteStatus;
use App\Models\Category;
use App\Models\CheckResult;
use App\Models\Site;
use App\Services\MonitoringServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;
    /** Total number of monitored sites */
    public int $totalSites = 0;

    /** Number of sites that are down (totally_down or partially_down) */
    public int $sitesDown = 0;

    /** Number of sites that are up */
    public int $sitesUp = 0;

    /** Datetime of the last completed checking cycle (YYYY-MM-DD HH:mm:ss) or null */
    public ?string $lastCycleDatetime = null;

    /** Countdown seconds remaining until next cycle */
    public int $countdownSeconds = 0;

    /** Whether a cycle is currently in progress */
    public bool $cycleInProgress = false;

    /** Full cycle interval in seconds (used to reset countdown after refresh) */
    public int $cycleIntervalSeconds = 0;

    /** Whether the manual refresh was just triggered (for UI feedback) */
    public bool $isRefreshing = false;

    /** Whether the live log drawer is open */
    public bool $showLogDrawer = false;

    /** View mode: 'card' or 'table' */
    public string $viewMode = 'card';

    /** Category filter: null means all categories */
    public ?int $categoryFilter = null;

    /** Search query for site name filtering */
    public string $siteSearch = '';

    /** Status filter: empty string means all statuses */
    public string $statusFilter = '';

    /**
     * Reset pagination when filters change.
     */
    public function updatingSiteSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    /** Currently selected site ID for detailed view (null = no selection) */
    public ?int $selectedSiteId = null;

    /** Whether a per-site refresh is in progress */
    public bool $isSiteRefreshing = false;

    /** Overview chart site filter: null means all sites averaged */
    public ?int $overviewSiteFilter = null;

    /**
     * Called automatically when overviewSiteFilter changes (via wire:model.live).
     * Dispatches updated chart data to Alpine via a browser event.
     */
    public function updatedOverviewSiteFilter(): void
    {
        $this->dispatch('overviewChartsUpdated', $this->overviewChartData);
    }

    public function mount(): void
    {
        $this->loadDashboardData();
    }

    /**
     * Livewire polling hook - called every 2 seconds.
     *
     * When the countdown has expired (0 seconds remaining) and no cycle is in progress,
     * this triggers the monitoring cycle in the background. This ensures cycles run even
     * without an external cron/scheduler (e.g., on Laragon/Windows) without blocking
     * the web request.
     */
    public function poll(): void
    {
        $monitoringService = app(MonitoringServiceInterface::class);
        $cycleState = $monitoringService->getCycleState();

        // Track previous cycle datetime to detect when a new cycle completes
        $previousCycleDatetime = $this->lastCycleDatetime;

        // Release the trigger lock once the cycle has completed (countdown reset means cycle finished)
        if ($cycleState->countdownSeconds > 0) {
            \Illuminate\Support\Facades\Cache::forget('poll_cycle_trigger_lock');
        }

        // Auto-trigger cycle when countdown has expired and no cycle is running
        if ($cycleState->countdownSeconds === 0 && !$cycleState->cycleInProgress && !$this->isRefreshing) {
            // Use an atomic lock to prevent multiple poll requests from spawning duplicate processes.
            $lock = \Illuminate\Support\Facades\Cache::lock('poll_cycle_trigger_lock', 120);

            if ($lock->get()) {
                $artisan = base_path('artisan');

                // Use the CLI php.exe (not php-cgi.exe which PHP_BINARY may point to under FPM)
                $phpDir = dirname(PHP_BINARY);
                $phpCli = $phpDir . DIRECTORY_SEPARATOR . 'php.exe';
                if (!file_exists($phpCli)) {
                    $phpCli = 'php'; // Fallback to PATH
                }

                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    pclose(popen("start /B \"\" \"{$phpCli}\" \"{$artisan}\" app:run-monitoring-cycle > NUL 2>&1", 'r'));
                } else {
                    exec("php \"{$artisan}\" app:run-monitoring-cycle > /dev/null 2>&1 &");
                }
            }
        }

        $this->loadDashboardData();

        // If cycle datetime changed, a new cycle just completed — push updated chart data to Alpine
        if ($this->lastCycleDatetime !== $previousCycleDatetime && $this->lastCycleDatetime !== null) {
            $this->isRefreshing = false;
            $this->dispatch('overviewChartsUpdated', $this->overviewChartData);
        }

        // Also reset isRefreshing if no cycle is in progress (safety net)
        if ($this->isRefreshing && !$this->cycleInProgress) {
            $this->isRefreshing = false;
        }
    }

    /**
     * Trigger a manual refresh for all sites.
     *
     * Spawns the monitoring cycle as a background process (non-blocking).
     * The 2-second polling will detect when the cycle completes and update the UI.
     */
    public function refreshAll(): void
    {
        $monitoringService = app(MonitoringServiceInterface::class);

        // Prevent duplicate refresh if already in progress
        if ($monitoringService->isCycleInProgress()) {
            return;
        }

        $this->isRefreshing = true;

        // Spawn the cycle as a background process instead of blocking
        $artisan = base_path('artisan');

        $phpDir = dirname(PHP_BINARY);
        $phpCli = $phpDir . DIRECTORY_SEPARATOR . 'php.exe';
        if (!file_exists($phpCli)) {
            $phpCli = 'php';
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B \"\" \"{$phpCli}\" \"{$artisan}\" app:run-monitoring-cycle > NUL 2>&1", 'r'));
        } else {
            exec("php \"{$artisan}\" app:run-monitoring-cycle > /dev/null 2>&1 &");
        }

        // UI will update via polling when cycle completes
        $this->loadDashboardData();
    }

    /**
     * Select a site to view its detailed information.
     */
    public function selectSite(int $siteId): void
    {
        $this->selectedSiteId = $siteId;
    }

    /**
     * Close the detailed site view.
     */
    public function closeSiteDetail(): void
    {
        $this->selectedSiteId = null;
    }

    /**
     * Trigger a manual refresh for a specific site.
     */
    public function refreshSite(int $siteId): void
    {
        $monitoringService = app(MonitoringServiceInterface::class);

        $this->isSiteRefreshing = true;

        // Execute single site refresh
        $monitoringService->refreshSite($siteId);

        $this->isSiteRefreshing = false;

        // Reload data
        $this->loadDashboardData();
    }

    /**
     * Toggle the live log drawer.
     */
    public function toggleLogDrawer(): void
    {
        $this->showLogDrawer = !$this->showLogDrawer;
    }

    /**
     * Get the live cycle log entries from cache.
     */
    public function getCycleLogsProperty(): array
    {
        return \Illuminate\Support\Facades\Cache::get('monitoring_cycle_live_log', []);
    }

    /**
     * Get the total pages count for the current cycle.
     */
    public function getCycleTotalPagesProperty(): int
    {
        return (int) \Illuminate\Support\Facades\Cache::get('monitoring_cycle_total_pages', 0);
    }

    /**
     * Get detailed data for the selected site including pages, charts, and down info.
     */
    public function getSelectedSiteDataProperty(): ?array
    {
        if (!$this->selectedSiteId) {
            return null;
        }

        $site = Site::with(['pages', 'responsiblePerson', 'category'])->find($this->selectedSiteId);

        if (!$site) {
            $this->selectedSiteId = null;
            return null;
        }

        // Get latest check results for each page
        $pageResults = $this->getLatestPageResults($site);

        // Get response time chart data (last 24 hours, 1-hour intervals)
        $responseTimeData = $this->getResponseTimeChartData($site);

        // Get downtime chart data (last 30 days)
        $downtimeData = $this->getDowntimeChartData($site);

        // Calculate down duration if site is down
        $downInfo = $this->getDownInfo($site);

        // Get downtime history (last 30 days of outage events)
        $downtimeHistory = $this->getDowntimeHistory($site);

        return [
            'site' => $site,
            'pageResults' => $pageResults,
            'responseTimeData' => $responseTimeData,
            'downtimeData' => $downtimeData,
            'downInfo' => $downInfo,
            'downtimeHistory' => $downtimeHistory,
        ];
    }

    /**
     * Get the latest check results for each page of a site.
     */
    private function getLatestPageResults(Site $site): array
    {
        $results = [];

        foreach ($site->pages as $page) {
            $latestResult = CheckResult::where('page_id', $page->id)
                ->orderBy('checked_at', 'desc')
                ->first();

            $results[] = [
                'page_id' => $page->id,
                'path' => $page->path,
                'full_url' => rtrim($site->base_url, '/') . $page->path,
                'http_code' => $latestResult?->http_code ?? null,
                'response_time_ms' => $latestResult?->response_time_ms ?? null,
                'error_type' => $latestResult?->error_type?->value ?? null,
                'checked_at' => $latestResult?->checked_at?->format('Y-m-d H:i:s') ?? null,
            ];
        }

        return $results;
    }

    /**
     * Get response time chart data for last 24 hours in 1-hour intervals.
     * Y-axis: seconds, X-axis: last 24 hours.
     */
    private function getResponseTimeChartData(Site $site): array
    {
        $now = Carbon::now();
        // Align to the start of the current hour, then go back 23 hours
        // This gives us 24 complete hourly buckets: from 23 hours ago to the current hour
        $start = $now->copy()->startOfHour()->subHours(23);

        $labels = [];
        $data = [];

        for ($i = 0; $i < 24; $i++) {
            $hourStart = $start->copy()->addHours($i);
            $hourEnd = $hourStart->copy()->addHour();

            $labels[] = $hourStart->format('H:00');

            // Get average response time for this hour across all pages of the site
            $avgMs = CheckResult::where('site_id', $site->id)
                ->where('checked_at', '>=', $hourStart)
                ->where('checked_at', '<', $hourEnd)
                ->where('http_code', '>', 0) // Only reachable pages
                ->avg('response_time_ms');

            // Convert ms to seconds for Y-axis, null if no data
            $data[] = $avgMs !== null ? round($avgMs / 1000, 3) : null;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get downtime chart data for last 30 days.
     * Y-axis: hours of downtime per day, X-axis: last 30 days.
     */
    private function getDowntimeChartData(Site $site): array
    {
        $labels = [];
        $data = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->startOfDay();
            $dayEnd = $day->copy()->endOfDay();

            $labels[] = $day->format('M d');

            // Get all check results for this site on this day, ordered by time
            $results = CheckResult::where('site_id', $site->id)
                ->where('checked_at', '>=', $day)
                ->where('checked_at', '<=', $dayEnd)
                ->orderBy('checked_at')
                ->select('cycle_id', 'http_code', 'checked_at')
                ->get();

            if ($results->isEmpty()) {
                $data[] = 0;
                continue;
            }

            // Group by cycle and determine if each cycle was "down"
            // Then calculate actual elapsed time between first down and recovery
            $cycles = $results->groupBy('cycle_id');
            $cycleTimestamps = [];

            foreach ($cycles as $cycleId => $cycleResults) {
                $someDown = $cycleResults->contains(function ($result) {
                    $code = $result->http_code;
                    return $code === 0 || $code < 200 || $code >= 400;
                });

                $cycleTimestamps[] = [
                    'time' => Carbon::parse($cycleResults->first()->checked_at),
                    'down' => $someDown,
                ];
            }

            // Sort by time
            usort($cycleTimestamps, fn($a, $b) => $a['time']->timestamp - $b['time']->timestamp);

            // Calculate actual downtime by measuring outage windows
            $downtimeSeconds = 0;
            $outageStart = null;

            for ($j = 0; $j < count($cycleTimestamps); $j++) {
                $current = $cycleTimestamps[$j];

                if ($current['down'] && $outageStart === null) {
                    // Outage begins
                    $outageStart = $current['time'];
                } elseif (!$current['down'] && $outageStart !== null) {
                    // Outage ends — measure from start to this recovery point
                    $downtimeSeconds += $outageStart->diffInSeconds($current['time']);
                    $outageStart = null;
                }
            }

            // If still in outage at end of data, close it at the last check time (or end of day if today)
            if ($outageStart !== null) {
                $lastTime = end($cycleTimestamps)['time'];
                $closeTime = $i === 0 ? Carbon::now() : $dayEnd;
                $downtimeSeconds += $outageStart->diffInSeconds(min($lastTime, $closeTime));
            }

            $downtimeHours = round($downtimeSeconds / 3600, 2);
            $data[] = min($downtimeHours, 24);
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'totalHours' => round(array_sum($data), 2),
        ];
    }

    /**
     * Get downtime history for a site (last 30 days of outage events).
     * Groups consecutive down checks into outage windows.
     */
    private function getDowntimeHistory(Site $site): array
    {
        $since = Carbon::now()->subDays(30);

        // Get all check results for this site in the last 30 days, ordered by time
        $results = CheckResult::where('site_id', $site->id)
            ->where('checked_at', '>=', $since)
            ->orderBy('checked_at')
            ->select('cycle_id', 'page_id', 'http_code', 'checked_at', 'error_type')
            ->get();

        if ($results->isEmpty()) {
            return [];
        }

        // Get page paths for display
        $pages = $site->pages->pluck('path', 'id')->toArray();

        // Group by cycle and determine down pages per cycle
        $cycles = $results->groupBy('cycle_id');
        $cycleEntries = [];

        foreach ($cycles as $cycleId => $cycleResults) {
            $downPages = [];
            foreach ($cycleResults as $result) {
                $code = $result->http_code;
                if ($code === 0 || $code < 200 || $code >= 400) {
                    $downPages[] = $pages[$result->page_id] ?? '/unknown';
                }
            }

            $cycleEntries[] = [
                'time' => Carbon::parse($cycleResults->first()->checked_at),
                'down' => !empty($downPages),
                'downPages' => $downPages,
            ];
        }

        // Sort by time
        usort($cycleEntries, fn($a, $b) => $a['time']->timestamp - $b['time']->timestamp);

        // Build outage windows
        $history = [];
        $outageStart = null;
        $outagePages = [];

        foreach ($cycleEntries as $entry) {
            if ($entry['down'] && $outageStart === null) {
                $outageStart = $entry['time'];
                $outagePages = $entry['downPages'];
            } elseif ($entry['down'] && $outageStart !== null) {
                // Merge pages
                $outagePages = array_unique(array_merge($outagePages, $entry['downPages']));
            } elseif (!$entry['down'] && $outageStart !== null) {
                // Outage ended
                $duration = $outageStart->diffInSeconds($entry['time']);
                $history[] = [
                    'from' => $outageStart->format('Y-m-d H:i:s'),
                    'to' => $entry['time']->format('Y-m-d H:i:s'),
                    'pages' => implode(', ', $outagePages),
                    'duration' => $this->formatDurationSeconds($duration),
                ];
                $outageStart = null;
                $outagePages = [];
            }
        }

        // If still in outage
        if ($outageStart !== null) {
            $duration = $outageStart->diffInSeconds(Carbon::now());
            $history[] = [
                'from' => $outageStart->format('Y-m-d H:i:s'),
                'to' => 'Ongoing',
                'pages' => implode(', ', $outagePages),
                'duration' => $this->formatDurationSeconds($duration) . ' (ongoing)',
            ];
        }

        // Return most recent first
        return array_reverse($history);
    }

    /**
     * Format seconds into a human-readable duration string.
     */
    private function formatDurationSeconds(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $parts = [];
        if ($days > 0) $parts[] = $days . 'd';
        if ($hours > 0) $parts[] = $hours . 'h';
        if ($minutes > 0) $parts[] = $minutes . 'm';

        return implode(' ', $parts) ?: '0m';
    }

    /**
     * Get down information (first_down_at and duration) for a site.
     */
    private function getDownInfo(Site $site): ?array
    {
        if ($site->status === SiteStatus::Up) {
            return null;
        }

        if (!$site->first_down_at) {
            return null;
        }

        $now = Carbon::now();
        $downSince = $site->first_down_at;
        $diffInMinutes = (int) $downSince->diffInMinutes($now);

        $days = intdiv($diffInMinutes, 1440);
        $hours = intdiv($diffInMinutes % 1440, 60);
        $minutes = $diffInMinutes % 60;

        $duration = '';
        if ($days > 0) {
            $duration .= $days . 'd ';
        }
        if ($hours > 0 || $days > 0) {
            $duration .= $hours . 'h ';
        }
        $duration .= $minutes . 'm';

        return [
            'first_down_at' => $downSince->format('Y-m-d H:i:s'),
            'duration' => trim($duration),
        ];
    }

    /**
     * Load all dashboard summary data from services and database.
     */
    private function loadDashboardData(): void
    {
        $monitoringService = app(MonitoringServiceInterface::class);

        // Get site counts
        $this->totalSites = Site::count();
        // $this->sitesDown = Site::where('status', SiteStatus::PartiallyDown)
        //     ->orWhere('status', SiteStatus::TotallyDown)
        //     ->count();
        $this->sitesDown = Site::where('status', SiteStatus::TotallyDown)->count();
        $this->sitesUp = Site::where('status', SiteStatus::Up)
            ->orWhere('status', SiteStatus::PartiallyDown)
            ->count();
        // $this->sitesUp = Site::where('status', SiteStatus::Up)->count();

        // Get cycle state
        $cycleState = $monitoringService->getCycleState();
        $this->countdownSeconds = $cycleState->countdownSeconds;
        $this->cycleInProgress = $cycleState->cycleInProgress;
        $this->cycleIntervalSeconds = $cycleState->cycleIntervalMinutes * 60;

        // Format last cycle datetime
        if ($cycleState->lastCheckAt) {
            $this->lastCycleDatetime = $cycleState->lastCheckAt->format('Y-m-d H:i:s');
        } else {
            $this->lastCycleDatetime = null;
        }
    }

    /**
     * Get the sorted and filtered list of sites for the website list view.
     */
    private function getSites(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Site::with(['category', 'responsiblePerson']);

        // Apply category filter
        if ($this->categoryFilter !== null) {
            $query->where('category_id', $this->categoryFilter);
        }

        // Apply status filter
        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        // Apply search filter
        if ($this->siteSearch !== '') {
            $query->where('name', 'like', '%' . $this->siteSearch . '%');
        }

        // Sort by status priority: totally_down first, partially_down second, up third, then alphabetical
        $query->orderByRaw("CASE status WHEN 'totally_down' THEN 0 WHEN 'partially_down' THEN 1 WHEN 'up' THEN 2 ELSE 3 END")
            ->orderBy('name');

        return $query->paginate(24);
    }

    /**
     * Get overview chart data (response time and downtime) for all sites or a filtered site.
     */
    public function getOverviewChartDataProperty(): array
    {
        $now = Carbon::now();

        // Response time: last 24 hours, hourly buckets
        $responseLabels = [];
        $responseData = [];
        $start = $now->copy()->startOfHour()->subHours(23);

        for ($i = 0; $i < 24; $i++) {
            $hourStart = $start->copy()->addHours($i);
            $hourEnd = $hourStart->copy()->addHour();
            $responseLabels[] = $hourStart->format('H:00');

            $query = CheckResult::where('checked_at', '>=', $hourStart)
                ->where('checked_at', '<', $hourEnd)
                ->where('http_code', '>', 0);

            if ($this->overviewSiteFilter) {
                $query->where('site_id', $this->overviewSiteFilter);
            }

            $avgMs = $query->avg('response_time_ms');
            $responseData[] = $avgMs !== null ? round($avgMs / 1000, 3) : null;
        }

        // Downtime: last 30 days (actual elapsed time between first down and recovery)
        $downtimeLabels = [];
        $downtimeData = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->startOfDay();
            $dayEnd = $day->copy()->endOfDay();
            $downtimeLabels[] = $day->format('M d');

            $query = CheckResult::where('checked_at', '>=', $day)
                ->where('checked_at', '<=', $dayEnd);

            if ($this->overviewSiteFilter) {
                $query->where('site_id', $this->overviewSiteFilter);
            }

            $results = $query->orderBy('checked_at')->select('cycle_id', 'http_code', 'checked_at', 'site_id')->get();

            if ($results->isEmpty()) {
                $downtimeData[] = 0;
                continue;
            }

            // Group by cycle, determine down/up per cycle
            $cycles = $results->groupBy('cycle_id');
            $cycleTimestamps = [];

            foreach ($cycles as $cycleId => $cycleResults) {
                $someDown = $cycleResults->contains(function ($result) {
                    $code = $result->http_code;
                    return $code === 0 || $code < 200 || $code >= 400;
                });

                $cycleTimestamps[] = [
                    'time' => Carbon::parse($cycleResults->first()->checked_at),
                    'down' => $someDown,
                ];
            }

            usort($cycleTimestamps, fn($a, $b) => $a['time']->timestamp - $b['time']->timestamp);

            // Measure actual outage windows
            $downtimeSeconds = 0;
            $outageStart = null;

            for ($j = 0; $j < count($cycleTimestamps); $j++) {
                $current = $cycleTimestamps[$j];

                if ($current['down'] && $outageStart === null) {
                    $outageStart = $current['time'];
                } elseif (!$current['down'] && $outageStart !== null) {
                    $downtimeSeconds += $outageStart->diffInSeconds($current['time']);
                    $outageStart = null;
                }
            }

            if ($outageStart !== null) {
                $lastTime = end($cycleTimestamps)['time'];
                $closeTime = $i === 0 ? Carbon::now() : $dayEnd;
                $downtimeSeconds += $outageStart->diffInSeconds(min($lastTime, $closeTime));
            }

            $downtimeHours = round($downtimeSeconds / 3600, 2);
            $downtimeData[] = min($downtimeHours, 24);
        }

        return [
            'response' => ['labels' => $responseLabels, 'data' => $responseData],
            'downtime' => ['labels' => $downtimeLabels, 'data' => $downtimeData, 'totalHours' => round(array_sum($downtimeData), 2)],
        ];
    }

    public function render(): View
    {
        $sites = $this->getSites();
        $categories = Category::orderBy('name')->get();

        // Pre-compute 24h avg response times and latest HTTP codes for all displayed sites
        $siteMetrics = [];
        foreach ($sites as $site) {
            $siteMetrics[$site->id] = [
                'avg_response_time_24h' => $this->getSiteAvgResponseTime24h($site->id),
                'latest_http_code' => $this->getSiteLatestHttpCode($site->id),
            ];
        }

        return view('livewire.dashboard', [
            'sites' => $sites,
            'categories' => $categories,
            'siteMetrics' => $siteMetrics,
            'allSites' => Site::orderBy('name')->get(['id', 'name']),
            'overviewChartData' => $this->overviewChartData,
        ]);
    }

    /**
     * Get the 24-hour average response time for a site.
     */
    private function getSiteAvgResponseTime24h(int $siteId): float
    {
        $since = Carbon::now()->subHours(24);

        $avg = CheckResult::where('site_id', $siteId)
            ->where('checked_at', '>=', $since)
            ->where('error_type', 'none')
            ->where('response_time_ms', '>', 0)
            ->avg('response_time_ms');

        return round((float) $avg, 1);
    }

    /**
     * Get the latest HTTP code for a site (from the most recent check).
     */
    private function getSiteLatestHttpCode(int $siteId): ?int
    {
        $result = CheckResult::where('site_id', $siteId)
            ->orderByDesc('checked_at')
            ->first();

        return $result?->http_code;
    }
}
