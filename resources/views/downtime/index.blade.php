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

        {{-- Gantt chart (last 24 hours) --}}
        <x-itk.card title="Downtime timeline (last 24 hours)" class="xl:col-span-3">
            @if (empty($ganttLabels))
                <x-itk.empty title="No downtime in the last 24 hours" icon="circle-check" class="py-6" />
            @else
                <ul class="flex flex-wrap gap-x-6 gap-y-2 mb-3 text-callout font-normal text-ink" aria-label="Legend">
                    <li class="flex items-center gap-2"><span class="inline-block w-4 h-3 rounded-xs bg-error" aria-hidden="true"></span>Ongoing</li>
                    <li class="flex items-center gap-2"><span class="inline-block w-4 h-3 rounded-xs bg-chart-4" aria-hidden="true"></span>Resolved</li>
                </ul>
                <div id="ganttChart" role="img" aria-label="Timeline of outages per website over the last 24 hours"
                    style="max-height: 400px; overflow-y: auto;"></div>
                <style>
                    #ganttChart .apexcharts-xaxis {
                        position: sticky;
                        top: 0;
                        z-index: 10;
                    }
                </style>
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
                document.addEventListener('DOMContentLoaded', function() {
                    const ganttData = @js($ganttData);
                    const chartHeight = Math.max(250, ganttData.length * 40 + 80);
                    let chart = null;

                    // Bars are coloured from the event state, read from the tokens of the current theme:
                    // ongoing = danger, resolved = chart-4 (neutral slate). The legend above names both.
                    const buildOptions = () => {
                        const colors = window.itkChartColors();
                        const colorFor = { active: colors.danger, resolved: colors.series[3] };
                        return {
                            series: [{
                                data: ganttData.map((d) => ({ ...d, fillColor: colorFor[d.state] ?? colors.series[3] })),
                            }],
                            chart: {
                                type: 'rangeBar',
                                height: chartHeight,
                                toolbar: { show: false },
                                zoom: { enabled: true },
                            },
                            plotOptions: {
                                bar: {
                                    horizontal: true,
                                    barHeight: '50%',
                                    borderRadius: 6,
                                    rangeBarGroupRows: true,
                                }
                            },
                            xaxis: {
                                type: 'datetime',
                                position: 'top',
                                min: @js($ganttStart->getTimestampMs()),
                                max: @js(now()->getTimestampMs()),
                                labels: { datetimeUTC: false, format: 'HH:mm' },
                                tickAmount: 24,
                            },
                            yaxis: {
                                labels: { style: { fontSize: '12px' } }
                            },
                            grid: {
                                row: {
                                    colors: [window.itkToken('surface-sunken'), 'transparent'],
                                    opacity: 0.5,
                                },
                                xaxis: { lines: { show: true } },
                                yaxis: { lines: { show: false } },
                            },
                            tooltip: {
                                x: { format: 'dd MMM HH:mm' },
                            },
                            legend: { show: false },
                        };
                    };

                    const renderChart = () => {
                        if (chart) { chart.destroy(); }
                        chart = new ApexCharts(document.getElementById('ganttChart'), buildOptions());
                        chart.render();
                    };

                    renderChart();
                    window.addEventListener('itk-theme-change', renderChart);
                });
            </script>
        @endpush
    @endif
@endsection
