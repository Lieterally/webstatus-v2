@extends('layouts.app')

@section('title', 'Downtime details')

@section('content')
    @php
        $fmtDuration = function (int $seconds): string {
            $h = intdiv($seconds, 3600);
            $m = intdiv($seconds % 3600, 60);
            return ($h > 0 ? $h . 'h ' : '') . $m . 'm';
        };
        $ranges = ['1d' => '1D', '3d' => '3D', '7d' => '7D', '1m' => '1M', '3m' => '3M', '6m' => '6M', '1y' => '1Y', 'all' => 'All'];
        $rangeHrefs = collect($ranges)->mapWithKeys(fn ($label, $value) => [
            $value => route('downtime.index', array_merge(request()->except('page'), ['range' => $value])),
        ])->all();
    @endphp

    <x-itk.page-header title="Downtime details" description="Outage history across all monitored websites." />

    {{-- Summary tiles --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-itk.stat title="Outage events" :value="$totalEvents" icon="triangle-alert">in the selected period</x-itk.stat>
        <x-itk.stat title="Ongoing now" :value="$activeNow" icon="activity" :tone="$activeNow > 0 ? 'danger' : 'success'">
            {{ $activeNow > 0 ? 'outages still open' : 'no open outages' }}
        </x-itk.stat>
        <x-itk.stat title="Total downtime" :value="$fmtDuration($totalDowntimeSeconds)" icon="clock">in the selected period</x-itk.stat>
        <x-itk.stat title="Most affected" icon="globe">
            <p class="text-headline text-ink truncate mb-0.5">{{ $mostAffectedSite ?? '—' }}</p>
            most outage events
        </x-itk.stat>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Outages in the last 24 hours: grouped incidents (default) or one row per website --}}
        <x-itk.card title="Outages in the last 24 hours" class="xl:col-span-3 min-w-0">
            @if (empty($ganttLabels))
                <x-itk.empty title="No downtime in the last 24 hours" icon="circle-check" class="py-6" />
            @else
                @php
                    $clock = fn ($t) => $t->format('H.i');
                    $incidentTime = function ($i) use ($clock) {
                        $start = $i['start'];
                        $prefix = $start->isToday() ? '' : $start->format('j M') . ', ';
                        return $prefix . $clock($start) . ' – ' . ($i['end'] ? $clock($i['end']) : 'now') . ' WITA';
                    };
                    $siteCount = count($ganttLabels);
                    $incidentCount = count($incidents);
                @endphp
                <div x-data="downtimeTimeline()" x-init="init()">
                    <div role="tablist" aria-label="Outage view" class="flex gap-1 border-b border-line mb-4"
                        x-on:keydown.right.prevent="select(tab === 'incidents' ? 'sites' : 'incidents', true)"
                        x-on:keydown.left.prevent="select(tab === 'incidents' ? 'sites' : 'incidents', true)">
                        @foreach (['incidents' => "Incidents ({$incidentCount})", 'sites' => "By website ({$siteCount})"] as $key => $tabLabel)
                            <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                                x-bind:aria-selected="(tab === '{{ $key }}').toString()" x-bind:tabindex="tab === '{{ $key }}' ? 0 : -1"
                                x-on:click="select('{{ $key }}')"
                                x-bind:class="tab === '{{ $key }}'
                                    ? 'text-ink font-semibold after:absolute after:inset-x-2 after:-bottom-px after:h-0.75 after:rounded-t-xs after:bg-primary'
                                    : 'text-ink-muted hover:text-ink hover:bg-surface-sunken'"
                                class="relative min-h-11 px-4 rounded-t-sm text-callout cursor-pointer">
                                {{ $tabLabel }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Incidents: how many websites were down at once, then the grouped outages --}}
                    <div id="panel-incidents" role="tabpanel" aria-labelledby="tab-incidents" tabindex="0"
                        x-bind:style="tab === 'incidents' ? '' : 'display: none'">
                        <p class="text-footnote text-ink-muted">
                            Websites down at the same time. Peak: {{ $peakDown }} {{ \Illuminate\Support\Str::plural('website', $peakDown) }}.
                        </p>
                        <div id="concurrencyChart" role="img"
                            aria-label="Number of websites down over the last 24 hours. Peak: {{ $peakDown }}."></div>

                        <ul class="grid mt-2" aria-label="Incidents">
                            @foreach ($incidents as $n => $incident)
                                <li class="py-3 border-t border-line" x-data="{ open: false }"
                                    @if ($n >= 8) style="display: none" x-bind:style="showAllIncidents ? '' : 'display: none'" @endif>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                        @if ($incident['ongoing'])
                                            <x-itk.badge tone="danger" icon="circle-x">Ongoing</x-itk.badge>
                                        @else
                                            <x-itk.badge tone="success" icon="circle-check">Resolved</x-itk.badge>
                                        @endif
                                        <span class="text-callout font-semibold text-ink tabular-nums">{{ $incidentTime($incident) }}</span>
                                        <span class="text-callout font-normal text-ink-muted">
                                            {{ \App\Services\PublicStatusService::formatDuration($incident['duration_seconds']) }}{{ $incident['ongoing'] ? ' so far' : '' }}
                                        </span>
                                        @if ($incident['count'] === 1)
                                            <span class="text-callout font-normal text-ink">{{ $incident['sites'][0] }}</span>
                                        @else
                                            <span class="text-callout font-normal text-ink">{{ $incident['count'] }} websites down</span>
                                            <x-itk.button variant="ghost" size="sm" class="max-lg:min-h-11"
                                                x-on:click="open = !open" aria-controls="incident-{{ $n }}-sites"
                                                x-bind:aria-expanded="open.toString()" aria-expanded="false">
                                                <span x-text="open ? 'Hide websites' : 'Show websites'">Show websites</span>
                                                <x-itk.icon name="chevron-down" size="16" class="transition-transform duration-200" x-bind:class="open && 'rotate-180'" />
                                            </x-itk.button>
                                        @endif
                                    </div>
                                    @if ($incident['count'] > 1)
                                        <ul id="incident-{{ $n }}-sites" class="flex flex-wrap gap-2 mt-3" style="display: none" x-bind:style="open ? '' : 'display: none'">
                                            @foreach ($incident['sites'] as $siteName)
                                                <li class="px-2 py-0.5 rounded-xs bg-surface-sunken border border-line text-footnote text-ink">{{ $siteName }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($incidentCount > 8)
                            <x-itk.button variant="ghost" class="mt-1" x-on:click="showAllIncidents = !showAllIncidents">
                                <span x-text="showAllIncidents ? 'Show fewer incidents' : 'Show all {{ $incidentCount }} incidents'">Show all {{ $incidentCount }} incidents</span>
                            </x-itk.button>
                        @endif
                    </div>

                    {{-- By website: compact rows, most downtime first --}}
                    <div id="panel-sites" role="tabpanel" aria-labelledby="tab-sites" tabindex="0" style="display: none"
                        x-bind:style="tab === 'sites' ? '' : 'display: none'">
                        <ul class="flex flex-wrap gap-x-6 gap-y-2 mb-2 text-callout font-normal text-ink" aria-label="Legend">
                            <li class="flex items-center gap-2"><span class="inline-block w-4 h-3 rounded-xs bg-error" aria-hidden="true"></span>Ongoing</li>
                            <li class="flex items-center gap-2"><span class="inline-block w-4 h-3 rounded-xs bg-chart-4" aria-hidden="true"></span>Resolved</li>
                        </ul>
                        <div id="ganttChart" role="img" aria-label="Outages per website over the last 24 hours, most downtime first"></div>
                        @if ($siteCount > 10)
                            <x-itk.button variant="ghost" class="mt-1" x-on:click="toggleAllSites()">
                                <span x-text="allSites ? 'Show the 10 most affected' : 'Show all {{ $siteCount }} websites'">Show all {{ $siteCount }} websites</span>
                            </x-itk.button>
                        @endif
                    </div>
                </div>
            @endif
        </x-itk.card>

        {{-- Outage events --}}
        <x-itk.card title="Outage events" class="xl:col-span-2 min-w-0">
            <form method="GET" action="{{ route('downtime.index') }}" aria-label="Filter outage events"
                class="flex flex-wrap items-center gap-3 mb-4">
                <x-itk.toggle-group label="Time range" :options="$ranges" :value="$range" :hrefs="$rangeHrefs" />

                <x-itk.select name="site" aria-label="Filter by website" onchange="this.form.submit()" class="w-full sm:w-auto">
                    <option value="">All websites</option>
                    @foreach ($sites as $s)
                        <option value="{{ $s->id }}" @selected($siteId == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </x-itk.select>

                <x-itk.select name="status" aria-label="Filter by status" onchange="this.form.submit()" class="w-full sm:w-auto">
                    <option value="" @selected($status === '')>All statuses</option>
                    <option value="active" @selected($status === 'active')>Ongoing</option>
                    <option value="resolved" @selected($status === 'resolved')>Resolved</option>
                    <option value="monitoring_stopped" @selected($status === 'monitoring_stopped')>Monitoring stopped</option>
                </x-itk.select>

                {{-- Keeps the range when the site or status filter changes --}}
                <input type="hidden" name="range" value="{{ $range }}">

                <noscript><x-itk.button type="submit" variant="secondary">Apply filters</x-itk.button></noscript>

                @if ($siteId || $status)
                    <x-itk.button variant="ghost" icon="x" :href="route('downtime.index', ['range' => $range])">Clear filters</x-itk.button>
                @endif
            </form>

            @if ($events->isEmpty())
                <x-itk.empty title="No outage events" icon="circle-check" class="py-6">
                    Nothing matches the selected period and filters.
                </x-itk.empty>
            @else
                <x-itk.table caption="Outage events" hide-caption caption-id="outage-events-caption">
                    <thead>
                        <tr>
                            <th scope="col">Website</th>
                            <th scope="col">Started</th>
                            <th scope="col">Ended</th>
                            <th scope="col" class="itk-num">Duration</th>
                            <th scope="col">Pages</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            @php
                                $secs = $event->getLiveDurationSeconds();
                                $dh = intdiv($secs, 3600);
                                $dm = intdiv($secs % 3600, 60);
                                $ds = $secs % 60;
                            @endphp
                            <tr @class(['bg-error-soft' => $event->isActive()])>
                                <th scope="row">{{ $event->site?->name ?? '—' }}</th>
                                <td class="tabular-nums">{{ $event->started_at->format('d M Y H:i') }}</td>
                                <td class="tabular-nums">
                                    @if ($event->isActive())
                                        <span class="text-ink-muted">Not yet</span>
                                    @else
                                        {{ $event->ended_at->format('d M Y H:i') }}
                                    @endif
                                </td>
                                <td class="itk-num">
                                    {{ $dh > 0 ? $dh . 'h ' : '' }}{{ $dm > 0 ? $dm . 'm ' : '' }}{{ $dh === 0 && $dm === 0 ? $ds . 's' : '' }}
                                    @if ($event->isActive())
                                        <span class="text-footnote text-ink-muted">(live)</span>
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($event->affected_pages))
                                        <x-itk.tooltip :id="'event-pages-' . $event->id" :content="implode(', ', $event->affected_pages)">
                                            <button type="button" aria-describedby="event-pages-{{ $event->id }}"
                                                class="min-h-9 px-1 rounded-xs underline decoration-dotted underline-offset-3 cursor-help">
                                                {{ count($event->affected_pages) }} {{ Str::plural('page', count($event->affected_pages)) }}
                                            </button>
                                        </x-itk.tooltip>
                                    @else
                                        <span class="text-ink-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($event->isActive())
                                        <x-itk.badge tone="danger" icon="circle-x">Ongoing</x-itk.badge>
                                    @elseif ($event->wasMonitoringStopped())
                                        <x-itk.badge tone="neutral" icon="circle-pause">Monitoring stopped</x-itk.badge>
                                    @else
                                        <x-itk.badge tone="success" icon="circle-check">Resolved</x-itk.badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-itk.table>
            @endif

            @if ($events->hasPages())
                <div class="mt-4">
                    {{ $events->links('vendor.pagination.daisy') }}
                </div>
            @endif
        </x-itk.card>

        {{-- Per-site breakdown --}}
        <x-itk.card title="By website">
            @if (empty($siteBreakdown) || count($siteBreakdown) === 0)
                <p class="text-body text-ink-muted">No outages in this period.</p>
            @else
                <ul class="grid">
                    @foreach ($siteBreakdown as $row)
                        <li class="flex items-center justify-between gap-3 py-2.5 border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="text-callout text-ink truncate">{{ $row['name'] }}</p>
                                <p class="text-footnote text-ink-muted tabular-nums">{{ $fmtDuration($row['total_seconds']) }} downtime</p>
                            </div>
                            <x-itk.badge tone="neutral" class="shrink-0">
                                {{ $row['outage_count'] }} {{ Str::plural('event', $row['outage_count']) }}
                            </x-itk.badge>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-itk.card>

    </div>

    @if (! empty($ganttLabels))
        @push('scripts')
            <script>
                // Outages card: "websites down" chart + incident list, and the compact per-website Gantt.
                function downtimeTimeline() {
                    const concurrency = @js($concurrency);
                    const ganttData = @js($ganttData);
                    const labels = @js($ganttLabels);
                    const peak = @js($peakDown);
                    const windowStart = @js($ganttStart->getTimestampMs());
                    const windowEnd = @js(now()->getTimestampMs());
                    const ROW = 32;
                    const xaxis = () => ({
                        type: 'datetime', min: windowStart, max: windowEnd, tickAmount: 12,
                        labels: { datetimeUTC: false, format: 'HH:mm' },
                    });
                    // Charts live outside Alpine's reactive data, so destroy() reaches the real objects
                    let concurrencyChart = null;
                    let ganttChart = null;

                    return {
                        tab: 'incidents',
                        showAllIncidents: false,
                        allSites: false,
                        init() {
                            this.$nextTick(() => this.renderConcurrency());
                            window.addEventListener('itk-theme-change', () => {
                                this.renderConcurrency();
                                if (ganttChart) this.renderGantt();
                            });
                        },
                        select(tab, focus = false) {
                            this.tab = tab;
                            this.$nextTick(() => {
                                if (focus) document.getElementById('tab-' + tab).focus();
                                if (tab === 'sites' && !ganttChart) this.renderGantt();
                            });
                        },
                        toggleAllSites() {
                            this.allSites = !this.allSites;
                            this.renderGantt();
                        },
                        renderConcurrency() {
                            const colors = window.itkChartColors();
                            if (concurrencyChart) concurrencyChart.destroy();
                            concurrencyChart = new ApexCharts(document.getElementById('concurrencyChart'), {
                                series: [{ name: 'Websites down', data: concurrency }],
                                chart: { type: 'area', height: 180, toolbar: { show: false }, zoom: { enabled: false }, animations: { enabled: false } },
                                stroke: { curve: 'stepline', width: 2 },
                                fill: { type: 'solid', opacity: 0.15 },
                                colors: [colors.danger],
                                dataLabels: { enabled: false },
                                xaxis: xaxis(),
                                yaxis: {
                                    min: 0, max: Math.max(peak, 1), tickAmount: Math.min(Math.max(peak, 1), 4),
                                    labels: { formatter: (v) => Math.round(v) },
                                },
                                grid: { borderColor: colors.grid },
                                tooltip: {
                                    x: { format: 'dd MMM HH:mm' },
                                    y: { formatter: (v) => v + (v === 1 ? ' website down' : ' websites down') },
                                },
                                legend: { show: false },
                            });
                            concurrencyChart.render();
                        },
                        renderGantt() {
                            const colors = window.itkChartColors();
                            const colorFor = { active: colors.danger, resolved: colors.series[3] };
                            const shown = this.allSites ? labels : labels.slice(0, 10);
                            const keep = new Set(shown);
                            if (ganttChart) ganttChart.destroy();
                            ganttChart = new ApexCharts(document.getElementById('ganttChart'), {
                                series: [{
                                    data: ganttData.filter((d) => keep.has(d.x))
                                        .map((d) => ({ ...d, fillColor: colorFor[d.state] ?? colors.series[3] })),
                                }],
                                chart: { type: 'rangeBar', height: shown.length * ROW + 70, toolbar: { show: false }, zoom: { enabled: false }, animations: { enabled: false } },
                                plotOptions: { bar: { horizontal: true, barHeight: '60%', borderRadius: 3, rangeBarGroupRows: true } },
                                xaxis: { ...xaxis(), position: 'top' },
                                yaxis: { labels: { style: { fontSize: '12px' }, maxWidth: 200 } },
                                grid: {
                                    borderColor: colors.grid,
                                    row: { colors: [window.itkToken('surface-sunken'), 'transparent'], opacity: 0.5 },
                                    xaxis: { lines: { show: true } },
                                    yaxis: { lines: { show: false } },
                                },
                                tooltip: { x: { format: 'dd MMM HH:mm' } },
                                legend: { show: false },
                            });
                            ganttChart.render();
                        },
                    };
                }
            </script>
        @endpush
    @endif
@endsection
