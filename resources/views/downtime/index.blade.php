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
@endsection
