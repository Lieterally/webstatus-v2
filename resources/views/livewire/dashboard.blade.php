<div wire:poll.2s="poll" x-data="{
    countdown: @entangle('countdownSeconds'),
    cycleInProgress: @entangle('cycleInProgress'),
    cycleIntervalSeconds: @entangle('cycleIntervalSeconds'),
    isRefreshing: @entangle('isRefreshing'),
    monitoringEnabled: @entangle('monitoringEnabled'),
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
    @php
        $rangeOptions = array_combine($r = ['1D', '3D', '7D', '1M', '3M', '6M', '1Y'], $r);
    @endphp

    <x-itk.page-header title="Dashboard" description="Website monitoring overview">
        <x-slot:actions>
            <x-itk.button wire:click="refreshAll" loading="refreshAll" icon="refresh-cw"
                x-bind:disabled="cycleInProgress || isRefreshing || !monitoringEnabled">
                <span wire:loading.remove wire:target="refreshAll">Refresh all now</span>
                <span wire:loading wire:target="refreshAll">Refreshing…</span>
            </x-itk.button>
            <x-itk.button id="liveLogButton" variant="secondary" icon="scroll-text" wire:click="toggleLogDrawer"
                aria-haspopup="dialog">
                Live log
            </x-itk.button>
        </x-slot:actions>
    </x-itk.page-header>

    {{-- Summary tiles --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-itk.stat title="Total sites" :value="$totalSites" icon="globe">
            @if ($sitesUnmonitored > 0)
                {{ $sitesUnmonitored }} not monitored
            @endif
        </x-itk.stat>

        <x-itk.stat title="Sites down" :value="$sitesDown" icon="circle-x" tone="danger" />

        <x-itk.stat title="Sites up" :value="$sitesUp" icon="circle-check" tone="success" />

        <x-itk.stat title="Last cycle" icon="clock">
            <p class="text-headline text-ink tabular-nums mb-1">
                @if ($lastCycleDatetime)
                    {{ $lastCycleDatetime }}
                @else
                    <span class="text-ink-muted font-normal">No data yet</span>
                @endif
            </p>
            <template x-if="cycleInProgress || isRefreshing">
                <span class="flex items-center gap-1.5 font-medium text-warning">
                    <x-itk.spinner size="14" label="Checking" />
                    Checking now, countdown paused
                </span>
            </template>
            <template x-if="!cycleInProgress && !isRefreshing && !monitoringEnabled">
                <span class="flex items-center gap-1.5 font-medium text-warning">
                    <x-itk.icon name="circle-pause" size="16" />
                    Monitoring is turned off
                </span>
            </template>
            <template x-if="!cycleInProgress && !isRefreshing && monitoringEnabled">
                <span class="flex items-center gap-1.5">
                    <x-itk.icon name="timer" size="16" />
                    Next cycle in <span class="tabular-nums font-medium text-ink" x-text="formattedCountdown"></span>
                </span>
            </template>
        </x-itk.stat>
    </div>

    {{-- Overview charts --}}
    <x-itk.card title="Overview" class="mb-6">
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                {{-- Response Time Chart --}}
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <h3 class="text-headline text-ink">Response time</h3>
                        <x-itk.toggle-group label="Response time range" model="responseTimeFilter"
                            :value="$responseTimeFilter" :options="$rangeOptions" />
                    </div>
                    <div class="bg-surface-sunken rounded-md p-4" style="height: 280px;" wire:ignore
                        x-data="{
                            chart: null,
                            chartData: null,
                            init() {
                                this.createChart();
                                Livewire.on('overviewChartsUpdated', (data) => {
                                    if (this.chart) { this.chart.destroy(); }
                                    this.createChartWithData(data[0].response);
                                });
                                window.addEventListener('itk-theme-change', () => {
                                    if (this.chart) { this.chart.destroy(); }
                                    this.createChartWithData(this.chartData);
                                });
                            },
                            createChart() {
                                const chartData = @js($overviewChartData['response']);
                                this.createChartWithData(chartData);
                            },
                            createChartWithData(chartData) {
                                const ctx = this.$refs.overviewResponseCanvas;
                                if (!ctx || !chartData) return;
                                this.chartData = chartData;
                                const colors = window.itkChartColors();
                                this.chart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: chartData.labels,
                                        datasets: [{
                                            label: 'Avg Response Time (s)',
                                            data: chartData.data,
                                            borderColor: colors.primary,
                                            backgroundColor: window.itkAlpha(colors.primary, 0.1),
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
                                            y: { beginAtZero: true, title: { display: true, text: 'Seconds' }, grid: { color: colors.grid } },
                                            x: { title: { display: true, text: chartData.xLabel || 'Time' }, grid: { display: false } }
                                        }
                                    }
                                });
                            }
                        }">
                        <canvas x-ref="overviewResponseCanvas" role="img" aria-label="Average response time chart, all websites" style="width: 100%; height: 100%;"></canvas>
                    </div>
                </div>

                {{-- Downtime Chart --}}
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-headline text-ink">
                                <a href="{{ route('downtime.index') }}"
                                    class="inline-flex items-center gap-1 min-h-11 text-link hover:underline underline-offset-3 rounded-xs">
                                    Downtime<span class="sr-only"> details</span>
                                    <x-itk.icon name="arrow-right" size="16" />
                                </a>
                            </h3>
                            <x-itk.badge tone="danger" icon="clock-alert">
                                <span id="downtimeTotalBadge">Total: {{ $overviewChartData['downtime']['totalHours'] }}</span>
                            </x-itk.badge>
                        </div>
                        <x-itk.toggle-group label="Downtime range" model="downtimeFilter" :value="$downtimeFilter"
                            :options="$rangeOptions" />
                    </div>
                    <div class="bg-surface-sunken rounded-md p-4" style="height: 280px;" wire:ignore
                        x-data="{
                            chart: null,
                            chartData: null,
                            init() {
                                this.createChart();
                                window.addEventListener('itk-theme-change', () => {
                                    if (this.chart) { this.chart.destroy(); }
                                    this.createChartWithData(this.chartData);
                                });
                                Livewire.on('overviewChartsUpdated', (data) => {
                                    if (this.chart) { this.chart.destroy(); }
                                    this.createChartWithData(data[0].downtime);
                                    document.getElementById('downtimeTotalBadge').textContent = 'Total: ' + data[0].downtime.totalHours;
                                });
                            },
                            createChart() {
                                const chartData = @js($overviewChartData['downtime']);
                                this.createChartWithData(chartData);
                            },
                            createChartWithData(chartData) {
                                const ctx = this.$refs.overviewDowntimeCanvas;
                                if (!ctx || !chartData) return;
                                this.chartData = chartData;
                                const colors = window.itkChartColors();
                                this.chart = new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: chartData.labels,
                                        datasets: [{
                                            label: 'Downtime (' + chartData.unit + ')',
                                            data: chartData.data,
                                            backgroundColor: colors.danger,
                                            borderColor: colors.danger,
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
                                                    label: (c) => c.parsed.y.toFixed(2) + ' ' + chartData.unit,
                                                    afterBody: (tooltipItems) => {
                                                        const idx = tooltipItems[0].dataIndex;
                                                        const sites = chartData.downSites && chartData.downSites[idx];
                                                        if (!sites || sites.length === 0) return '';
                                                        return ['', 'Down sites:'].concat(sites.map(s => '  • ' + s));
                                                    }
                                                }
                                            }
                                        },
                                        scales: {
                                            y: { beginAtZero: true, ...(chartData.yMax ? { max: chartData.yMax } : {}), title: { display: true, text: chartData.yLabel }, grid: { color: colors.grid } },
                                            x: { title: { display: true, text: chartData.xLabel || 'Time' }, grid: { display: false } }
                                        }
                                    }
                                });
                            }
                        }">
                        <canvas x-ref="overviewDowntimeCanvas" role="img" aria-label="Downtime chart, all websites" style="width: 100%; height: 100%;"></canvas>
                    </div>
                </div>
            </div>
    </x-itk.card>

    {{-- Detailed Site View (shown when a site is selected) --}}
    @if ($selectedSiteId && $this->selectedSiteData)
        @php $siteData = $this->selectedSiteData; @endphp
        <section aria-labelledby="site-detail-title" class="bg-surface text-ink border border-line rounded-md shadow-1 mb-6 overflow-hidden" wire:ignore.self
            wire:key="site-detail-{{ $selectedSiteId }}" x-data="{
                responseChart: null,
                downtimeChart: null,
                responseData: null,
                downtimeData: null,
                chartsInitialized: false,
            
                initCharts() {
                    if (this.chartsInitialized) return;
                    this.chartsInitialized = true;
                    this.$nextTick(() => {
                        this.createResponseTimeChart();
                        this.createDowntimeChart();
                    });
            
                    window.addEventListener('itk-theme-change', () => {
                        if (this.responseData) { this.createResponseChartWithData(this.responseData); }
                        if (this.downtimeData) { this.createDowntimeChartWithData(this.downtimeData); }
                    });

                    Livewire.on('siteChartsUpdated', (data) => {
                        const payload = data[0];
                        if (payload.responseTimeData) {
                            this.updateResponseChart(payload.responseTimeData);
                        }
                        if (payload.downtimeData) {
                            this.updateDowntimeChart(payload.downtimeData);
                        }
                    });
                },
            
                updateResponseChart(chartData) {
                    if (this.responseChart) { this.responseChart.destroy(); }
                    this.createResponseChartWithData(chartData);
                },
            
                updateDowntimeChart(chartData) {
                    if (this.downtimeChart) { this.downtimeChart.destroy(); }
                    this.createDowntimeChartWithData(chartData);
                    const badge = document.getElementById('siteDowntimeTotalBadge');
                    if (badge) badge.textContent = 'Total: ' + chartData.totalHours;
                },
            
                createResponseTimeChart() {
                    const chartData = @js($siteData['responseTimeData']);
                    this.createResponseChartWithData(chartData);
                },
            
                createResponseChartWithData(chartData) {
                    const ctx = this.$refs.responseTimeCanvas;
                    if (!ctx) return;
                    this.responseData = chartData;
                    const colors = window.itkChartColors();
                    if (this.responseChart) { this.responseChart.destroy(); }
            
                    this.responseChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                label: 'Response Time (seconds)',
                                data: chartData.data,
                                borderColor: colors.primary,
                                backgroundColor: window.itkAlpha(colors.primary, 0.1),
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
                                    grid: { color: colors.grid }
                                },
                                x: {
                                    title: { display: true, text: chartData.xLabel || 'Time' },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                },
            
                createDowntimeChart() {
                    const chartData = @js($siteData['downtimeData']);
                    this.createDowntimeChartWithData(chartData);
                },
            
                createDowntimeChartWithData(chartData) {
                    const ctx = this.$refs.downtimeCanvas;
                    if (!ctx) return;
                    this.downtimeData = chartData;
                    const colors = window.itkChartColors();
                    if (this.downtimeChart) { this.downtimeChart.destroy(); }
            
                    this.downtimeChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                label: 'Downtime (' + chartData.unit + ')',
                                data: chartData.data,
                                backgroundColor: colors.danger,
                                borderColor: colors.danger,
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
                                        label: (ctx) => ctx.parsed.y.toFixed(2) + ' ' + chartData.unit
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ...(chartData.yMax ? { max: chartData.yMax } : {}),
                                    title: { display: true, text: chartData.yLabel },
                                    grid: { color: colors.grid }
                                },
                                x: {
                                    title: { display: true, text: chartData.xLabel || 'Time' },
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
            {{-- Detail header --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 md:px-6 py-4 border-b border-line">
                <div class="flex flex-wrap items-center gap-3 min-w-0">
                    <x-itk.button variant="ghost" icon="arrow-left" icon-only label="Close site details"
                        wire:click="closeSiteDetail" />
                    <div class="min-w-0">
                        <h2 id="site-detail-title" class="text-title-3 text-ink">{{ $siteData['site']->name }}</h2>
                        <a href="{{ $siteData['site']->base_url }}" target="_blank" rel="noopener noreferrer"
                            class="text-callout font-normal text-link hover:underline underline-offset-3 break-all">
                            {{ $siteData['site']->base_url }}<span class="sr-only"> (opens in a new tab)</span>
                        </a>
                    </div>
                    <x-itk.status-badge :status="$siteData['site']->status" :monitored="$siteData['site']->is_monitored" />
                </div>

                <x-itk.button wire:click="refreshSite({{ $siteData['site']->id }})" loading="refreshSite"
                    icon="refresh-cw" :disabled="!$siteData['site']->is_monitored || !$monitoringEnabled">
                    <span wire:loading.remove wire:target="refreshSite">Refresh</span>
                    <span wire:loading wire:target="refreshSite">Refreshing…</span>
                </x-itk.button>
            </div>

            {{-- Down info (shown when the site is down) --}}
            @if ($siteData['downInfo'] && $siteData['site']->is_monitored)
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 px-4 md:px-6 py-3 bg-error-soft border-b border-line text-callout font-normal">
                    <span class="flex items-center gap-1.5">
                        <x-itk.icon name="clock-alert" size="16" class="text-error" />
                        <span class="text-ink-muted">First down:</span>
                        <span class="font-semibold text-ink">{{ $siteData['downInfo']['first_down_at'] }}</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <x-itk.icon name="timer" size="16" class="text-error" />
                        <span class="text-ink-muted">Duration:</span>
                        <span class="font-semibold text-ink">{{ $siteData['downInfo']['duration'] }}</span>
                    </span>
                </div>
            @endif

            {{-- Pages --}}
            <div class="px-4 md:px-6 py-4">
                <x-itk.table caption="Pages" caption-id="site-pages-caption">
                    <thead>
                        <tr>
                            <th scope="col">URL</th>
                            <th scope="col">HTTP code</th>
                            <th scope="col" class="itk-num">Response time</th>
                            <th scope="col" class="itk-num">Last checked</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siteData['pageResults'] as $pageResult)
                            <tr>
                                <td class="min-w-60">
                                    <span class="block text-ink">{{ $pageResult['path'] }}</span>
                                    <a href="{{ $pageResult['full_url'] }}" target="_blank" rel="noopener noreferrer"
                                        class="text-footnote text-link hover:underline underline-offset-3 break-all">
                                        {{ $pageResult['full_url'] }}<span class="sr-only"> (opens in a new tab)</span>
                                    </a>
                                </td>
                                <td>
                                    @if ($pageResult['http_code'] === null)
                                        <span class="text-ink-muted">No data</span>
                                    @elseif($pageResult['http_code'] === 0)
                                        <x-itk.badge tone="danger" icon="circle-x">Unreachable</x-itk.badge>
                                    @elseif($pageResult['http_code'] >= 200 && $pageResult['http_code'] < 400)
                                        <x-itk.badge tone="success" icon="circle-check">{{ $pageResult['http_code'] }}</x-itk.badge>
                                    @else
                                        <x-itk.badge tone="danger" icon="circle-x">{{ $pageResult['http_code'] }}</x-itk.badge>
                                    @endif
                                </td>
                                <td class="itk-num">
                                    @if ($pageResult['response_time_ms'] !== null && $pageResult['http_code'] > 0)
                                        {{ number_format($pageResult['response_time_ms'], 0) }} ms
                                    @elseif($pageResult['http_code'] === 0)
                                        <span class="text-ink-muted">—</span>
                                    @else
                                        <span class="text-ink-muted">No data</span>
                                    @endif
                                </td>
                                <td class="itk-num text-ink-muted">{{ $pageResult['checked_at'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-itk.empty title="No pages yet" icon="file-text" class="py-6">
                                        Add pages to this website in the website manager.
                                    </x-itk.empty>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-itk.table>
            </div>

            {{-- Charts --}}
            <div class="px-4 md:px-6 py-4 border-t border-line">
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                            <h3 class="text-headline text-ink">Response time</h3>
                            <x-itk.toggle-group label="Site response time range" model="siteResponseTimeFilter"
                                :value="$siteResponseTimeFilter" :options="$rangeOptions" />
                        </div>
                        <div class="bg-surface-sunken rounded-md p-4" style="height: 280px;" wire:ignore>
                            <canvas x-ref="responseTimeCanvas" role="img" aria-label="Response time chart for {{ $siteData['site']->name }}"
                                style="width: 100%; height: 100%;"></canvas>
                        </div>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-headline text-ink">Downtime</h3>
                                <x-itk.badge tone="danger" icon="clock-alert">
                                    <span id="siteDowntimeTotalBadge">Total: {{ $siteData['downtimeData']['totalHours'] }}</span>
                                </x-itk.badge>
                            </div>
                            <x-itk.toggle-group label="Site downtime range" model="siteDowntimeFilter"
                                :value="$siteDowntimeFilter" :options="$rangeOptions" />
                        </div>
                        <div class="bg-surface-sunken rounded-md p-4" style="height: 280px;" wire:ignore>
                            <canvas x-ref="downtimeCanvas" role="img" aria-label="Downtime chart for {{ $siteData['site']->name }}"
                                style="width: 100%; height: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Downtime history --}}
            <div class="px-4 md:px-6 py-4 border-t border-line">
                <x-itk.table caption="Downtime history (last 30 days)" caption-id="site-downtime-caption">
                    <thead>
                        <tr>
                            <th scope="col" class="itk-num">#</th>
                            <th scope="col">From</th>
                            <th scope="col">To</th>
                            <th scope="col">Pages</th>
                            <th scope="col" class="itk-num">Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siteData['downtimeHistory'] as $index => $outage)
                            <tr>
                                <td class="itk-num text-ink-muted">{{ $index + 1 }}</td>
                                <td class="whitespace-nowrap">{{ $outage['from'] }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($outage['to'] === 'Ongoing')
                                        <x-itk.badge tone="danger" icon="circle-x">Ongoing</x-itk.badge>
                                    @else
                                        {{ $outage['to'] }}
                                    @endif
                                </td>
                                <td>{{ $outage['pages'] }}</td>
                                <td class="itk-num font-semibold">{{ $outage['duration'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-itk.empty title="No downtime in the last 30 days" icon="circle-check" class="py-6" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-itk.table>
            </div>
        </section>
    @endif

    {{-- Website list --}}
    <x-itk.card title="Monitored websites">
        <x-slot:actions>
            <div class="relative w-full sm:w-64">
                <x-itk.icon name="search" class="pointer-events-none absolute left-3 top-3 text-ink-muted" />
                <x-itk.input type="search" wire:model.live.debounce.300ms="siteSearch" aria-label="Search websites"
                    placeholder="Search by name" class="pl-10" />
            </div>

            <x-itk.select wire:model.live="statusFilter" aria-label="Filter by status" class="w-full sm:w-auto">
                <option value="">All statuses</option>
                <option value="up">Up</option>
                <option value="partially_down">Partially down</option>
                <option value="totally_down">Down</option>
            </x-itk.select>

            <x-itk.select id="categoryFilter" wire:model.live="categoryFilter" aria-label="Filter by category"
                class="w-full sm:w-auto">
                <option value="">All categories</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </x-itk.select>

            <x-itk.toggle-group label="View" model="viewMode" :value="$viewMode" :options="[
                'card' => ['icon' => 'layout-grid', 'label' => 'Card view'],
                'table' => ['icon' => 'table', 'label' => 'Table view'],
            ]" />
        </x-slot:actions>

        @if ($sites->isEmpty())
            <x-itk.empty title="No websites found" icon="globe">
                Try another search or filter, or add a website in the website manager.
            </x-itk.empty>
        @elseif($viewMode === 'card')
            <ul class="itk-grid-tiles">
                @foreach ($sites as $site)
                    @php $cardAvgTime = $siteMetrics[$site->id]['avg_response_time_24h'] ?? 0; @endphp
                    <li>
                        <button type="button" wire:click="selectSite({{ $site->id }})"
                            @if ($selectedSiteId === $site->id) aria-current="true" @endif
                            class="grid content-start gap-1.5 w-full h-full p-3 text-left bg-surface border border-line rounded-md shadow-1 hover:shadow-2 hover:border-line-strong transition-shadow duration-120 cursor-pointer aria-[current=true]:bg-primary-soft aria-[current=true]:border-link">
                            <span class="text-callout font-semibold text-ink truncate" title="{{ $site->name }}">{{ $site->name }}</span>
                            <x-itk.status-badge :status="$site->status" :monitored="$site->is_monitored" class="justify-self-start" />
                            <span class="text-footnote text-ink-muted tabular-nums">
                                {{ $cardAvgTime > 0 ? 'Avg ' . number_format($cardAvgTime, 0) . ' ms' : 'No response data' }}
                            </span>
                            @if ($site->responsiblePerson)
                                <span class="text-footnote text-ink-muted truncate" title="{{ $site->responsiblePerson->name }}">
                                    {{ $site->responsiblePerson->name }}
                                </span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        @else
            <x-itk.table caption="Monitored websites" hide-caption caption-id="sites-table-caption">
                <thead>
                    <tr>
                        <th scope="col">Website</th>
                        <th scope="col">HTTP code</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="itk-num">Avg response (24h)</th>
                        <th scope="col">Responsible person</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sites as $site)
                        @php
                            $httpCode = $siteMetrics[$site->id]['latest_http_code'] ?? null;
                            $avgTime24h = $siteMetrics[$site->id]['avg_response_time_24h'] ?? 0;
                            $httpOk = $httpCode !== null && $httpCode >= 200 && $httpCode < 400;
                        @endphp
                        <tr wire:click="selectSite({{ $site->id }})" class="cursor-pointer">
                            <th scope="row">
                                <button type="button" wire:click.stop="selectSite({{ $site->id }})"
                                    class="min-h-11 text-left font-semibold text-link hover:underline underline-offset-3 rounded-xs cursor-pointer">
                                    {{ $site->name }}
                                </button>
                            </th>
                            <td>
                                @if ($httpCode !== null)
                                    <span @class(['inline-flex items-center gap-1 tabular-nums', 'text-success' => $httpOk, 'text-error' => ! $httpOk])>
                                        <x-itk.icon :name="$httpOk ? 'circle-check' : 'circle-x'" size="16" />
                                        {{ $httpCode }}
                                    </span>
                                @else
                                    <span class="text-ink-muted">—</span>
                                @endif
                            </td>
                            <td><x-itk.status-badge :status="$site->status" :monitored="$site->is_monitored" /></td>
                            <td class="itk-num">{{ $avgTime24h > 0 ? number_format($avgTime24h, 0) . ' ms' : '—' }}</td>
                            <td class="text-ink-muted">{{ $site->responsiblePerson?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-itk.table>
        @endif

        {{-- Pagination (restyled in 2.8) --}}
        @if ($sites->hasPages())
            <div class="mt-4">
                {{ $sites->links('vendor.livewire.daisy-pagination') }}
            </div>
        @endif
    </x-itk.card>

    {{-- Live log sheet --}}
    @if ($showLogDrawer)
        <x-itk.sheet title="Live checking log" title-id="live-log-title" close="$wire.toggleLogDrawer()"
            return-focus="liveLogButton">
            <x-slot:meta>
                @if ($cycleInProgress || $isRefreshing)
                    <x-itk.spinner size="16" label="Checking" class="text-link" />
                @endif
            </x-slot:meta>

            @if ($this->cycleTotalPages > 0)
                <div class="px-5 pt-3">
                    <x-itk.progress label="Pages checked" :value="count($this->cycleLogs)" :max="$this->cycleTotalPages"
                        :value-text="count($this->cycleLogs) . ' of ' . $this->cycleTotalPages" />
                </div>
            @endif

            <div class="flex-1 overflow-y-auto px-5 py-2" role="log" aria-label="Check results" tabindex="0"
                x-data x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)" x-effect="$el.scrollTop = $el.scrollHeight">
                @forelse($this->cycleLogs as $log)
                    @if ($log['http_code'] === -1)
                        <div class="flex items-center gap-2 py-2 my-1">
                            <div class="flex-1 border-t border-warning"></div>
                            <span class="flex items-center gap-1 text-footnote font-semibold text-warning shrink-0">
                                <x-itk.icon name="rotate-cw" size="16" />
                                Retry cycle (verifying down sites)
                            </span>
                            <div class="flex-1 border-t border-warning"></div>
                        </div>
                    @else
                        <div class="flex items-center gap-2 py-1.5 border-b border-line text-footnote">
                            <span class="text-ink-muted tabular-nums shrink-0">{{ $log['time'] }}</span>
                            @if ($log['http_code'] === 0)
                                <x-itk.badge tone="danger" icon="circle-x" class="shrink-0">Error</x-itk.badge>
                            @elseif($log['http_code'] >= 200 && $log['http_code'] < 400)
                                <x-itk.badge tone="success" icon="circle-check" class="shrink-0">{{ $log['http_code'] }}</x-itk.badge>
                            @else
                                <x-itk.badge tone="danger" icon="circle-x" class="shrink-0">{{ $log['http_code'] }}</x-itk.badge>
                            @endif
                            <span class="font-semibold text-ink truncate shrink-0 max-w-30" title="{{ $log['site'] }}">{{ $log['site'] }}</span>
                            <span class="text-ink-muted truncate" title="{{ $log['url'] }}">{{ parse_url($log['url'], PHP_URL_PATH) ?: '/' }}</span>
                            @if ($log['http_code'] > 0 && $log['response_time_ms'] > 0)
                                <span class="text-ink-muted tabular-nums ml-auto shrink-0">{{ number_format($log['response_time_ms'], 0) }} ms</span>
                            @elseif($log['http_code'] === 0)
                                <span class="text-error ml-auto shrink-0">{{ $log['error_type'] }}</span>
                            @endif
                        </div>
                    @endif
                @empty
                    <x-itk.empty title="No checks yet" icon="clipboard-list">
                        Select "Refresh all now" to see live results here.
                    </x-itk.empty>
                @endforelse
            </div>
        </x-itk.sheet>
    @endif
</div>
