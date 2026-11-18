{{--
    Uptime bars (custom: the ITK Design System has no equivalent; built only from tokens and the
    Tooltip / Chart patterns). One bar per day, oldest first.

    days: PublicStatusService day rows (date, uptime, downtime_seconds, outages, items, tone).
          items are the outages touching that day, each with its public note.
    label: what the bars describe (the site name).
    editable: logged-in staff can click a day with outages (or press Enter on it) to write public
              notes. Saving posts to outage-notes.update, then fires `note-saved`; `note-editing`
              (true/false) lets the page pause its live updates while the editor is open.

    Keyboard: the row is one tab stop; Left/Right move between days, Home/End jump, Esc hides the
    tooltip (or closes the editor). Colour is never the only signal: the tooltip states each day in
    words, and screen readers get a summary that lists every day below 100% and every note.
--}}
@props(['days' => [], 'label', 'id', 'editable' => false])

@php
    $toneClass = ['ok' => 'bg-success', 'minor' => 'bg-warning', 'major' => 'bg-error', 'none' => 'bg-line'];
    $toneWord = ['ok' => 'No downtime', 'minor' => 'Brief downtime', 'major' => 'Downtime', 'none' => 'No data'];
    $stateWord = ['ongoing' => 'Ongoing', 'monitoring_stopped' => 'Monitoring stopped', 'resolved' => 'Resolved'];
    $count = count($days);

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

    // Grey days (no checks) never take notes
    $noteable = fn (array $d) => $d['tone'] !== 'none' && ! empty($d['items']);

    $points = array_map(fn ($d) => [
        'date' => \Carbon\Carbon::parse($d['date'])->format('j M Y'),
        'text' => $describe($d),
        'word' => $toneWord[$d['tone']],
        'noteable' => $noteable($d),
        'items' => array_map(fn ($o) => [
            'id' => $o['id'],
            'time' => $o['time'],
            'state' => $stateWord[$o['state']] ?? '',
            'note' => $o['note'],
        ], $d['items'] ?? []),
    ], $days);

    $problemDays = array_values(array_filter($days, fn ($d) => in_array($d['tone'], ['minor', 'major'], true)));
    $noDataDays = count(array_filter($days, fn ($d) => $d['tone'] === 'none'));
    $notedDays = array_values(array_filter($days, fn ($d) => collect($d['items'] ?? [])->contains(fn ($o) => $o['note'] !== null)));
@endphp

{{-- points come from data-points, not x-data, so an in-place page update (Alpine morph) can replace them --}}
<div {{ $attributes->class('relative') }} data-points="{{ json_encode($points) }}"
    x-data="{
        get points() { return JSON.parse(this.$root.dataset.points) },
        active: null,
        canEdit: @js((bool) $editable),
        editing: null,
        drafts: {},
        applyAll: false,
        saving: false,
        error: '',
        first() { return 0 },
        move(step) {
            const last = this.points.length - 1;
            const start = this.active === null ? last : this.active + step;
            this.active = Math.min(last, Math.max(this.first(), start));
        },
        notesOf(i) { return i === null ? [] : this.points[i].items.filter((item) => item.note) },
        get editItems() { return this.editing === null ? [] : this.points[this.editing].items },
        get dirty() { return this.editItems.some((item) => (this.drafts[item.id] ?? '') !== (item.note ?? '')) },
        open(i) {
            if (!this.canEdit || this.saving || !this.points[i].noteable) return;
            this.editing = i;
            this.active = null;
            this.error = '';
            this.applyAll = false;
            this.drafts = Object.fromEntries(this.points[i].items.map((item) => [item.id, item.note ?? '']));
            this.$dispatch('note-editing', true);
            this.$nextTick(() => this.$refs.editor.querySelector('textarea')?.focus());
        },
        close(refocus = true) {
            if (this.editing === null) return;
            this.editing = null;
            this.$dispatch('note-editing', false);
            if (refocus) this.$refs.row.focus();
        },
        async save() {
            const items = this.editItems;
            const firstId = items[0].id;
            const notes = items.map((item) => ({ id: item.id, note: this.applyAll ? this.drafts[firstId] : this.drafts[item.id] }));
            this.saving = true;
            this.error = '';
            try {
                const r = await fetch(@js(route('outage-notes.update')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                    body: JSON.stringify({ notes }),
                });
                if (r.status === 422) { this.error = (await r.json()).message; return; }
                if ([401, 403, 419].includes(r.status)) { this.error = 'Your session has ended. Log in again to save notes.'; return; }
                if (!r.ok) { this.error = 'The note could not be saved. Try again.'; return; }
                this.close();
                this.$dispatch('note-saved');
            } catch (e) {
                this.error = 'The note could not be saved. Check your connection and try again.';
            } finally {
                this.saving = false;
            }
        },
    }"
    x-effect="if (applyAll && editItems.length > 1) editItems.slice(1).forEach((item) => drafts[item.id] = drafts[editItems[0].id])"
    x-on:mouseleave="active = null">
    {{-- Tooltip for the hovered or focused day --}}
    <div id="{{ $id }}-tip" role="tooltip" x-show="active !== null && editing === null" x-cloak
        class="absolute bottom-full mb-2 z-40 w-max max-w-64 px-2.5 py-1.5 rounded-sm bg-surface-inverse text-ink-inverse text-footnote shadow-2 pointer-events-none"
        x-bind:style="active === null ? '' : (active / points.length < 0.5
            ? 'left:' + Math.max(0, (active - first()) / (points.length - first()) * 100) + '%'
            : 'right:' + Math.max(0, (points.length - 1 - active) / (points.length - first()) * 100) + '%')">
        <span class="block font-semibold" x-text="active === null ? '' : points[active].date"></span>
        <span class="block" x-text="active === null ? '' : points[active].text"></span>
        <template x-for="item in notesOf(active)" :key="item.id">
            <span class="flex items-start gap-1.5 mt-1.5">
                <x-itk.icon name="message-square-text" size="14" class="mt-0.5 shrink-0" />
                <span class="line-clamp-4 whitespace-pre-line break-words min-w-0" x-text="item.note"></span>
            </span>
        </template>
        @if ($editable)
            <span class="block mt-1.5 opacity-80" x-show="active !== null && points[active].noteable"
                x-text="active !== null && notesOf(active).length ? 'Click to edit the note' : 'Click to add a note'"></span>
        @endif
    </div>

    <div tabindex="0" role="img" x-ref="row"
        aria-label="{{ $label }}: daily uptime. Use the left and right arrow keys to read each day.{{ $editable ? ' Press Enter on a day with outages to write a note.' : '' }}"
        aria-describedby="{{ $id }}-tip {{ $id }}-summary"
        x-on:focus="if (active === null && editing === null) active = points.length - 1"
        x-on:blur="active = null"
        x-on:keydown.left.prevent="move(-1)" x-on:keydown.right.prevent="move(1)"
        x-on:keydown.home.prevent="active = first()" x-on:keydown.end.prevent="active = points.length - 1"
        x-on:keydown.escape="active = null"
        @if ($editable) x-on:keydown.enter.prevent="if (active !== null) open(active)" @endif
        class="flex items-stretch gap-0.5 h-8 rounded-xs">
        @foreach ($days as $i => $day)
            <span aria-hidden="true" x-on:mouseenter="if (editing === null) active = {{ $i }}"
                @if ($editable && $noteable($day)) x-on:click="open({{ $i }})" @endif
                x-bind:class="active === {{ $i }} || editing === {{ $i }} ? 'ring-2 ring-focus ring-offset-1 ring-offset-surface' : ''"
                @class([
                    'relative flex-1 min-w-0.5 rounded-xs',
                    $toneClass[$day['tone']],
                    'cursor-pointer' => $editable && $noteable($day),
                ])></span>
        @endforeach
    </div>

    @if ($editable)
        {{-- Note editor: opens under the clicked day. Live page updates pause while it is open. --}}
        <div x-ref="editor" x-show="editing !== null" x-cloak role="dialog"
            x-bind:aria-label="editing === null ? '' : 'Notes for ' + points[editing].date"
            class="absolute top-full mt-2 z-50 w-80 max-w-[calc(100vw-2rem)] p-4 rounded-md border border-line bg-surface-raised text-ink shadow-3"
            x-bind:style="editing === null ? '' : (editing / points.length < 0.5
                ? 'left:' + Math.max(0, editing / points.length * 100) + '%'
                : 'right:' + Math.max(0, (points.length - 1 - editing) / points.length * 100) + '%')"
            x-on:keydown.escape.stop="close()"
            x-on:click.outside="if (editing !== null && !saving && !dirty && !$refs.row.contains($event.target)) close(false)">
            <template x-if="editing !== null">
                <form class="grid gap-3" x-on:submit.prevent="save()">
                    <div>
                        <p class="text-headline text-ink" x-text="points[editing].date"></p>
                        <p class="text-footnote text-ink-muted">Public note: everyone can see it here and in the incident history.</p>
                    </div>

                    <template x-for="(item, index) in editItems" :key="item.id">
                        <div class="grid gap-1.5">
                            <label class="text-callout font-semibold text-ink" x-bind:for="'{{ $id }}-note-' + item.id">
                                <span x-text="'Outage ' + item.time"></span>
                                <span class="font-normal text-ink-muted" x-text="'· ' + item.state"></span>
                            </label>
                            <x-itk.textarea rows="2" x-bind:id="'{{ $id }}-note-' + item.id" x-model="drafts[item.id]"
                                maxlength="{{ \App\Models\DowntimeHistory::NOTE_MAX_LENGTH }}"
                                x-bind:disabled="saving || (applyAll && index > 0)"
                                placeholder="What happened, or what is being done" />
                            <template x-if="index === 0 && editItems.length > 1">
                                <x-itk.checkbox name="apply_all" id="{{ $id }}-apply-all"
                                    label="Apply this note to the other outages"
                                    x-on:change="applyAll = $event.target.checked" />
                            </template>
                        </div>
                    </template>

                    <p role="alert" class="flex items-start gap-1 text-footnote font-medium text-error" x-show="error">
                        <x-itk.icon name="circle-alert" size="16" class="mt-px shrink-0" />
                        <span x-text="error"></span>
                    </p>

                    <div class="flex justify-end gap-2">
                        <x-itk.button variant="ghost" size="sm" class="max-lg:min-h-11" x-on:click="close()"
                            x-bind:disabled="saving">Cancel</x-itk.button>
                        <x-itk.button type="submit" size="sm" class="max-lg:min-h-11" x-bind:disabled="saving"
                            x-text="saving ? 'Saving…' : 'Save'">Save</x-itk.button>
                    </div>
                </form>
            </template>
        </div>
    @endif

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
        @foreach ($notedDays as $d)
            @foreach ($d['items'] as $o)
                @if ($o['note'] !== null)
                    Note for {{ \Carbon\Carbon::parse($d['date'])->format('j M Y') }}, {{ $o['time'] }}: {{ $o['note'] }}
                @endif
            @endforeach
        @endforeach
    </p>
</div>
