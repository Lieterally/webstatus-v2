@extends('layouts.public')

@section('title', 'Webstatus')

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
    {{--
        Live update: every 60 s (and when the tab comes back into view) the page compares versions with
        /status.json. When they differ it fetches this page again and morphs #status-live in place, so
        open categories, scroll position, focus and tooltips stay as they are. The changing values show
        a skeleton while the new data loads.
        Search filters the website list in the browser by name or address; matching categories open.
        Logged-in staff can click a day square to write public notes; live updates pause while a note
        editor is open, and the page updates as soon as a note is saved.
    --}}
    <div x-data="{
        version: @js($status['version']),
        updating: false,
        noteEditing: false,
        announcement: '',
        query: '',
        matches(text) {
            const q = this.query.trim().toLowerCase();
            return q === '' || (text ?? '').includes(q);
        },
        get noMatches() {
            return this.query.trim() !== '' &&
                [...this.$refs.live.querySelectorAll('[data-key^=site-]')].every((el) => !this.matches(el.dataset.search));
        },
        openMatches() {
            if (this.query.trim() === '') return;
            this.$refs.live.querySelectorAll('[data-key^=category-]').forEach((el) => {
                if (this.matches(el.dataset.search)) Alpine.$data(el).open = true;
            });
        },
        async poll() {
            if (this.updating || this.noteEditing || document.hidden) return;
            try {
                const r = await fetch(@js(route('status.json')), { headers: { Accept: 'application/json' }, cache: 'no-store' });
                if (r.ok && (await r.json()).version !== this.version) await this.update();
            } catch (e) {}
        },
        async update() {
            const live = this.$refs.live;
            this.updating = true;
            try {
                // Keep the skeleton up long enough to read as a deliberate update, not a flicker
                const [r] = await Promise.all([
                    fetch(@js(route('status')), { headers: { Accept: 'text/html' }, cache: 'no-store' }),
                    new Promise((done) => setTimeout(done, 500)),
                ]);
                if (!r.ok) return;
                const next = new DOMParser().parseFromString(await r.text(), 'text/html').getElementById('status-live');
                if (!next) return;
                const hadProblem = new Set([...live.querySelectorAll('[data-problem]')].map((el) => el.dataset.key));
                Alpine.morph(live, next, {
                    key: (el) => el.dataset?.key,
                    // Leave the search box alone so typing and focus aren't interrupted
                    updating: (el, toEl, childrenOnly, skip) => { if (el.dataset && 'morphSkip' in el.dataset) skip(); },
                });
                // A category that newly has a problem opens, as it would on a fresh load
                live.querySelectorAll('[data-problem]').forEach((el) => {
                    if (!hadProblem.has(el.dataset.key)) Alpine.$data(el).open = true;
                });
                this.version = live.dataset.version;
                this.announcement = 'Status updated. ' + (live.querySelector('[data-last-checked]')?.textContent.trim() ?? '');
            } catch (e) {} finally {
                this.updating = false;
            }
        },
    }" x-init="setInterval(() => poll(), 60000);
    $watch('query', () => openMatches())" x-on:visibilitychange.document="poll()"
        x-on:note-editing.window="noteEditing = $event.detail" x-on:note-saved.window="update()">

        <h1 class="text-title-1 text-ink mb-4">ITK Webstatus</h1>

        <p role="status" class="sr-only" x-text="announcement"></p>

        <div id="status-live" x-ref="live" data-version="{{ $status['version'] }}"
            x-bind:aria-busy="updating ? 'true' : 'false'">

            {{-- Overall status --}}
            <x-itk.alert :tone="$banner[0]" :title="$banner[1]" class="mb-2 text-body live-skeleton">
                @if ($banner[2])
                    <span class="block">{{ $banner[2] }}</span>
                @endif
            </x-itk.alert>
            <p class="flex flex-wrap items-center gap-x-2 text-footnote text-ink-muted mb-8">
                <x-itk.icon name="clock" size="16" />
                <span class="tabular-nums rounded-xs live-skeleton" data-last-checked>{{ $lastChecked }}</span>
                <span aria-hidden="true">·</span>
                <span>Checked every {{ $interval }} minutes</span>
            </p>

            {{-- Websites by category --}}
            @if (count($status['categories']) > 0)
                <section aria-labelledby="websites-heading" class="mb-10">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <h2 id="websites-heading" class="text-title-2 text-ink">Websites</h2>
                        <div class="relative w-full sm:w-72" data-morph-skip>
                            <x-itk.icon name="search" class="pointer-events-none absolute left-3 top-3 text-ink-muted" />
                            <x-itk.input type="search" x-model="query" aria-label="Search websites"
                                placeholder="Search by name or address" class="pl-10" />
                        </div>
                    </div>

                    <div class="grid gap-4">
                        @foreach ($status['categories'] as $ci => $category)
                            <x-itk.collapsible :id="'category-' . $ci" as="h3" closed-below="sm" :force-open="$category['has_problem']"
                                :data-key="'category-' . $category['name']" :data-problem="$category['has_problem'] ? '1' : null" :data-search="collect($category['sites'])
                                    ->map(fn($s) => mb_strtolower($s['name'] . ' ' . $s['host']))
                                    ->implode(' | ')" x-show="matches($el.dataset.search)"
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
                                        <li class="grid gap-3 py-4 border-t border-line" data-key="site-{{ $site['id'] }}"
                                            data-search="{{ mb_strtolower($site['name'] . ' ' . $site['host']) }}"
                                            x-show="matches($el.dataset.search)">
                                            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                                                <div class="min-w-0">
                                                    <p class="text-headline text-ink">{{ $site['name'] }}</p>
                                                    <a href="{{ $site['url'] }}" target="_blank" rel="noopener noreferrer"
                                                        class="inline-flex items-center gap-1 text-footnote text-link hover:underline underline-offset-3 break-all">
                                                        {{ $site['host'] }}<x-itk.icon name="external-link"
                                                            size="14" /><span class="sr-only"> (opens in a new
                                                            tab)</span>
                                                    </a>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span
                                                        class="text-callout text-ink tabular-nums rounded-xs live-skeleton">
                                                        {{ $fmt($site['uptime']) }}<span class="sr-only"> uptime</span>
                                                    </span>
                                                    <x-itk.status-badge :status="$site['status']" class="live-skeleton" />
                                                </div>
                                            </div>
                                            <x-itk.uptime-bars :days="$site['days']" :label="$site['name']" :id="'uptime-' . $site['id']"
                                                :editable="auth()->check()" class="live-skeleton rounded-xs" />
                                        </li>
                                    @endforeach
                                </ul>
                            </x-itk.collapsible>
                        @endforeach
                    </div>

                    <x-itk.card x-show="noMatches" x-cloak>
                        <x-itk.empty title="No websites match your search" icon="search" class="py-6">
                            Try another name or web address.
                        </x-itk.empty>
                    </x-itk.card>
                </section>
            @endif

        </div>

        {{-- Incident history lives on its own page --}}
        <x-itk.card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-title-3 text-ink">Incident history</h2>
                <x-itk.button variant="secondary" :href="route('history')" icon-right="arrow-right">View history</x-itk.button>
            </div>
        </x-itk.card>
    </div>
@endsection
