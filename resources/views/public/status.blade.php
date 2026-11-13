@extends('layouts.public')

@section('title', 'Website status')

@use('App\Services\PublicStatusService', 'PS')

@php
    $now = $status['generated_at'];
    $banner = [
        'operational' => ['success', 'All websites are operational', null],
        'degraded' => [
            'warning',
            'Some websites are partially down',
            'Some pages of these websites are not loading. The rest of each website works.',
        ],
        'down' => [
            'danger',
            'Some websites are down',
            'These websites cannot be reached right now. IT staff are notified automatically.',
        ],
        'paused' => [
            'info',
            'Monitoring is paused',
            'Automatic checks are switched off, so the statuses below are unknown.',
        ],
        'stale' => [
            'warning',
            'Status may be out of date',
            'The last check is older than expected, so the statuses below are unknown.',
        ],
        'empty' => ['info', 'No websites are listed yet', null],
    ][$status['state']];
    $lastChecked = $status['last_checked_at']
        ? 'Last checked ' . PS::formatTime($status['last_checked_at'], $now)
        : 'Not checked yet';
    $interval = $status['interval_minutes'];
@endphp

@section('footer-note')
    Checked automatically from ITK's server every {{ $interval }} minutes.
@endsection

@section('content')
    <div x-data="{
        version: @js($status['version']),
        changed: false,
        async poll() {
            try {
                const r = await fetch(@js(route('status.json')), { headers: { Accept: 'application/json' } });
                if (r.ok && (await r.json()).version !== this.version) this.changed = true;
            } catch (e) {}
        },
    }" x-init="setInterval(() => { if (!document.hidden && !changed) poll() }, 60000)">

        <h1 class="text-title-1 text-ink mb-4">ITK website status</h1>

        {{-- New data notice: the page never reloads by itself, so nothing moves while someone reads --}}
        <div role="status" class="mb-4" x-show="changed" x-cloak>
            <x-itk.alert tone="info">
                The status has been updated.
                <a href="{{ route('status') }}" class="font-semibold text-link underline underline-offset-3">Refresh to see
                    it</a>
            </x-itk.alert>
        </div>

        {{-- Overall status --}}
        <x-itk.alert :tone="$banner[0]" :title="$banner[1]" class="mb-2 text-body">
            @if ($banner[2])
                <span class="block">{{ $banner[2] }}</span>
            @endif
        </x-itk.alert>
        <p class="flex flex-wrap items-center gap-x-2 text-footnote text-ink-muted mb-8">
            <x-itk.icon name="clock" size="16" />
            <span class="tabular-nums">{{ $lastChecked }}</span>
            <span aria-hidden="true">·</span>
            <span>Checked every {{ $interval }} minutes</span>
        </p>

        {{-- Websites by category --}}
        @if (count($status['categories']) > 0)
            <section aria-labelledby="websites-heading" class="mb-10">
                <div class="flex flex-wrap items-end justify-between gap-3 mb-3">
                    <h2 id="websites-heading" class="text-title-2 text-ink">Websites</h2>
                    <p class="text-footnote text-ink-muted">Uptime over the last {{ $status['history_days'] }} days</p>
                </div>

                <div class="grid gap-4">
                    @foreach ($status['categories'] as $ci => $category)
                        <x-itk.collapsible :id="'category-' . $ci" as="h3" closed-below="sm" :force-open="$category['has_problem']"
                            class="bg-surface border border-line rounded-md shadow-1 px-4 md:px-6">
                            <x-slot:trigger>
                                <span class="flex flex-wrap items-center gap-3">
                                    <span class="text-title-3 text-ink">{{ $category['name'] }}</span>
                                    <span class="text-footnote text-ink-muted">
                                        {{ count($category['sites']) }}
                                        {{ \Illuminate\Support\Str::plural('website', count($category['sites'])) }}
                                    </span>
                                </span>
                            </x-slot:trigger>

                            <ul class="grid pb-2">
                                @foreach ($category['sites'] as $site)
                                    @php
                                        $fmt = fn($v) => $v === null
                                            ? 'No data'
                                            : rtrim(rtrim(number_format($v, 2), '0'), '.') . '%';
                                    @endphp
                                    <li class="grid gap-3 py-4 border-t border-line">
                                        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                                            <div class="min-w-0">
                                                <p class="text-headline text-ink">{{ $site['name'] }}</p>
                                                <a href="{{ $site['url'] }}" target="_blank" rel="noopener noreferrer"
                                                    class="inline-flex items-center gap-1 text-footnote text-link hover:underline underline-offset-3 break-all">
                                                    {{ $site['host'] }}<x-itk.icon name="external-link"
                                                        size="14" /><span class="sr-only"> (opens in a new tab)</span>
                                                </a>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="text-callout text-ink tabular-nums">
                                                    {{ $fmt($site['uptime']) }}<span class="sr-only"> uptime</span>
                                                </span>
                                                <x-itk.status-badge :status="$site['status']" />
                                            </div>
                                        </div>
                                        <x-itk.uptime-bars :days="$site['days']" :label="$site['name']" :id="'uptime-' . $site['id']" />
                                        <div class="flex justify-between text-footnote text-ink-muted" aria-hidden="true">
                                            <span>{{ $status['history_days'] }} days ago</span>
                                            <span>Today</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </x-itk.collapsible>
                    @endforeach
                </div>

                {{-- Legend: the bar colours in words --}}
                <ul class="flex flex-wrap gap-x-6 gap-y-2 mt-4 text-footnote text-ink-muted" aria-label="Uptime bar legend">
                    <li class="flex items-center gap-2"><span class="inline-block w-2 h-4 rounded-xs bg-success"
                            aria-hidden="true"></span>No downtime</li>
                    <li class="flex items-center gap-2"><span class="inline-block w-2 h-4 rounded-xs bg-warning"
                            aria-hidden="true"></span>Under 1% downtime</li>
                    <li class="flex items-center gap-2"><span class="inline-block w-2 h-4 rounded-xs bg-error"
                            aria-hidden="true"></span>1% downtime or more</li>
                    <li class="flex items-center gap-2"><span class="inline-block w-2 h-4 rounded-xs bg-line"
                            aria-hidden="true"></span>No data</li>
                </ul>
            </section>
        @endif

        {{-- Incidents --}}
        <section aria-labelledby="incidents-heading">
            <h2 id="incidents-heading" class="text-title-2 text-ink mb-1">Incidents</h2>
            <p class="text-footnote text-ink-muted mb-4">Detected automatically. Ongoing first, then the last
                {{ PS::INCIDENT_DAYS }} days.</p>

            @if (count($status['incidents']) === 0)
                <x-itk.card>
                    <x-itk.empty title="No incidents in the last {{ PS::INCIDENT_DAYS }} days" icon="circle-check"
                        class="py-6" />
                </x-itk.card>
            @else
                <ul class="grid gap-4">
                    @foreach ($status['incidents'] as $incident)
                        @php
                            $state = $incident['state'];
                            $pages = $incident['pages_count'];
                            $detail =
                                'Lasted ' .
                                PS::formatDuration($incident['duration_seconds']) .
                                ($state === 'ongoing' ? ' so far' : '') .
                                ($pages > 0
                                    ? ' · ' .
                                        $pages .
                                        ' ' .
                                        \Illuminate\Support\Str::plural('page', $pages) .
                                        ' affected'
                                    : '');
                            $steps = [
                                [
                                    'title' => 'Outage detected',
                                    'status' => 'error',
                                    'time' => PS::formatTime($incident['started_at'], $now),
                                ],
                            ];
                            $steps[] = match ($state) {
                                'ongoing' => [
                                    'title' => 'Still down, being checked every ' . $interval . ' minutes',
                                    'status' => 'current',
                                    'detail' => $detail,
                                ],
                                'monitoring_stopped' => [
                                    'title' => 'Monitoring stopped',
                                    'status' => 'pending',
                                    'word' => 'Ended',
                                    'time' => PS::formatTime($incident['ended_at'], $now),
                                    'detail' => $detail,
                                ],
                                default => [
                                    'title' => 'Resolved',
                                    'status' => 'done',
                                    'time' => PS::formatTime($incident['ended_at'], $now),
                                    'detail' => $detail,
                                ],
                            };
                        @endphp
                        <li>
                            <x-itk.card class="grid gap-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <h3 class="text-headline text-ink">Outage on {{ $incident['site'] }}</h3>
                                    @if ($state === 'ongoing')
                                        <x-itk.badge tone="danger" icon="circle-x">Ongoing</x-itk.badge>
                                    @elseif ($state === 'monitoring_stopped')
                                        <x-itk.badge tone="neutral" icon="circle-pause">Monitoring stopped</x-itk.badge>
                                    @else
                                        <x-itk.badge tone="success" icon="circle-check">Resolved</x-itk.badge>
                                    @endif
                                </div>
                                <x-itk.trace :steps="$steps" :label="'Timeline of the outage on ' . $incident['site']" />
                            </x-itk.card>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
