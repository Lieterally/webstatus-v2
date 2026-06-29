<?php

namespace App\Livewire;

use App\Enums\SiteStatus;
use App\Models\Category;
use App\Models\CheckResult;
use App\Models\Site;
use App\Services\MonitoringServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    /** Site detail response time filter: 1D, 3D, 7D, 1M */
    public string $siteResponseTimeFilter = '1D';

    /** Site detail downtime filter: 1D, 3D, 7D, 1M */
    public string $siteDowntimeFilter = '7D';

    /** Overview chart site filter: null means all sites averaged */
    public ?int $overviewSiteFilter = null;

    /** Response time chart time filter: 1D, 3D, 7D, 1M */
    public string $responseTimeFilter = '1D';

    /** Downtime chart time filter: 1D, 3D, 7D, 1M */
    public string $downtimeFilter = '7D';

    /**
     * Called automatically when overviewSiteFilter changes (via wire:model.live).
     * Dispatches updated chart data to Alpine via a browser event.
     */
    public function updatedOverviewSiteFilter(): void
    {
        $this->dispatch('overviewChartsUpdated', $this->overviewChartData);
    }

    /**
     * Called automatically when responseTimeFilter changes.
     */
    public function updatedResponseTimeFilter(): void
    {
        $this->dispatch('overviewChartsUpdated', $this->overviewChartData);
    }

    /**
     * Called automatically when downtimeFilter changes.
     */
    public function updatedDowntimeFilter(): void
    {
        $this->dispatch('overviewChartsUpdated', $this->overviewChartData);
    }

    /**
     * Called automatically when siteResponseTimeFilter changes.
     */
    public function updatedSiteResponseTimeFilter(): void
    {
        $this->dispatch('siteChartsUpdated', [
            'responseTimeData' => $this->getSiteResponseTimeFiltered(),
        ]);
    }

    /**
     * Called automatically when siteDowntimeFilter changes.
     */
    public function updatedSiteDowntimeFilter(): void
    {
        $this->dispatch('siteChartsUpdated', [
            'downtimeData' => $this->getSiteDowntimeFiltered(),
        ]);
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
        $this->siteResponseTimeFilter = '1D';
        $this->siteDowntimeFilter = '7D';
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

        // Get response time chart data (filtered)
        $responseTimeData = $this->getSiteResponseTimeFiltered();

        // Get downtime chart data (filtered)
        $downtimeData = $this->getSiteDowntimeFiltered();

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
     * Get response time chart data for the selected site based on siteResponseTimeFilter.
     */
    private function getSiteResponseTimeFiltered(): array
    {
        if (!$this->selectedSiteId) {
            return ['labels' => [], 'data' => [], 'xLabel' => 'Time', 'filter' => $this->siteResponseTimeFilter];
        }

        $now = Carbon::now();
        $labels = [];
        $data = [];

        switch ($this->siteResponseTimeFilter) {
            case '1D':
                $start = $now->copy()->startOfHour()->subHours(23);
                for ($i = 0; $i < 24; $i++) {
                    $bucketStart = $start->copy()->addHours($i);
                    $bucketEnd = $bucketStart->copy()->addHour();
                    $labels[] = $bucketStart->format('H:00');
                    $avgMs = CheckResult::where('site_id', $this->selectedSiteId)
                        ->where('checked_at', '>=', $bucketStart)
                        ->where('checked_at', '<', $bucketEnd)
                        ->where('http_code', '>', 0)
                        ->avg('response_time_ms');
                    $data[] = $avgMs !== null ? round($avgMs / 1000, 3) : null;
                }
                $xLabel = 'Hours';
                break;

            case '3D':
                $start = $now->copy()->startOfDay()->subDays(2);
                for ($i = 0; $i < 12; $i++) {
                    $bucketStart = $start->copy()->addHours($i * 6);
                    $bucketEnd = $bucketStart->copy()->addHours(6);
                    $labels[] = $bucketStart->format('M d H:00');
                    $avgMs = CheckResult::where('site_id', $this->selectedSiteId)
                        ->where('checked_at', '>=', $bucketStart)
                        ->where('checked_at', '<', $bucketEnd)
                        ->where('http_code', '>', 0)
                        ->avg('response_time_ms');
                    $data[] = $avgMs !== null ? round($avgMs / 1000, 3) : null;
                }
                $xLabel = '6-Hour Intervals';
                break;

            case '7D':
                for ($i = 6; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $avgMs = CheckResult::where('site_id', $this->selectedSiteId)
                        ->where('checked_at', '>=', $dayStart)
                        ->where('checked_at', '<', $dayEnd)
                        ->where('http_code', '>', 0)
                        ->avg('response_time_ms');
                    $data[] = $avgMs !== null ? round($avgMs / 1000, 3) : null;
                }
                $xLabel = 'Days';
                break;

            case '1M':
                for ($i = 29; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $avgMs = CheckResult::where('site_id', $this->selectedSiteId)
                        ->where('checked_at', '>=', $dayStart)
                        ->where('checked_at', '<', $dayEnd)
                        ->where('http_code', '>', 0)
                        ->avg('response_time_ms');
                    $data[] = $avgMs !== null ? round($avgMs / 1000, 3) : null;
                }
                $xLabel = 'Days';
                break;

            case '3M':
                for ($i = 12; $i >= 0; $i--) {
                    $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
                    $weekEnd = $weekStart->copy()->endOfWeek();
                    $labels[] = $weekStart->format('M d');
                    $avgMs = CheckResult::where('site_id', $this->selectedSiteId)
                        ->where('checked_at', '>=', $weekStart)
                        ->where('checked_at', '<', $weekEnd)
                        ->where('http_code', '>', 0)
                        ->avg('response_time_ms');
                    $data[] = $avgMs !== null ? round($avgMs / 1000, 3) : null;
                }
                $xLabel = 'Weeks';
                break;

            case '6M':
                for ($i = 25; $i >= 0; $i--) {
                    $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
                    $weekEnd = $weekStart->copy()->endOfWeek();
                    $labels[] = $weekStart->format('M d');
                    $avgMs = CheckResult::where('site_id', $this->selectedSiteId)
                        ->where('checked_at', '>=', $weekStart)
                        ->where('checked_at', '<', $weekEnd)
                        ->where('http_code', '>', 0)
                        ->avg('response_time_ms');
                    $data[] = $avgMs !== null ? round($avgMs / 1000, 3) : null;
                }
                $xLabel = 'Weeks';
                break;

            case '1Y':
                for ($i = 11; $i >= 0; $i--) {
                    $monthStart = $now->copy()->subMonths($i)->startOfMonth();
                    $monthEnd = $monthStart->copy()->endOfMonth();
                    $labels[] = $monthStart->format('M Y');
                    $avgMs = CheckResult::where('site_id', $this->selectedSiteId)
                        ->where('checked_at', '>=', $monthStart)
                        ->where('checked_at', '<', $monthEnd)
                        ->where('http_code', '>', 0)
                        ->avg('response_time_ms');
                    $data[] = $avgMs !== null ? round($avgMs / 1000, 3) : null;
                }
                $xLabel = 'Months';
                break;

            default:
                for ($i = 29; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $avgMs = CheckResult::where('site_id', $this->selectedSiteId)
                        ->where('checked_at', '>=', $dayStart)
                        ->where('checked_at', '<', $dayEnd)
                        ->where('http_code', '>', 0)
                        ->avg('response_time_ms');
                    $data[] = $avgMs !== null ? round($avgMs / 1000, 3) : null;
                }
                $xLabel = 'Days';
                break;
        }

        return ['labels' => $labels, 'data' => $data, 'xLabel' => $xLabel, 'filter' => $this->siteResponseTimeFilter];
    }

    /**
     * Get downtime chart data for the selected site based on siteDowntimeFilter.
     */
    private function getSiteDowntimeFiltered(): array
    {
        if (!$this->selectedSiteId) {
            return ['labels' => [], 'data' => [], 'yLabel' => '', 'yMax' => 24, 'xLabel' => 'Time', 'unit' => 'hours', 'totalHours' => '0h', 'filter' => $this->siteDowntimeFilter];
        }

        $now = Carbon::now();
        $labels = [];
        $data = [];

        // Temporarily set overviewSiteFilter to use the shared getDowntimeSeconds method
        $originalFilter = $this->overviewSiteFilter;
        $this->overviewSiteFilter = $this->selectedSiteId;

        switch ($this->siteDowntimeFilter) {
            case '1D':
                $start = $now->copy()->startOfHour()->subHours(23);
                for ($i = 0; $i < 24; $i++) {
                    $bucketStart = $start->copy()->addHours($i);
                    $bucketEnd = $bucketStart->copy()->addHour();
                    $labels[] = $bucketStart->format('H:00');
                    $seconds = $this->getDowntimeSeconds($bucketStart, $bucketEnd);
                    $data[] = round($seconds / 3600, 4);
                }
                $yLabel = 'Hours';
                $yMax = 1;
                $xLabel = 'Hours';
                $unit = 'hours';
                break;

            case '3D':
                $start = $now->copy()->startOfDay()->subDays(2);
                for ($i = 0; $i < 12; $i++) {
                    $bucketStart = $start->copy()->addHours($i * 6);
                    $bucketEnd = $bucketStart->copy()->addHours(6);
                    $labels[] = $bucketStart->format('M d H:00');
                    $seconds = $this->getDowntimeSeconds($bucketStart, $bucketEnd);
                    $data[] = round($seconds / 3600, 4);
                }
                $yLabel = 'Hours per 6h';
                $yMax = 6;
                $xLabel = '6-Hour Intervals';
                $unit = 'hours';
                break;

            case '7D':
                for ($i = 6; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $seconds = $this->getDowntimeSeconds($dayStart, $dayEnd);
                    $data[] = round($seconds / 3600, 2);
                }
                $yLabel = 'Hours per day';
                $yMax = 24;
                $xLabel = 'Days';
                $unit = 'hours';
                break;

            case '1M':
                for ($i = 29; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $seconds = $this->getDowntimeSeconds($dayStart, $dayEnd);
                    $data[] = round($seconds / 3600, 2);
                }
                $yLabel = 'Hours per day';
                $yMax = 24;
                $xLabel = 'Days';
                $unit = 'hours';
                break;

            case '3M':
                for ($i = 12; $i >= 0; $i--) {
                    $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
                    $weekEnd = $weekStart->copy()->endOfWeek();
                    $labels[] = $weekStart->format('M d');
                    $seconds = $this->getDowntimeSeconds($weekStart, $weekEnd);
                    $data[] = round($seconds / 3600, 2);
                }
                $yLabel = 'Hours per week';
                $yMax = 168;
                $xLabel = 'Weeks';
                $unit = 'hours';
                break;

            case '6M':
                for ($i = 25; $i >= 0; $i--) {
                    $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
                    $weekEnd = $weekStart->copy()->endOfWeek();
                    $labels[] = $weekStart->format('M d');
                    $seconds = $this->getDowntimeSeconds($weekStart, $weekEnd);
                    $data[] = round($seconds / 3600, 2);
                }
                $yLabel = 'Hours per week';
                $yMax = 168;
                $xLabel = 'Weeks';
                $unit = 'hours';
                break;

            case '1Y':
                for ($i = 11; $i >= 0; $i--) {
                    $monthStart = $now->copy()->subMonths($i)->startOfMonth();
                    $monthEnd = $monthStart->copy()->endOfMonth();
                    $labels[] = $monthStart->format('M Y');
                    $seconds = $this->getDowntimeSeconds($monthStart, $monthEnd);
                    $data[] = round($seconds / 3600, 2);
                }
                $yLabel = 'Hours per month';
                $yMax = null;
                $xLabel = 'Months';
                $unit = 'hours';
                break;

            default:
                for ($i = 29; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $seconds = $this->getDowntimeSeconds($dayStart, $dayEnd);
                    $data[] = round($seconds / 3600, 2);
                }
                $yLabel = 'Hours per day';
                $yMax = 24;
                $xLabel = 'Days';
                $unit = 'hours';
                break;
        }

        // Restore original filter
        $this->overviewSiteFilter = $originalFilter;

        $totalSeconds = array_sum(array_map(function ($val) use ($unit) {
            return $unit === 'minutes' ? $val * 60 : $val * 3600;
        }, $data));

        if ($totalSeconds >= 3600) {
            $totalLabel = round($totalSeconds / 3600, 2) . 'h';
        } else {
            $totalLabel = round($totalSeconds / 60, 1) . 'm';
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'yLabel' => $yLabel,
            'yMax' => $yMax,
            'xLabel' => $xLabel,
            'unit' => $unit,
            'totalHours' => $totalLabel,
            'filter' => $this->siteDowntimeFilter,
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
     * Get overview chart data (response time and downtime) with independent time filters.
     * Cached for 60 seconds to reduce query load during polling.
     */
    public function getOverviewChartDataProperty(): array
    {
        $cacheKey = "overview_chart_{$this->overviewSiteFilter}_{$this->responseTimeFilter}_{$this->downtimeFilter}";

        return Cache::remember($cacheKey, 60, fn() => [
            'response' => $this->getResponseTimeChartDataFiltered(),
            'downtime' => $this->getDowntimeChartDataFiltered(),
        ]);
    }

    /**
     * Get response time chart data based on the selected time filter.
     * Uses a single batch query instead of one query per bucket.
     */
    private function getResponseTimeChartDataFiltered(): array
    {
        $now = Carbon::now();
        $labels = [];
        $buckets = [];

        switch ($this->responseTimeFilter) {
            case '1D':
                $start = $now->copy()->startOfHour()->subHours(23);
                for ($i = 0; $i < 24; $i++) {
                    $bucketStart = $start->copy()->addHours($i);
                    $bucketEnd = $bucketStart->copy()->addHour();
                    $labels[] = $bucketStart->format('H:00');
                    $buckets[] = [$bucketStart, $bucketEnd];
                }
                $xLabel = 'Hours';
                break;

            case '3D':
                $start = $now->copy()->startOfDay()->subDays(2);
                for ($i = 0; $i < 12; $i++) {
                    $bucketStart = $start->copy()->addHours($i * 6);
                    $bucketEnd = $bucketStart->copy()->addHours(6);
                    $labels[] = $bucketStart->format('M d H:00');
                    $buckets[] = [$bucketStart, $bucketEnd];
                }
                $xLabel = '6-Hour Intervals';
                break;

            case '7D':
                for ($i = 6; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $buckets[] = [$dayStart, $dayEnd];
                }
                $xLabel = 'Days';
                break;

            case '1M':
                for ($i = 29; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $buckets[] = [$dayStart, $dayEnd];
                }
                $xLabel = 'Days';
                break;

            case '3M':
                for ($i = 12; $i >= 0; $i--) {
                    $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
                    $weekEnd = $weekStart->copy()->endOfWeek();
                    $labels[] = $weekStart->format('M d');
                    $buckets[] = [$weekStart, $weekEnd];
                }
                $xLabel = 'Weeks';
                break;

            case '6M':
                for ($i = 25; $i >= 0; $i--) {
                    $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
                    $weekEnd = $weekStart->copy()->endOfWeek();
                    $labels[] = $weekStart->format('M d');
                    $buckets[] = [$weekStart, $weekEnd];
                }
                $xLabel = 'Weeks';
                break;

            case '1Y':
                for ($i = 11; $i >= 0; $i--) {
                    $monthStart = $now->copy()->subMonths($i)->startOfMonth();
                    $monthEnd = $monthStart->copy()->endOfMonth();
                    $labels[] = $monthStart->format('M Y');
                    $buckets[] = [$monthStart, $monthEnd];
                }
                $xLabel = 'Months';
                break;

            default:
                for ($i = 29; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $buckets[] = [$dayStart, $dayEnd];
                }
                $xLabel = 'Days';
                break;
        }

        $data = $this->getBatchAvgResponseTimes($buckets);

        return ['labels' => $labels, 'data' => $data, 'xLabel' => $xLabel, 'filter' => $this->responseTimeFilter];
    }

    /**
     * Get average response time (in seconds) for a time bucket, optionally filtered by site.
     */
    private function getAvgResponseTime(Carbon $start, Carbon $end): ?float
    {
        $query = CheckResult::where('checked_at', '>=', $start)
            ->where('checked_at', '<', $end)
            ->where('http_code', '>', 0);

        if ($this->overviewSiteFilter) {
            $query->where('site_id', $this->overviewSiteFilter);
        }

        $avgMs = $query->avg('response_time_ms');
        return $avgMs !== null ? round($avgMs / 1000, 3) : null;
    }

    /**
     * Batch-fetch average response times for all buckets in a single query.
     * Returns an array of nullable floats (seconds) indexed by bucket position.
     */
    private function getBatchAvgResponseTimes(array $buckets): array
    {
        if (empty($buckets)) {
            return [];
        }

        $overallStart = $buckets[0][0];
        $overallEnd = end($buckets)[1];

        // Build a CASE expression to assign each row to a bucket index
        $caseExpression = 'CASE';
        foreach ($buckets as $i => [$start, $end]) {
            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');
            $caseExpression .= " WHEN checked_at >= '{$startStr}' AND checked_at < '{$endStr}' THEN {$i}";
        }
        $caseExpression .= ' END';

        $query = CheckResult::where('checked_at', '>=', $overallStart)
            ->where('checked_at', '<', $overallEnd)
            ->where('http_code', '>', 0)
            ->selectRaw("({$caseExpression}) as bucket_idx, AVG(response_time_ms) as avg_ms")
            ->havingRaw("bucket_idx IS NOT NULL")
            ->groupByRaw("bucket_idx");

        if ($this->overviewSiteFilter) {
            $query->where('site_id', $this->overviewSiteFilter);
        }

        $results = $query->pluck('avg_ms', 'bucket_idx');

        $data = [];
        for ($i = 0; $i < count($buckets); $i++) {
            $avg = $results[$i] ?? null;
            $data[] = $avg !== null ? round((float) $avg / 1000, 3) : null;
        }

        return $data;
    }

    /**
     * Get downtime chart data based on the selected time filter.
     * Optimized: fetches all data in a single lightweight query and processes in PHP.
     */
    private function getDowntimeChartDataFiltered(): array
    {
        $now = Carbon::now();
        $labels = [];
        $buckets = [];

        switch ($this->downtimeFilter) {
            case '1D':
                $start = $now->copy()->startOfHour()->subHours(23);
                for ($i = 0; $i < 24; $i++) {
                    $bucketStart = $start->copy()->addHours($i);
                    $bucketEnd = $bucketStart->copy()->addHour();
                    $labels[] = $bucketStart->format('H:00');
                    $buckets[] = [$bucketStart, $bucketEnd];
                }
                $yLabel = 'Hours';
                $yMax = 1;
                $xLabel = 'Hours';
                $unit = 'hours';
                break;

            case '3D':
                $start = $now->copy()->startOfDay()->subDays(2);
                for ($i = 0; $i < 12; $i++) {
                    $bucketStart = $start->copy()->addHours($i * 6);
                    $bucketEnd = $bucketStart->copy()->addHours(6);
                    $labels[] = $bucketStart->format('M d H:00');
                    $buckets[] = [$bucketStart, $bucketEnd];
                }
                $yLabel = 'Hours per 6h';
                $yMax = 6;
                $xLabel = '6-Hour Intervals';
                $unit = 'hours';
                break;

            case '7D':
                for ($i = 6; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $buckets[] = [$dayStart, $dayEnd];
                }
                $yLabel = 'Hours per day';
                $yMax = 24;
                $xLabel = 'Days';
                $unit = 'hours';
                break;

            case '1M':
                for ($i = 29; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $buckets[] = [$dayStart, $dayEnd];
                }
                $yLabel = 'Hours per day';
                $yMax = 24;
                $xLabel = 'Days';
                $unit = 'hours';
                break;

            case '3M':
                for ($i = 12; $i >= 0; $i--) {
                    $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
                    $weekEnd = $weekStart->copy()->endOfWeek();
                    $labels[] = $weekStart->format('M d');
                    $buckets[] = [$weekStart, $weekEnd];
                }
                $yLabel = 'Hours per week';
                $yMax = 168;
                $xLabel = 'Weeks';
                $unit = 'hours';
                break;

            case '6M':
                for ($i = 25; $i >= 0; $i--) {
                    $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
                    $weekEnd = $weekStart->copy()->endOfWeek();
                    $labels[] = $weekStart->format('M d');
                    $buckets[] = [$weekStart, $weekEnd];
                }
                $yLabel = 'Hours per week';
                $yMax = 168;
                $xLabel = 'Weeks';
                $unit = 'hours';
                break;

            case '1Y':
                for ($i = 11; $i >= 0; $i--) {
                    $monthStart = $now->copy()->subMonths($i)->startOfMonth();
                    $monthEnd = $monthStart->copy()->endOfMonth();
                    $labels[] = $monthStart->format('M Y');
                    $buckets[] = [$monthStart, $monthEnd];
                }
                $yLabel = 'Hours per month';
                $yMax = null;
                $xLabel = 'Months';
                $unit = 'hours';
                break;

            default:
                for ($i = 29; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $dayStart->copy()->endOfDay();
                    $labels[] = $dayStart->format('M d');
                    $buckets[] = [$dayStart, $dayEnd];
                }
                $yLabel = 'Hours per day';
                $yMax = 24;
                $xLabel = 'Days';
                $unit = 'hours';
                break;
        }

        // Single query: get per-cycle down/up summary across the entire time range
        $overallStart = $buckets[0][0];
        $overallEnd = end($buckets)[1];

        $cycleData = $this->fetchCycleDownSummary($overallStart, $overallEnd);

        // Calculate downtime per bucket and down sites per bucket
        $data = [];
        $downSites = [];
        $siteNames = Site::pluck('name', 'id')->toArray();

        foreach ($buckets as [$bucketStart, $bucketEnd]) {
            $bucketStartTs = $bucketStart->timestamp;
            $bucketEndTs = $bucketEnd->timestamp;

            // Filter cycles that fall within this bucket
            $bucketCycles = [];
            $bucketDownSiteIds = [];
            foreach ($cycleData as $cycle) {
                if ($cycle['timestamp'] >= $bucketStartTs && $cycle['timestamp'] < $bucketEndTs) {
                    $bucketCycles[] = $cycle;
                    if ($cycle['has_down']) {
                        foreach ($cycle['down_site_ids'] as $siteId) {
                            $bucketDownSiteIds[$siteId] = true;
                        }
                    }
                }
            }

            // Calculate downtime using outage window detection
            $seconds = $this->calculateDowntimeFromCycles($bucketCycles, $bucketEnd);
            $data[] = round($seconds / 3600, $unit === 'hours' && $yMax <= 6 ? 4 : 2);

            // Collect down site names for tooltip
            $names = [];
            foreach (array_keys($bucketDownSiteIds) as $siteId) {
                if (isset($siteNames[$siteId])) {
                    $names[] = $siteNames[$siteId];
                }
            }
            $downSites[] = $names;
        }

        $totalSeconds = array_sum(array_map(fn($val) => $val * 3600, $data));

        if ($totalSeconds >= 3600) {
            $totalLabel = round($totalSeconds / 3600, 2) . 'h';
        } else {
            $totalLabel = round($totalSeconds / 60, 1) . 'm';
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'yLabel' => $yLabel,
            'yMax' => $yMax,
            'xLabel' => $xLabel,
            'unit' => $unit,
            'totalHours' => $totalLabel,
            'filter' => $this->downtimeFilter,
            'downSites' => $downSites,
        ];
    }

    /**
     * Fetch a lightweight per-cycle summary: cycle_id, min checked_at, whether any page was down, and which sites were down.
     * Uses a single raw query with GROUP BY instead of hydrating all CheckResult models.
     *
     * @return array<int, array{cycle_id: int, timestamp: int, has_down: bool, down_site_ids: array}>
     */
    private function fetchCycleDownSummary(Carbon $start, Carbon $end): array
    {
        $query = DB::table('check_results')
            ->where('checked_at', '>=', $start)
            ->where('checked_at', '<', $end)
            ->select([
                'cycle_id',
                'site_id',
                DB::raw('MIN(checked_at) as first_checked_at'),
                DB::raw('SUM(CASE WHEN http_code = 0 OR http_code < 200 OR http_code >= 400 THEN 1 ELSE 0 END) as down_count'),
            ])
            ->groupBy('cycle_id', 'site_id');

        if ($this->overviewSiteFilter) {
            $query->where('site_id', $this->overviewSiteFilter);
        }

        $rows = $query->get();

        // Group by cycle_id to build per-cycle summaries
        $cycleMap = [];
        foreach ($rows as $row) {
            $cycleId = $row->cycle_id;
            if (!isset($cycleMap[$cycleId])) {
                $cycleMap[$cycleId] = [
                    'cycle_id' => $cycleId,
                    'timestamp' => strtotime($row->first_checked_at),
                    'has_down' => false,
                    'down_site_ids' => [],
                ];
            }

            if ($row->down_count > 0) {
                $cycleMap[$cycleId]['has_down'] = true;
                $cycleMap[$cycleId]['down_site_ids'][] = $row->site_id;
            }

            // Use earliest timestamp across all sites in the cycle
            $rowTs = strtotime($row->first_checked_at);
            if ($rowTs < $cycleMap[$cycleId]['timestamp']) {
                $cycleMap[$cycleId]['timestamp'] = $rowTs;
            }
        }

        // Sort by timestamp
        $cycles = array_values($cycleMap);
        usort($cycles, fn($a, $b) => $a['timestamp'] - $b['timestamp']);

        return $cycles;
    }

    /**
     * Calculate downtime seconds from a pre-sorted array of cycle summaries.
     */
    private function calculateDowntimeFromCycles(array $cycles, Carbon $bucketEnd): int
    {
        if (empty($cycles)) {
            return 0;
        }

        $downtimeSeconds = 0;
        $outageStart = null;

        foreach ($cycles as $cycle) {
            if ($cycle['has_down'] && $outageStart === null) {
                $outageStart = $cycle['timestamp'];
            } elseif (!$cycle['has_down'] && $outageStart !== null) {
                $downtimeSeconds += $cycle['timestamp'] - $outageStart;
                $outageStart = null;
            }
        }

        // If still in outage at end of bucket
        if ($outageStart !== null) {
            $closeTs = min(time(), $bucketEnd->timestamp);
            $downtimeSeconds += $closeTs - $outageStart;
        }

        return $downtimeSeconds;
    }

    /**
     * Calculate downtime seconds within a time bucket using cycle-based outage detection.
     * (Kept for per-site detail view usage where it operates on smaller data sets)
     */
    private function getDowntimeSeconds(Carbon $start, Carbon $end): int
    {
        $query = CheckResult::where('checked_at', '>=', $start)
            ->where('checked_at', '<', $end);

        if ($this->overviewSiteFilter) {
            $query->where('site_id', $this->overviewSiteFilter);
        }

        $results = $query->orderBy('checked_at')->select('cycle_id', 'http_code', 'checked_at')->get();

        if ($results->isEmpty()) {
            return 0;
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

        // Measure outage windows
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

        // If still in outage at end of bucket
        if ($outageStart !== null) {
            $closeTime = Carbon::now()->lt($end) ? Carbon::now() : $end;
            $downtimeSeconds += $outageStart->diffInSeconds($closeTime);
        }

        return $downtimeSeconds;
    }

    public function render(): View
    {
        $sites = $this->getSites();
        $categories = Category::orderBy('name')->get();

        // Batch-compute metrics for all displayed sites in 2 queries instead of 2×N
        $siteIds = $sites->pluck('id')->toArray();
        $siteMetrics = Cache::remember(
            'site_metrics_' . md5(implode(',', $siteIds)),
            30,
            function () use ($siteIds) {
                $since = Carbon::now()->subHours(24);

                // Batch avg response times
                $avgTimes = CheckResult::whereIn('site_id', $siteIds)
                    ->where('checked_at', '>=', $since)
                    ->where('error_type', 'none')
                    ->where('response_time_ms', '>', 0)
                    ->selectRaw('site_id, AVG(response_time_ms) as avg_ms')
                    ->groupBy('site_id')
                    ->pluck('avg_ms', 'site_id');

                // Batch latest HTTP codes (get the most recent check per site)
                $latestCodes = CheckResult::whereIn('site_id', $siteIds)
                    ->whereIn('id', function ($query) use ($siteIds) {
                        $query->selectRaw('MAX(id)')
                            ->from('check_results')
                            ->whereIn('site_id', $siteIds)
                            ->groupBy('site_id');
                    })
                    ->pluck('http_code', 'site_id');

                $metrics = [];
                foreach ($siteIds as $id) {
                    $metrics[$id] = [
                        'avg_response_time_24h' => round((float) ($avgTimes[$id] ?? 0), 1),
                        'latest_http_code' => $latestCodes[$id] ?? null,
                    ];
                }
                return $metrics;
            }
        );

        return view('livewire.dashboard', [
            'sites' => $sites,
            'categories' => $categories,
            'siteMetrics' => $siteMetrics,
            'allSites' => Site::orderBy('name')->get(['id', 'name', 'base_url']),
            'overviewChartData' => $this->overviewChartData,
            'overviewSiteInfo' => $this->overviewSiteFilter ? Site::find($this->overviewSiteFilter, ['name', 'base_url']) : null,
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
