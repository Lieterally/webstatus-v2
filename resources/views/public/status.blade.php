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
            'These websites cannot be reached right now. We are currently working on fixing it.',
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
                                    </li>
                                @endforeach
                            </ul>
                        </x-itk.collapsible>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Incident history lives on its own page --}}
        <x-itk.card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-title-3 text-ink">Incident history</h2>
                <x-itk.button variant="secondary" :href="route('history')" icon-right="arrow-right">View history</x-itk.button>
            </div>
        </x-itk.card>
    </div>
@endsection
