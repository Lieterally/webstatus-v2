@extends('layouts.app')

@section('title', 'Downtime Details')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Downtime Details</h1>
            <p class="text-sm text-base-content/60 mt-1">Outage history across all monitored websites</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="stats stats-vertical sm:stats-horizontal bg-base-100 shadow-sm w-full mb-6">
        <div class="stat">
            <div class="stat-figure text-error">
                <i class="fa-regular fa-triangle-exclamation fa-lg"></i>
            </div>
            <div class="stat-title">Total Outage Events</div>
            <div class="stat-value text-2xl">{{ $totalEvents }}</div>
            <div class="stat-desc">in selected period</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-error">
                <i class="fa-solid fa-circle-dot fa-lg animate-pulse"></i>
            </div>
            <div class="stat-title">Currently Active</div>
            <div class="stat-value text-2xl {{ $activeNow > 0 ? 'text-error' : 'text-success' }}">
                {{ $activeNow }}
            </div>
            <div class="stat-desc">ongoing outages</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-warning">
                <i class="fa-regular fa-clock fa-lg"></i>
            </div>
            <div class="stat-title">Total Downtime</div>
            <div class="stat-value text-2xl">
                @php
                    $h = intdiv($totalDowntimeSeconds, 3600);
                    $m = intdiv($totalDowntimeSeconds % 3600, 60);
                @endphp
                {{ $h > 0 ? $h . 'h ' : '' }}{{ $m }}m
            </div>
            <div class="stat-desc">in selected period</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-base-content/50">
                <i class="fa-regular fa-globe fa-lg"></i>
            </div>
            <div class="stat-title">Most Affected</div>
            <div class="stat-value text-lg truncate">{{ $mostAffectedSite ?? '—' }}</div>
            <div class="stat-desc">highest outage count</div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Gantt Chart (Last 24 Hours) --}}
        <div class="xl:col-span-3 card bg-base-100 shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-base mb-2">Downtime Timeline (Last 24 Hours)</h2>
                @if (empty($ganttLabels))
                    <p class="text-sm text-base-content/50">No downtime events in the last 24 hours.</p>
                @else
                    <div class="overflow-y-auto" style="max-height: 400px;">
                        <div style="height: {{ max(150, count($ganttLabels) * 50) }}px; min-height: 100%;">
                            <canvas id="ganttChart"></canvas>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Outage Events Table --}}
        <div class="xl:col-span-2 card bg-base-100 shadow-sm">
            <div class="card-body p-0">

                {{-- Filters --}}
                <div class="p-4 border-b border-base-200">
                    <form method="GET" action="{{ route('downtime.index') }}" class="flex flex-wrap items-center gap-3">

                        {{-- Time range --}}
                        <div class="join">
                            @foreach (['1d' => '1D', '3d' => '3D', '7d' => '7D', '1m' => '1M', '3m' => '3M', '6m' => '6M', '1y' => '1Y', 'all' => 'All'] as $value => $label)
                                <a href="{{ route('downtime.index', array_merge(request()->except('page'), ['range' => $value])) }}"
                                    class="join-item btn btn-sm {{ $range === $value ? 'btn-primary' : 'btn-ghost' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>

                        {{-- Site filter --}}
                        <select name="site" class="select select-sm select-bordered" onchange="this.form.submit()">
                            <option value="">All Sites</option>
                            @foreach ($sites as $s)
                                <option value="{{ $s->id }}" {{ $siteId == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Status filter --}}
                        <select name="status" class="select select-sm select-bordered" onchange="this.form.submit()">
                            <option value="" {{ $status === '' ? 'selected' : '' }}>All Status</option>
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        </select>

                        {{-- Hidden range input so site/status filter preserves range --}}
                        <input type="hidden" name="range" value="{{ $range }}">

                        @if ($siteId || $status)
                            <a href="{{ route('downtime.index', ['range' => $range]) }}"
                                class="btn btn-sm btn-ghost">Clear</a>
                        @endif
                    </form>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr>
                                <th>Site</th>
                                <th>Started</th>
                                <th>Ended</th>
                                <th>Duration</th>
                                <th>Pages</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($events as $event)
                                <tr class="{{ $event->isActive() ? 'bg-error/5' : '' }}">
                                    <td class="font-medium">{{ $event->site?->name ?? '—' }}</td>
                                    <td class="text-sm text-base-content/70 whitespace-nowrap">
                                        {{ $event->started_at->format('d M Y H:i') }}
                                    </td>
                                    <td class="text-sm text-base-content/70 whitespace-nowrap">
                                        @if ($event->isActive())
                                            <span class="text-error">Ongoing</span>
                                        @else
                                            {{ $event->ended_at->format('d M Y H:i') }}
                                        @endif
                                    </td>
                                    <td class="text-sm whitespace-nowrap">
                                        @php
                                            $secs = $event->getLiveDurationSeconds();
                                            $dh = intdiv($secs, 3600);
                                            $dm = intdiv($secs % 3600, 60);
                                            $ds = $secs % 60;
                                        @endphp
                                        {{ $dh > 0 ? $dh . 'h ' : '' }}{{ $dm > 0 ? $dm . 'm ' : '' }}{{ $dh === 0 && $dm === 0 ? $ds . 's' : '' }}
                                        @if ($event->isActive())
                                            <span class="text-error/60 text-xs">(live)</span>
                                        @endif
                                    </td>
                                    <td class="text-sm text-base-content/60 max-w-xs truncate">
                                        @if (!empty($event->affected_pages))
                                            <span class="tooltip tooltip-left"
                                                data-tip="{{ implode(', ', $event->affected_pages) }}">
                                                {{ count($event->affected_pages) }}
                                                {{ Str::plural('page', count($event->affected_pages)) }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($event->isActive())
                                            <span class="badge badge-error badge-sm gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="badge badge-ghost badge-sm">Resolved</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-base-content/50 py-8">
                                        No outage events found for the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($events->hasPages())
                    <div class="p-4 border-t border-base-200">
                        {{ $events->links('vendor.pagination.daisy') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Per-site Breakdown --}}
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-base mb-4">Site Breakdown</h2>

                @forelse ($siteBreakdown as $row)
                    <div class="flex items-center justify-between py-2 border-b border-base-200 last:border-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ $row['name'] }}</p>
                            <p class="text-xs text-base-content/50">
                                @php
                                    $bh = intdiv($row['total_seconds'], 3600);
                                    $bm = intdiv($row['total_seconds'] % 3600, 60);
                                @endphp
                                {{ $bh > 0 ? $bh . 'h ' : '' }}{{ $bm }}m downtime
                            </p>
                        </div>
                        <span class="badge badge-ghost badge-sm ml-2 shrink-0">
                            {{ $row['outage_count'] }} {{ Str::plural('event', $row['outage_count']) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-base-content/50">No data for this period.</p>
                @endforelse
            </div>
        </div>

    </div>

    @if (!empty($ganttLabels))
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ganttData = @js($ganttData);
                    const labels = @js($ganttLabels);
                    const ctx = document.getElementById('ganttChart');

                    // Group events by site and build layered datasets for alignment
                    const grouped = {};
                    ganttData.forEach(event => {
                        if (!grouped[event.site]) grouped[event.site] = [];
                        grouped[event.site].push(event);
                    });

                    const maxEvents = Math.max(...Object.values(grouped).map(g => g.length), 1);
                    const datasets = [];

                    for (let layer = 0; layer < maxEvents; layer++) {
                        const data = labels.map(site => {
                            const events = grouped[site] || [];
                            if (layer < events.length) {
                                return [events[layer].start, events[layer].end];
                            }
                            return null;
                        });

                        const colors = labels.map(site => {
                            const events = grouped[site] || [];
                            if (layer < events.length) {
                                return events[layer].active ? '#DC2626' : '#F87171';
                            }
                            return 'transparent';
                        });

                        datasets.push({
                            data: data,
                            backgroundColor: colors,
                            borderColor: colors.map(c => c === '#DC2626' ? '#991B1B' : (c === '#F87171' ?
                                '#DC2626' : 'transparent')),
                            borderWidth: 0,
                            borderRadius: 999,
                            borderSkipped: false,
                            barThickness: 16,
                            skipNull: true,
                        });
                    }

                    // Plugin to draw alternating row backgrounds
                    const alternatingRowsPlugin = {
                        id: 'alternatingRows',
                        beforeDraw(chart) {
                            const {
                                ctx,
                                chartArea,
                                scales
                            } = chart;
                            if (!scales.y) return;

                            const yScale = scales.y;
                            const ticks = yScale.ticks;

                            ctx.save();
                            ticks.forEach((tick, index) => {
                                if (index % 2 === 0) {
                                    const y = yScale.getPixelForTick(index);
                                    const halfHeight = (yScale.height / ticks.length) / 2;
                                    ctx.fillStyle = 'rgba(0, 0, 0, 0.03)';
                                    ctx.fillRect(
                                        chartArea.left,
                                        y - halfHeight,
                                        chartArea.width,
                                        halfHeight * 2
                                    );
                                }
                            });
                            ctx.restore();
                        }
                    };

                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: datasets,
                        },
                        plugins: [alternatingRowsPlugin],
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const raw = context.raw.x;
                                            const startH = Math.floor(raw[0]);
                                            const startM = Math.round((raw[0] - startH) * 60);
                                            const endH = Math.floor(raw[1]);
                                            const endM = Math.round((raw[1] - endH) * 60);
                                            const duration = raw[1] - raw[0];
                                            const durH = Math.floor(duration);
                                            const durM = Math.round((duration - durH) * 60);
                                            const pad = n => String(n).padStart(2, '0');
                                            return `${pad(startH)}:${pad(startM)} - ${pad(endH)}:${pad(endM)} (${durH > 0 ? durH + 'h ' : ''}${durM}m)`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    min: 0,
                                    max: 24,
                                    title: {
                                        display: true,
                                        text: 'Hours'
                                    },
                                    ticks: {
                                        stepSize: 2,
                                        callback: function(value) {
                                            const startTs = @js($ganttStart->timestamp * 1000);
                                            const h = new Date(startTs + value * 3600000);
                                            return h.getHours().toString().padStart(2, '0') + ':00';
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0,0,0,0.05)'
                                    }
                                },
                                y: {
                                    title: {
                                        display: false
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
        @endpush
    @endif
@endsection
