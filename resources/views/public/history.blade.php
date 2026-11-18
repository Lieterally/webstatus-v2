@extends('layouts.public')

@section('title', 'Incident history')

@use('App\Services\PublicStatusService', 'PS')

@php
    $tz = config('app.timezone');
    $interval = $history['interval_minutes'];
    $timeRange = fn (array $i) => PS::formatRange($i['started_at'], $i['ended_at']);
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
@endphp

@section('footer-note')
    Checked automatically from ITK's server every {{ $interval }} minutes.
@endsection

@section('content')
    <x-itk.back-link :href="route('status')" class="mb-2">Website status</x-itk.back-link>

    {{--
        Search filters the incidents in the browser by website name or address (like the status page):
        months without a match hide, months with one open, and each month's count shows the matches.
    --}}
    <div x-data="{
        query: '',
        matches(text) {
            const q = this.query.trim().toLowerCase();
            return q === '' || (text ?? '').includes(q);
        },
        count(month) {
            return [...month.querySelectorAll('[data-search]')].filter((el) => this.matches(el.dataset.search)).length;
        },
        get searching() { return this.query.trim() !== '' },
        get noMatches() {
            return this.searching && [...this.$root.querySelectorAll('[data-search]')].every((el) => !this.matches(el.dataset.search));
        },
    }" x-init="$watch('query', () => {
        if (!searching) return;
        $root.querySelectorAll('[data-month]').forEach((el) => { if (count(el) > 0) Alpine.$data(el).open = true });
    })">

    <x-itk.page-header title="Incident history"
        description="Outages detected automatically over the last {{ PS::HISTORY_MONTHS }} months.">
        <x-slot:actions>
            <div class="relative w-full sm:w-72">
                <x-itk.icon name="search" class="pointer-events-none absolute left-3 top-3 text-ink-muted" />
                <x-itk.input type="search" x-model="query" aria-label="Search incidents by website"
                    placeholder="Search by name or address" class="pl-10" />
            </div>
        </x-slot:actions>
    </x-itk.page-header>

    <div class="grid gap-4">
        @foreach ($history['months'] as $mi => $month)
            <x-itk.collapsible :id="'month-' . $month['key']" as="h2" :open="$mi === 0 || $month['has_ongoing']"
                data-month x-show="!searching || count($el) > 0"
                class="bg-surface border border-line rounded-md shadow-1 px-4 md:px-6">
                <x-slot:trigger>
                    <span class="flex flex-wrap items-center gap-3">
                        <span class="text-title-3 text-ink">{{ $month['label'] }}</span>
                        <span class="text-footnote text-ink-muted" x-show="!searching">
                            {{ $month['count'] === 0 ? 'No' : $month['count'] }}
                            {{ \Illuminate\Support\Str::plural('incident', $month['count']) }}
                        </span>
                        <span class="text-footnote text-ink-muted" x-show="searching" x-cloak
                            x-text="(n => n + (n === 1 ? ' matching incident' : ' matching incidents'))(count($el.closest('[data-month]')))"></span>
                    </span>
                </x-slot:trigger>

                @if ($month['count'] === 0)
                    <p class="py-4 border-t border-line text-callout text-ink-muted">
                        No incidents this month.
                    </p>
                @else
                    <ol class="grid pb-2">
                        @foreach ($month['days'] as $day)
                            @php $date = \Carbon\CarbonImmutable::parse($day['date']); @endphp
                            <li class="grid gap-3 sm:grid-cols-[4rem_1fr] py-4 border-t border-line"
                                x-show="!searching || count($el) > 0">
                                <h3 class="flex sm:flex-col items-baseline gap-2 sm:gap-0">
                                    <span class="text-title-3 text-ink tabular-nums">{{ $date->format('j') }}</span>
                                    <span class="text-footnote text-ink-muted">{{ $date->format('D') }}<span
                                            class="sr-only"> {{ $date->format('j F Y') }}</span></span>
                                </h3>
                                <ul class="grid gap-5 min-w-0">
                                    @foreach ($day['incidents'] as $incident)
                                        @php [$border, $badgeTone, $badgeIcon, $badgeText] = $tone[$incident['state']]; @endphp
                                        <li class="grid gap-1 border-l-4 {{ $border }} pl-4 min-w-0"
                                            data-search="{{ mb_strtolower($incident['site'] . ' ' . $incident['host']) }}"
                                            x-show="matches($el.dataset.search)">
                                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                                <h4 class="text-headline text-ink">Outage on {{ $incident['site'] }}</h4>
                                                <span class="text-footnote text-ink-muted tabular-nums">{{ $timeRange($incident) }}</span>
                                            </div>
                                            <p class="text-callout text-ink-muted">{{ $describe($incident) }}</p>
                                            @if ($incident['note'])
                                                <p class="flex items-start gap-2 mt-1 text-callout text-ink">
                                                    <x-itk.icon name="message-square-text" size="16" class="mt-0.5 shrink-0 text-link" />
                                                    <span class="whitespace-pre-line break-words min-w-0"><span class="sr-only">Note from ITK: </span>{{ $incident['note'] }}</span>
                                                </p>
                                            @endif
                                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                                <x-itk.badge :tone="$badgeTone" :icon="$badgeIcon">{{ $badgeText }}</x-itk.badge>
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

        <x-itk.card x-show="noMatches" x-cloak>
            <x-itk.empty title="No incidents match your search" icon="search" class="py-6">
                Try another name or web address.
            </x-itk.empty>
        </x-itk.card>
    </div>
    </div>
@endsection
