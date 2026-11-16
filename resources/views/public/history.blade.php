@extends('layouts.public')

@section('title', 'Incident history')

@use('App\Services\PublicStatusService', 'PS')

@php
    $tz = config('app.timezone');
    $interval = $history['interval_minutes'];
    $clock = fn ($t) => \Carbon\CarbonImmutable::parse($t)->setTimezone($tz)->format('H.i');
    $timeRange = function (array $i) use ($clock, $tz) {
        $start = \Carbon\CarbonImmutable::parse($i['started_at'])->setTimezone($tz);
        if (! $i['ended_at']) {
            return $clock($start) . ' WITA – now';
        }
        $end = \Carbon\CarbonImmutable::parse($i['ended_at'])->setTimezone($tz);
        $endText = $end->isSameDay($start) ? $clock($end) : $end->format('j M') . ', ' . $clock($end);

        return $clock($start) . ' – ' . $endText . ' WITA';
    };
    $describe = function (array $i) use ($interval) {
        $duration = PS::formatDuration($i['duration_seconds']);
        $pages = $i['pages_count'];
        $pageText = $pages > 0 ? ' ' . $pages . ' ' . \Illuminate\Support\Str::plural('page', $pages) . ' affected.' : '';

        return match ($i['state']) {
            'ongoing' => "Down for {$duration} so far. We are currently working on fixing it.{$pageText}",
            'monitoring_stopped' => "Monitoring was switched off after {$duration}, so it is not known when the website came back.{$pageText}",
            default => "The website was back up after {$duration}.{$pageText}",
        };
    };
    $tone = [
        'ongoing' => ['border-error', 'danger', 'circle-x', 'Ongoing'],
        'monitoring_stopped' => ['border-line-strong', 'neutral', 'circle-pause', 'Monitoring stopped'],
        'resolved' => ['border-success', 'success', 'circle-check', 'Resolved'],
    ];
    $selected = $history['category_id'];
    $selectedName = collect($history['categories'])->firstWhere('id', $selected)['name'] ?? null;
@endphp

@section('footer-note')
    Checked automatically from ITK's server every {{ $interval }} minutes.
@endsection

@section('content')
    <x-itk.back-link :href="route('status')" class="mb-2">Website status</x-itk.back-link>

    <x-itk.page-header title="Incident history"
        description="Outages detected automatically over the last {{ PS::HISTORY_MONTHS }} months." />

    {{-- Category filter: a plain GET form, so it works without JavaScript --}}
    <form method="get" action="{{ route('history') }}" class="flex flex-wrap items-end gap-3 mb-6">
        <x-itk.field name="category" label="Category" class="w-full sm:w-72">
            <x-itk.select x-data x-on:change="$el.form.requestSubmit()">
                <option value="">All categories</option>
                @foreach ($history['categories'] as $category)
                    <option value="{{ $category['id'] }}" @selected($selected === $category['id'])>{{ $category['name'] }}</option>
                @endforeach
            </x-itk.select>
        </x-itk.field>
        <noscript>
            <x-itk.button type="submit" variant="secondary">Apply</x-itk.button>
        </noscript>
    </form>

    <div class="grid gap-4">
        @foreach ($history['months'] as $mi => $month)
            <x-itk.collapsible :id="'month-' . $month['key']" as="h2" :open="$mi === 0 || $month['has_ongoing']"
                class="bg-surface border border-line rounded-md shadow-1 px-4 md:px-6">
                <x-slot:trigger>
                    <span class="flex flex-wrap items-center gap-3">
                        <span class="text-title-3 text-ink">{{ $month['label'] }}</span>
                        <span class="text-footnote text-ink-muted">
                            {{ $month['count'] === 0 ? 'No' : $month['count'] }}
                            {{ \Illuminate\Support\Str::plural('incident', $month['count']) }}
                        </span>
                    </span>
                </x-slot:trigger>

                @if ($month['count'] === 0)
                    <p class="py-4 border-t border-line text-callout text-ink-muted">
                        No incidents{{ $selectedName ? ' in ' . $selectedName : '' }} this month.
                    </p>
                @else
                    <ol class="grid pb-2">
                        @foreach ($month['days'] as $day)
                            @php $date = \Carbon\CarbonImmutable::parse($day['date']); @endphp
                            <li class="grid gap-3 sm:grid-cols-[4rem_1fr] py-4 border-t border-line">
                                <h3 class="flex sm:flex-col items-baseline gap-2 sm:gap-0">
                                    <span class="text-title-3 text-ink tabular-nums">{{ $date->format('j') }}</span>
                                    <span class="text-footnote text-ink-muted">{{ $date->format('D') }}<span
                                            class="sr-only"> {{ $date->format('j F Y') }}</span></span>
                                </h3>
                                <ul class="grid gap-5 min-w-0">
                                    @foreach ($day['incidents'] as $incident)
                                        @php [$border, $badgeTone, $badgeIcon, $badgeText] = $tone[$incident['state']]; @endphp
                                        <li class="grid gap-1 border-l-4 {{ $border }} pl-4 min-w-0">
                                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                                <h4 class="text-headline text-ink">Outage on {{ $incident['site'] }}</h4>
                                                <span class="text-footnote text-ink-muted tabular-nums">{{ $timeRange($incident) }}</span>
                                            </div>
                                            <p class="text-callout text-ink-muted">{{ $describe($incident) }}</p>
                                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                                <x-itk.badge :tone="$badgeTone" :icon="$badgeIcon">{{ $badgeText }}</x-itk.badge>
                                                @unless ($selected)
                                                    <span class="text-footnote text-ink-muted">{{ $incident['category'] }}</span>
                                                @endunless
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-itk.collapsible>
        @endforeach
    </div>
@endsection
