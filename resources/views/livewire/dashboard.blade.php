<div wire:poll.2s="poll" x-data="{
    countdown: @entangle('countdownSeconds'),
    cycleInProgress: @entangle('cycleInProgress'),
    cycleIntervalSeconds: @entangle('cycleIntervalSeconds'),
    isRefreshing: @entangle('isRefreshing'),
    intervalId: null,

    init() {
        this.startCountdown();

        this.$watch('countdown', () => {
            this.startCountdown();
        });
    },

    startCountdown() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }

        this.intervalId = setInterval(() => {
            if (!this.cycleInProgress && !this.isRefreshing && this.countdown > 0) {
                this.countdown--;
            }
        }, 1000);
    },

    get formattedCountdown() {
        const minutes = Math.floor(this.countdown / 60);
        const seconds = this.countdown % 60;
        return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
    },

    destroy() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
    }
}" x-on:livewire:navigating.window="destroy()">
    {{-- Page Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Dashboard</h1>
            <p class="text-sm text-base-content/60 mt-1">Website monitoring overview</p>
        </div>

        {{-- Refresh All Button + Log Button --}}
        <div class="flex items-center gap-2">
            <button wire:click="refreshAll" wire:loading.attr="disabled" x-bind:disabled="cycleInProgress || isRefreshing"
                class="btn btn-primary btn-md gap-2">

                <!-- Loading Icon -->
                <i wire:loading wire:target="refreshAll" class="fa-solid fa-rotate-right animate-spin text-sm"></i>

                <!-- Normal Icon -->
                <i wire:loading.remove wire:target="refreshAll" x-show="!cycleInProgress && !isRefreshing"
                    class="fa-solid fa-rotate-right text-sm"></i>

                <span wire:loading.remove wire:target="refreshAll">
                    Refresh All Now
                </span>

                <span wire:loading wire:target="refreshAll">
                    Refreshing...
                </span>
            </button>

            {{-- Live Log Button --}}
            <button wire:click="toggleLogDrawer" class="btn btn-ghost btn-lg btn-square" title="View live logs">
                <i class="fa-regular fa-clipboard"></i>
            </button>
        </div>
    </div>

    {{-- Summary Cards using DaisyUI stats --}}
    <div class="stats stats-vertical sm:stats-horizontal bg-base-100 shadow-sm w-full mb-6">
        {{-- Total Sites --}}
        <div class="stat">
            <div class="stat-figure text-primary">
                <i class="fa-regular fa-globe fa-lg"></i>
            </div>
            <div class="stat-title">Total Sites</div>
            <div class="stat-value text-base-content">{{ $totalSites }}</div>
        </div>

        {{-- Sites Down --}}
        <div class="stat">
            <div class="stat-figure text-error">
                <i class="fa-regular fa-warning fa-lg"></i>
            </div>
            <div class="stat-title">Sites Down</div>
            <div class="stat-value text-error">{{ $sitesDown }}</div>
        </div>

        {{-- Sites Up --}}
        <div class="stat">
            <div class="stat-figure text-success">
                <i class="fa-regular fa-circle-check fa-lg"></i>
            </div>
            <div class="stat-title">Sites Up</div>
            <div class="stat-value text-success">{{ $sitesUp }}</div>
        </div>

        {{-- Last Cycle / Countdown --}}
        <div class="stat">
            <div class="stat-figure text-base-content/50">
                <i class="fa-regular fa-clock fa-lg"></i>
            </div>
            <div class="stat-title">Last Cycle</div>
            @if ($lastCycleDatetime)
                <div class="stat-value text-sm font-semibold">{{ $lastCycleDatetime }}</div>
            @else
                <div class="stat-value text-sm font-semibold text-base-content/40 italic">No data yet</div>
            @endif
            <div class="stat-desc">
                <template x-if="cycleInProgress || isRefreshing">
                    <div class="flex items-center gap-1.5">
                        <svg class="animate-spin h-3.5 w-3.5 text-warning" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span class="text-xs font-medium text-warning">Refreshing... (paused)</span>
                    </div>
                </template>
                <template x-if="!cycleInProgress && !isRefreshing">
                    <div class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-base-content/40" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-xs font-medium text-base-content/60">Next cycle in <span class="font-mono"
                                x-text="formattedCountdown"></span></span>
                    </div>
                </template>
            </div>
        </div>
    </div>


    {{-- Overview Charts Section --}}
    <div class="card bg-base-100 shadow-sm mb-6">
        <div class="card-body">
            {{-- Header with site filter --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <h2 class="card-title">Overview</h2>
                <div class="flex items-center gap-2">
                    {{-- <label for="overviewSiteFilter" class="text-sm text-base-content/60">Filter site:</label>
                    <select id="overviewSiteFilter" wire:model.live="overviewSiteFilter"
                        class="select select-bordered select-sm">
                        <option value="">All Sites</option>
                        @foreach ($allSites as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select> --}}

                    {{-- Site Filter --}}
                    {{-- 
                    <div class="dropdown">
                        <div tabindex="0" role="button" class="btn btn-sm">
                            {{ $overviewSiteFilter ? $allSites->firstWhere('id', $overviewSiteFilter)?->name ?? 'All Sites' : 'All Sites' }}
                        </div>

                        <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-10 w-52 p-2 shadow">
                            <li><a wire:click="$set('overviewSiteFilter', '')">All Sites</a></li>

                            @foreach ($allSites as $s)
                                <li>
                                    <a wire:click="$set('overviewSiteFilter', {{ $s->id }})">
                                        {{ $s->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div> --}}

                    <div x-data="{
                        tomSelect: null,
                        init() {
                            this.tomSelect = new TomSelect(this.$refs.overviewSiteSelect, {
                                placeholder: 'All Sites',
                                allowEmptyOption: true,
                                onChange: (value) => {
                                    @this.set('overviewSiteFilter', value || null);
                                }
                            });
                        },
                        destroy() {
                            if (this.tomSelect) {
                                this.tomSelect.destroy();
                            }
                        }
                    }" wire:ignore>
                        <select x-ref="overviewSiteSelect" id="overviewSiteFilter">
                            <option value="">All Sites</option>
                            @foreach ($allSites as $s)
                                <option value="{{ $s->id }}"
                                    {{ $overviewSiteFilter == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Charts grid - wire:key forces full rebuild when filter changes --}}
            {{-- Charts grid - wire:ignore prevents Livewire from destroying canvases on poll --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" wire:ignore>
                <div x-data="{
                    chart: null,
                    init() {
                        this.createChart();
                        Livewire.on('overviewChartsUpdated', (data) => {
                            if (this.chart) { this.chart.destroy(); }
                            this.createChartWithData(data[0].response);
                        });
                    },
                    createChart() {
                        const chartData = @js($overviewChartData['response']);
                        this.createChartWithData(chartData);
                    },
                    createChartWithData(chartData) {
                        const ctx = this.$refs.overviewResponseCanvas;
                        if (!ctx) return;
                        this.chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: chartData.labels,
                                datasets: [{
                                    label: 'Avg Response Time (s)',
                                    data: chartData.data,
                                    borderColor: '#1565C0',
                                    backgroundColor: 'rgba(21, 101, 192, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.3,
                                    spanGaps: true,
                                    pointRadius: 3,
                                    pointHoverRadius: 5,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { callbacks: { label: (c) => c.parsed.y !== null ? c.parsed.y.toFixed(3) + 's' : 'No data' } }
                                },
                                scales: {
                                    y: { beginAtZero: true, title: { display: true, text: 'Seconds' }, grid: { color: 'rgba(0,0,0,0.05)' } },
                                    x: { title: { display: true, text: 'Time (last 24 hours)' }, grid: { display: false } }
                                }
                            }
                        });
                    }
                }">
                    <h3 class="text-sm font-semibold text-base-content/70 mb-3">Response Time (Last 24 Hours)</h3>
                    <div class="bg-base-200/50 rounded-lg p-4" style="height: 280px;">
                        <canvas x-ref="overviewResponseCanvas" style="width: 100%; height: 100%;"></canvas>
                    </div>
                </div>

                <div x-data="{
                    chart: null,
                    init() {
                        this.createChart();
                        Livewire.on('overviewChartsUpdated', (data) => {
                            if (this.chart) { this.chart.destroy(); }
                            this.createChartWithData(data[0].downtime);
                            this.$refs.totalLabel.textContent = 'Total: ' + data[0].downtime.totalHours + 'h';
                        });
                    },
                    createChart() {
                        const chartData = @js($overviewChartData['downtime']);
                        this.createChartWithData(chartData);
                    },
                    createChartWithData(chartData) {
                        const ctx = this.$refs.overviewDowntimeCanvas;
                        if (!ctx) return;
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: chartData.labels,
                                datasets: [{
                                    label: 'Downtime (hours)',
                                    data: chartData.data,
                                    backgroundColor: '#DC2626',
                                    borderColor: '#DC2626',
                                    borderWidth: 1,
                                    borderRadius: 2,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { callbacks: { label: (c) => c.parsed.y.toFixed(2) + ' hours' } }
                                },
                                scales: {
                                    y: { beginAtZero: true, max: 24, title: { display: true, text: 'Hours per day' }, grid: { color: 'rgba(0,0,0,0.05)' } },
                                    x: { title: { display: true, text: 'Last 30 days' }, grid: { display: false } }
                                }
                            }
                        });
                    }
                }">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-base-content/70">Downtime (Last 30 Days)</h3>
                        <span x-ref="totalLabel" class="badge badge-error badge-sm">
                            Total: {{ $overviewChartData['downtime']['totalHours'] }}h
                        </span>
                    </div>
                    <div class="bg-base-200/50 rounded-lg p-4" style="height: 280px;">
                        <canvas x-ref="overviewDowntimeCanvas" style="width: 100%; height: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Site View (shown when a site is selected) --}}
    @if ($selectedSiteId && $this->selectedSiteData)
        @php $siteData = $this->selectedSiteData; @endphp
        <div class="card bg-base-100 shadow-sm mb-6 overflow-hidden" wire:ignore.self
            wire:key="site-detail-{{ $selectedSiteId }}" x-data="{
                responseChart: null,
                downtimeChart: null,
                chartsInitialized: false,
            
                initCharts() {
                    if (this.chartsInitialized) return;
                    this.chartsInitialized = true;
                    this.$nextTick(() => {
                        this.createResponseTimeChart();
                        this.createDowntimeChart();
                    });
                },
            
                createResponseTimeChart() {
                    const ctx = this.$refs.responseTimeCanvas;
                    if (!ctx) return;
                    if (this.responseChart) { this.responseChart.destroy(); }
            
                    const chartData = @js($siteData['responseTimeData']);
            
                    this.responseChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                label: 'Response Time (seconds)',
                                data: chartData.data,
                                borderColor: '#1565C0',
                                backgroundColor: 'rgba(21, 101, 192, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.3,
                                spanGaps: true,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => ctx.parsed.y !== null ? ctx.parsed.y.toFixed(3) + 's' : 'No data'
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: { display: true, text: 'Seconds' },
                                    grid: { color: 'rgba(0,0,0,0.05)' }
                                },
                                x: {
                                    title: { display: true, text: 'Time (last 24 hours)' },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                },
            
                createDowntimeChart() {
                    const ctx = this.$refs.downtimeCanvas;
                    if (!ctx) return;
                    if (this.downtimeChart) { this.downtimeChart.destroy(); }
            
                    const chartData = @js($siteData['downtimeData']);
            
                    this.downtimeChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                label: 'Downtime (hours)',
                                data: chartData.data,
                                backgroundColor: '#DC2626',
                                borderColor: '#DC2626',
                                borderWidth: 1,
                                borderRadius: 2,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => ctx.parsed.y.toFixed(2) + ' hours'
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 24,
                                    title: { display: true, text: 'Hours per day' },
                                    grid: { color: 'rgba(0,0,0,0.05)' }
                                },
                                x: {
                                    title: { display: true, text: 'Last 30 days' },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                },
            
                destroyCharts() {
                    if (this.responseChart) {
                        this.responseChart.destroy();
                        this.responseChart = null;
                    }
                    if (this.downtimeChart) {
                        this.downtimeChart.destroy();
                        this.downtimeChart = null;
                    }
                }
            }" x-init="initCharts()">
            {{-- Detail Header --}}
            <div
                class="px-6 py-4 border-b border-base-200 bg-base-200/50 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <button wire:click="closeSiteDetail" class="btn btn-ghost btn-sm btn-square"
                        title="Close detail view">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-lg font-semibold text-base-content">{{ $siteData['site']->name }}</h2>
                        <a href="{{ $siteData['site']->base_url }}" target="_blank" rel="noopener noreferrer"
                            class="text-sm text-base-content/50 hover:text-primary hover:underline">
                            {{ $siteData['site']->base_url }}
                        </a>
                    </div>
                    {{-- Status Badge --}}
                    @php
                        $status = $siteData['site']->status;
                        $badgeClass = match ($status) {
                            \App\Enums\SiteStatus::Up => 'badge-success',
                            \App\Enums\SiteStatus::PartiallyDown => 'badge-warning',
                            \App\Enums\SiteStatus::TotallyDown => 'badge-error',
                        };
                        $statusLabel = match ($status) {
                            \App\Enums\SiteStatus::Up => 'Up',
                            \App\Enums\SiteStatus::PartiallyDown => 'Partially Down',
                            \App\Enums\SiteStatus::TotallyDown => 'Totally Down',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                {{-- Per-Site Refresh Button --}}
                <button wire:click="refreshSite({{ $siteData['site']->id }})" wire:loading.attr="disabled"
                    wire:target="refreshSite" class="btn btn-primary btn-sm gap-2">
                    <svg wire:loading wire:target="refreshSite" class="animate-spin h-4 w-4 text-white"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <svg wire:loading.remove wire:target="refreshSite" class="h-4 w-4" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span wire:loading.remove wire:target="refreshSite">Refresh</span>
                    <span wire:loading wire:target="refreshSite">Refreshing...</span>
                </button>
            </div>

            {{-- Down Info (shown when site is down) --}}
            @if ($siteData['downInfo'])
                <div class="px-6 py-3 bg-error/10 border-b border-error/20">
                    <div class="flex flex-wrap items-center gap-4 text-sm">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-error" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-base-content/60">First down:</span>
                            <span class="font-semibold text-error">{{ $siteData['downInfo']['first_down_at'] }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-error" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span class="text-base-content/60">Duration:</span>
                            <span class="font-semibold text-error">{{ $siteData['downInfo']['duration'] }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Pages Table --}}
            <div class="px-6 py-4">
                <h3 class="text-sm font-semibold text-base-content/70 mb-3">Pages</h3>
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr>
                                <th>URL</th>
                                <th class="text-center">HTTP Code</th>
                                <th class="text-right">Response Time</th>
                                <th class="text-right">Last Checked</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($siteData['pageResults'] as $pageResult)
                                <tr>
                                    <td>
                                        <span class="text-base-content">{{ $pageResult['path'] }}</span>
                                        {{-- <span
                                            class="text-xs text-base-content/40 block">{{ $pageResult['full_url'] }}</span> --}}
                                        <a href="{{ $pageResult['full_url'] }}" target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-xs text-base-content/40 block hover:text-primary hover:underline">
                                            {{ $pageResult['full_url'] }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        @if ($pageResult['http_code'] === null)
                                            <span class="text-base-content/40 italic">No data</span>
                                        @elseif($pageResult['http_code'] === 0)
                                            <span class="badge badge-error badge-sm">
                                                Unreachable
                                            </span>
                                        @elseif($pageResult['http_code'] >= 200 && $pageResult['http_code'] < 400)
                                            <span class="badge badge-success badge-sm">
                                                {{ $pageResult['http_code'] }}
                                            </span>
                                        @else
                                            <span class="badge badge-error badge-sm">
                                                {{ $pageResult['http_code'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if ($pageResult['response_time_ms'] !== null && $pageResult['http_code'] > 0)
                                            <span
                                                class="font-mono text-base-content/70">{{ number_format($pageResult['response_time_ms'], 0) }}
                                                ms</span>
                                        @elseif($pageResult['http_code'] === 0)
                                            <span class="text-base-content/40">&mdash;</span>
                                        @else
                                            <span class="text-base-content/40 italic">No data</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-base-content/50">
                                        {{ $pageResult['checked_at'] ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-base-content/40 italic">No pages
                                        defined for this site</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Charts Section --}}
            <div class="px-6 py-4 border-t border-base-200" wire:ignore>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Response Time Chart --}}
                    <div>
                        <h3 class="text-sm font-semibold text-base-content/70 mb-3">Response Time (Last 24 Hours)</h3>
                        <div class="bg-base-200/50 rounded-lg p-4" style="height: 280px;">
                            <canvas x-ref="responseTimeCanvas" style="width: 100%; height: 100%;"></canvas>
                        </div>
                    </div>

                    {{-- Downtime Chart --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-base-content/70">Downtime (Last 30 Days)</h3>
                            <span class="badge badge-error badge-sm">
                                Total: {{ $siteData['downtimeData']['totalHours'] }}h
                            </span>
                        </div>
                        <div class="bg-base-200/50 rounded-lg p-4" style="height: 280px;">
                            <canvas x-ref="downtimeCanvas" style="width: 100%; height: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Downtime History Section --}}
            <div class="px-6 py-4 border-t border-base-200">
                <h3 class="text-sm font-semibold text-base-content/70 mb-3">Downtime History (Last 30 Days)</h3>
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Pages</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($siteData['downtimeHistory'] as $index => $outage)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="font-mono text-xs">{{ $outage['from'] }}</td>
                                    <td class="font-mono text-xs">
                                        @if ($outage['to'] === 'Ongoing')
                                            <span class="badge badge-error badge-xs">Ongoing</span>
                                        @else
                                            {{ $outage['to'] }}
                                        @endif
                                    </td>
                                    <td class="text-xs">{{ $outage['pages'] }}</td>
                                    <td class="font-semibold text-xs">{{ $outage['duration'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-base-content/40 italic">No downtime
                                        recorded in the last 30 days</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Website List Section --}}
    <div class="card bg-base-100 shadow-sm">
        {{-- List Header with Controls --}}
        <div class="card-body pb-0">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <h2 class="card-title">Monitored Websites</h2>

                <div class="flex items-center gap-3 flex-wrap lg:flex-nowrap w-full lg:w-auto">
                    {{-- Search --}}
                    <label class="input input-sm w-full lg:w-64">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" wire:model.live.debounce.300ms="siteSearch" class="grow"
                            placeholder="Search" />
                    </label>

                    {{-- Status Filter --}}
                    <select class="select select-sm w-full lg:w-auto" wire:model.live="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="up">Up</option>
                        <option value="partially_down">Partially Down</option>
                        <option value="totally_down">Totally Down</option>
                    </select>

                    {{-- Category Filter --}}
                    <select class="select select-sm w-full lg:w-auto" id="categoryFilter"
                        wire:model.live="categoryFilter">
                        <option value="">All Categories</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>

                    {{-- View Toggle --}}
                    <div class="join">
                        <button wire:click="$set('viewMode', 'card')"
                            class="join-item btn btn-sm {{ $viewMode === 'card' ? 'btn-primary' : 'btn-ghost' }}">
                            Card
                        </button>
                        <button wire:click="$set('viewMode', 'table')"
                            class="join-item btn btn-sm {{ $viewMode === 'table' ? 'btn-primary' : 'btn-ghost' }}">
                            Table
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Site List Content --}}
        <div class="card-body pt-0">
            @if ($sites->isEmpty())
                <div class="text-center py-8 text-base-content/50">
                    <svg class="mx-auto w-12 h-12 text-base-content/30 mb-3" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                    </svg>
                    <p class="text-sm">No websites available</p>
                </div>
            @elseif($viewMode === 'card')
                {{-- Card View --}}
                <div
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 2xl:grid-cols-8 gap-3">
                    @foreach ($sites as $site)
                        @php
                            $cardBorder = match ($site->status) {
                                \App\Enums\SiteStatus::Up => 'border-l-success',
                                \App\Enums\SiteStatus::PartiallyDown => 'border-l-warning',
                                \App\Enums\SiteStatus::TotallyDown => 'border-l-error',
                            };
                            $cardBadge = match ($site->status) {
                                \App\Enums\SiteStatus::Up => 'badge-success',
                                \App\Enums\SiteStatus::PartiallyDown => 'badge-warning',
                                \App\Enums\SiteStatus::TotallyDown => 'badge-error',
                            };
                            $cardStatusText = match ($site->status) {
                                \App\Enums\SiteStatus::Up => 'Up',
                                \App\Enums\SiteStatus::PartiallyDown => 'Partially Down',
                                \App\Enums\SiteStatus::TotallyDown => 'Totally Down',
                            };
                        @endphp
                        <div wire:click="selectSite({{ $site->id }})"
                            class="card bg-base-100 shadow-sm border-l-4 {{ $cardBorder }} p-3 cursor-pointer hover:shadow-md transition-shadow">
                            <p class="text-xs font-semibold text-base-content truncate" title="{{ $site->name }}">
                                {{ $site->name }}</p>
                            <span class="badge {{ $cardBadge }} badge-xs mt-1.5">
                                {{ $cardStatusText }}
                            </span>
                            @php $cardAvgTime = $siteMetrics[$site->id]['avg_response_time_24h'] ?? 0; @endphp
                            <p class="text-[10px] text-base-content/50 mt-1">
                                {{ $cardAvgTime > 0 ? number_format($cardAvgTime, 0) . ' ms' : '—' }}
                            </p>
                            @if ($site->responsiblePerson)
                                <p class="text-[10px] text-base-content/40 mt-0.5 truncate"
                                    title="{{ $site->responsiblePerson->name }}">{{ $site->responsiblePerson->name }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Table View --}}
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr>
                                <th>Site Name</th>
                                <th class="text-center">HTTP Code</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Avg Response Time (24h)</th>
                                <th>Responsible Person</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sites as $site)
                                @php
                                    $tableBadge = match ($site->status) {
                                        \App\Enums\SiteStatus::Up => 'badge-success',
                                        \App\Enums\SiteStatus::PartiallyDown => 'badge-warning',
                                        \App\Enums\SiteStatus::TotallyDown => 'badge-error',
                                    };
                                    $tableStatusText = match ($site->status) {
                                        \App\Enums\SiteStatus::Up => 'Up',
                                        \App\Enums\SiteStatus::PartiallyDown => 'Partially Down',
                                        \App\Enums\SiteStatus::TotallyDown => 'Totally Down',
                                    };
                                    $httpCode = $siteMetrics[$site->id]['latest_http_code'] ?? null;
                                    $avgTime24h = $siteMetrics[$site->id]['avg_response_time_24h'] ?? 0;
                                @endphp
                                <tr wire:click="selectSite({{ $site->id }})" class="cursor-pointer hover">
                                    <td class="font-medium text-base-content">{{ $site->name }}</td>
                                    <td class="text-center">
                                        @if ($httpCode !== null)
                                            <span
                                                class="font-mono text-sm {{ $httpCode >= 200 && $httpCode < 400 ? 'text-success' : 'text-error' }}">
                                                {{ $httpCode }}
                                            </span>
                                        @else
                                            <span class="text-base-content/40">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $tableBadge }} badge-sm">
                                            {{ $tableStatusText }}
                                        </span>
                                    </td>
                                    <td class="text-right font-mono text-base-content/70">
                                        {{ $avgTime24h > 0 ? number_format($avgTime24h, 0) . ' ms' : '—' }}
                                    </td>
                                    <td class="text-base-content/60">
                                        {{ $site->responsiblePerson?->name ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Pagination --}}
            @if ($sites->hasPages())
                <div class="mt-4">
                    {{ $sites->links('vendor.livewire.daisy-pagination') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Live Log Drawer --}}
    @if ($showLogDrawer)
        <div class="fixed inset-0 z-50 flex justify-end" x-data x-init="$nextTick(() => $refs.logPanel.focus())">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/30" wire:click="toggleLogDrawer"></div>

            {{-- Drawer Panel --}}
            <div x-ref="logPanel"
                class="relative w-full max-w-lg bg-base-100 shadow-xl flex flex-col h-full overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-base-200 bg-base-200/50">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-base-content">Live Checking Log</h3>
                        @if ($this->cycleTotalPages > 0)
                            <span class="badge badge-sm badge-ghost">
                                {{ count($this->cycleLogs) }} / {{ $this->cycleTotalPages }}
                            </span>
                        @endif
                        @if ($cycleInProgress || $isRefreshing)
                            <span class="loading loading-dots loading-xs text-primary"></span>
                        @endif
                    </div>
                    <button wire:click="toggleLogDrawer" class="btn btn-ghost btn-sm btn-square">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Progress bar --}}
                @if ($this->cycleTotalPages > 0)
                    @php
                        $progress = min(100, round((count($this->cycleLogs) / $this->cycleTotalPages) * 100));
                    @endphp
                    <div class="px-4 pt-2">
                        <progress class="progress progress-primary w-full" value="{{ $progress }}"
                            max="100"></progress>
                    </div>
                @endif

                {{-- Log Entries --}}
                <div class="flex-1 overflow-y-auto px-4 py-2 space-y-1" x-data x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                    x-effect="$el.scrollTop = $el.scrollHeight">
                    @forelse($this->cycleLogs as $log)
                        <div class="flex items-center gap-2 py-1 border-b border-base-200/50 text-xs">
                            <span class="text-base-content/40 font-mono shrink-0">{{ $log['time'] }}</span>
                            @if ($log['http_code'] === 0)
                                <span class="badge badge-error badge-xs shrink-0">ERR</span>
                            @elseif($log['http_code'] >= 200 && $log['http_code'] < 400)
                                <span class="badge badge-success badge-xs shrink-0">{{ $log['http_code'] }}</span>
                            @else
                                <span class="badge badge-error badge-xs shrink-0">{{ $log['http_code'] }}</span>
                            @endif
                            <span class="font-medium text-base-content truncate shrink-0 max-w-[120px]"
                                title="{{ $log['site'] }}">{{ $log['site'] }}</span>
                            <span class="text-base-content/50 truncate"
                                title="{{ $log['url'] }}">{{ parse_url($log['url'], PHP_URL_PATH) ?: '/' }}</span>
                            @if ($log['http_code'] > 0 && $log['response_time_ms'] > 0)
                                <span
                                    class="text-base-content/40 font-mono ml-auto shrink-0">{{ number_format($log['response_time_ms'], 0) }}ms</span>
                            @elseif($log['http_code'] === 0)
                                <span class="text-error/70 ml-auto shrink-0">{{ $log['error_type'] }}</span>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8 text-base-content/40">
                            <svg class="mx-auto w-8 h-8 mb-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p class="text-sm">No logs yet. Trigger a refresh to see live results.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
