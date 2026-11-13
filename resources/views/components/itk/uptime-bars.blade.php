{{--
    Uptime bars (custom: the ITK Design System has no equivalent; built only from tokens and the
    Tooltip / Chart patterns). One bar per day, oldest first.

    days: PublicStatusService day rows (date, uptime, downtime_seconds, outages, tone).
    label: what the bars describe (the site name).

    Keyboard: the row is one tab stop; Left/Right move between days, Home/End jump, Esc hides the
    tooltip. Colour is never the only signal: the tooltip states each day in words, and screen
    readers get a summary that lists every day below 100%.
    Below 640px only the last 30 days are drawn.
--}}
@props(['days' => [], 'label', 'id'])

@php
    $toneClass = ['ok' => 'bg-success', 'minor' => 'bg-warning', 'major' => 'bg-error', 'none' => 'bg-line'];
    $toneWord = ['ok' => 'No downtime', 'minor' => 'Brief downtime', 'major' => 'Downtime', 'none' => 'No data'];
    $count = count($days);
    $mobileFrom = max(0, $count - 30);

    $describe = function (array $d) use ($toneWord): string {
        if ($d['uptime'] === null) {
            return 'No data';
        }
        $text = rtrim(rtrim(number_format($d['uptime'], 2), '0'), '.') . '% uptime';
        if ($d['downtime_seconds'] > 0) {
            $text .= ' · ' . \App\Services\PublicStatusService::formatDuration($d['downtime_seconds']) . ' down';
        }
        if ($d['outages'] > 0) {
            $text .= ' · ' . $d['outages'] . ' ' . \Illuminate\Support\Str::plural('outage', $d['outages']);
        }

        return $text;
    };

    $points = array_map(fn ($d) => [
        'date' => \Carbon\Carbon::parse($d['date'])->format('j M Y'),
        'text' => $describe($d),
        'word' => $toneWord[$d['tone']],
    ], $days);

    $problemDays = array_values(array_filter($days, fn ($d) => in_array($d['tone'], ['minor', 'major'], true)));
    $noDataDays = count(array_filter($days, fn ($d) => $d['tone'] === 'none'));
@endphp

<div {{ $attributes->class('relative') }}
    x-data="{
        points: @js($points),
        active: null,
        mobileFrom: {{ $mobileFrom }},
        first() { return window.matchMedia('(min-width: 640px)').matches ? 0 : this.mobileFrom },
        move(step) {
            const last = this.points.length - 1;
            const start = this.active === null ? last : this.active + step;
            this.active = Math.min(last, Math.max(this.first(), start));
        },
    }"
    x-on:mouseleave="active = null">
    {{-- Tooltip for the hovered or focused day --}}
    <div id="{{ $id }}-tip" role="tooltip" x-show="active !== null" x-cloak
        class="absolute bottom-full mb-2 z-40 w-max max-w-64 px-2.5 py-1.5 rounded-sm bg-surface-inverse text-ink-inverse text-footnote shadow-2 pointer-events-none"
        x-bind:style="active === null ? '' : (active / points.length < 0.5
            ? 'left:' + Math.max(0, (active - first()) / (points.length - first()) * 100) + '%'
            : 'right:' + Math.max(0, (points.length - 1 - active) / (points.length - first()) * 100) + '%')">
        <span class="block font-semibold" x-text="active === null ? '' : points[active].date"></span>
        <span class="block" x-text="active === null ? '' : points[active].text"></span>
    </div>

    <div tabindex="0" role="img"
        aria-label="{{ $label }}: daily uptime. Use the left and right arrow keys to read each day."
        aria-describedby="{{ $id }}-tip {{ $id }}-summary"
        x-on:focus="if (active === null) active = points.length - 1"
        x-on:blur="active = null"
        x-on:keydown.left.prevent="move(-1)" x-on:keydown.right.prevent="move(1)"
        x-on:keydown.home.prevent="active = first()" x-on:keydown.end.prevent="active = points.length - 1"
        x-on:keydown.escape="active = null"
        class="flex items-stretch gap-0.5 h-8 rounded-xs">
        @foreach ($days as $i => $day)
            <span aria-hidden="true" x-on:mouseenter="active = {{ $i }}"
                x-bind:class="active === {{ $i }} ? 'ring-2 ring-focus ring-offset-1 ring-offset-surface' : ''"
                @class([
                    'flex-1 min-w-0.5 rounded-xs',
                    $toneClass[$day['tone']],
                    'max-sm:hidden' => $i < $mobileFrom,
                ])></span>
        @endforeach
    </div>

    <p id="{{ $id }}-summary" class="sr-only">
        @if (count($problemDays) === 0)
            No downtime recorded in the last {{ $count }} days.
        @else
            Days with downtime:
            @foreach ($problemDays as $d)
                {{ \Carbon\Carbon::parse($d['date'])->format('j M Y') }}, {{ $describe($d) }}@if (! $loop->last);@else.@endif
            @endforeach
        @endif
        @if ($noDataDays > 0)
            {{ $noDataDays }} {{ \Illuminate\Support\Str::plural('day', $noDataDays) }} without data.
        @endif
    </p>
</div>
