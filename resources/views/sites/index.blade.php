@extends('layouts.app')

@section('title', 'Website manager')

@section('content')
    @php
        $filterKeys = ['search', 'category', 'person', 'monitoring', 'visibility'];
        $hasFilters = collect($filterKeys)->contains(fn ($key) => filled(request($key)));
    @endphp
    <div x-data="{
            target: { name: '', url: '' },
            confirmDelete(name, url) { this.target = { name, url }; $refs.confirmDialog.showModal(); },
            selected: [],
            pageIds: @js($sites->pluck('id')),
            get allSelected() { return this.pageIds.length > 0 && this.selected.length === this.pageIds.length },
            get someSelected() { return this.selected.length > 0 && !this.allSelected },
            get countLabel() { return this.selected.length + (this.selected.length === 1 ? ' website' : ' websites') },
            toggleAll(on) { this.selected = on ? [...this.pageIds] : [] },
            run(action) { if (!this.selected.length) return; $refs.bulkAction.value = action; $refs.bulkForm.submit() },

            // Filters update the list in place: fetch the filtered page, swap #sites-results, update the URL.
            filtering: false,
            hasFilters: @js($hasFilters),
            filterKeys: @js($filterKeys),
            statusText: '',
            requestId: 0,
            async refresh() {
                const form = $refs.filters;
                const params = new URLSearchParams(new FormData(form));
                for (const [key, value] of [...params]) if (value === '') params.delete(key);
                this.hasFilters = this.filterKeys.some((key) => params.get(key));
                const url = form.getAttribute('action') + (params.toString() ? '?' + params : '');
                const id = ++this.requestId;
                this.filtering = true;
                try {
                    const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' } });
                    const html = await response.text();
                    if (id !== this.requestId) return; // a newer search is on its way
                    const fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('sites-results');
                    if (!response.ok || !fresh) { window.location.assign(url); return; }
                    this.selected = [];
                    this.pageIds = JSON.parse(fresh.dataset.pageIds || '[]');
                    document.getElementById('sites-results').innerHTML = fresh.innerHTML;
                    history.replaceState(null, '', url);
                    this.statusText = fresh.querySelector('[data-results-count]')?.textContent.replace(/\s+/g, ' ').trim()
                        || 'No websites match these filters';
                } catch (e) {
                    window.location.assign(url);
                } finally {
                    if (id === this.requestId) this.filtering = false;
                }
            },
            clearFilters() {
                const form = $refs.filters;
                this.filterKeys.forEach((key) => form.querySelector('[name=' + key + ']').value = '');
                this.refresh();
                form.querySelector('[name=search]').focus();
            },
        }">
    <x-itk.page-header title="Website manager" description="Manage monitored websites and their pages.">
        <x-slot:actions>
            <x-itk.button variant="secondary" :href="route('categories.index')" icon="folder">Manage categories</x-itk.button>
            <x-itk.dropdown-menu id="bulk-actions" label="Bulk actions" icon="list-checks">
                <x-slot:trigger>
                    Bulk actions<span x-show="selected.length" x-cloak class="tabular-nums" x-text="'(' + selected.length + ')'"></span>
                    <span class="sr-only" x-text="selected.length ? countLabel + ' selected' : 'No websites selected'"></span>
                </x-slot:trigger>

                <p x-show="!selected.length" class="px-3 py-2 text-footnote text-ink-muted">Select websites in the table first.</p>
                <p x-show="selected.length" x-cloak class="px-3 py-2 text-footnote text-ink-muted" aria-hidden="true">
                    <span x-text="countLabel"></span> selected
                </p>
                <x-itk.menu-item icon="play" x-bind:aria-disabled="selected.length ? 'false' : 'true'" x-on:click="run('monitor_on')">
                    Turn monitoring on
                </x-itk.menu-item>
                <x-itk.menu-item icon="circle-pause" x-bind:aria-disabled="selected.length ? 'false' : 'true'" x-on:click="run('monitor_off')">
                    Turn monitoring off
                </x-itk.menu-item>
                <x-itk.menu-separator />
                <x-itk.menu-item icon="eye" x-bind:aria-disabled="selected.length ? 'false' : 'true'" x-on:click="run('make_public')">
                    Make public
                </x-itk.menu-item>
                <x-itk.menu-item icon="eye-off" x-bind:aria-disabled="selected.length ? 'false' : 'true'" x-on:click="run('make_private')">
                    Make private
                </x-itk.menu-item>
                <x-itk.menu-separator />
                <x-itk.menu-item icon="x" x-bind:aria-disabled="selected.length ? 'false' : 'true'"
                    x-on:click="if (selected.length) { selected = []; close() }">
                    Clear selection
                </x-itk.menu-item>
                <x-itk.menu-separator />
                <x-itk.menu-item icon="trash-2" destructive x-bind:aria-disabled="selected.length ? 'false' : 'true'"
                    x-on:click="if (selected.length) { close(); $nextTick(() => $refs.bulkDialog.showModal()) }">
                    Delete…
                </x-itk.menu-item>
            </x-itk.dropdown-menu>
            <x-itk.button :href="route('sites.create')" icon="plus">Add website</x-itk.button>
        </x-slot:actions>
    </x-itk.page-header>

    {{-- Search and filters --}}
    <form method="GET" action="{{ route('sites.index') }}" role="search" aria-label="Filter websites"
        x-ref="filters" x-on:submit.prevent="refresh()" class="flex flex-wrap items-center gap-3 mb-4">
        @if ($showAll)
            <input type="hidden" name="show" value="all">
        @endif
        <div class="relative w-full sm:w-64">
            <x-itk.icon name="search" class="pointer-events-none absolute left-3 top-3 text-ink-muted" />
            <x-itk.input type="search" name="search" :value="request('search')" aria-label="Search by website name"
                placeholder="Search by name" class="pl-10 pr-10" x-on:input.debounce.400ms="refresh()" />
            <x-itk.spinner x-bind:style="filtering ? '' : 'display: none'" style="display: none" label="Updating results" class="absolute right-3 top-3.5 text-ink-muted" />
        </div>

        <x-itk.select name="category" aria-label="Filter by category" class="w-full sm:w-auto" x-on:change="refresh()">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </x-itk.select>

        <x-itk.select name="person" aria-label="Filter by responsible person" class="w-full sm:w-auto" x-on:change="refresh()">
            <option value="">All responsible people</option>
            @foreach ($staffMembers as $staff)
                <option value="{{ $staff->id }}" @selected(request('person') == $staff->id)>{{ $staff->name }}</option>
            @endforeach
        </x-itk.select>

        <x-itk.select name="monitoring" aria-label="Filter by monitoring" class="w-full sm:w-auto" x-on:change="refresh()">
            <option value="">Monitoring status</option>
            <option value="monitored" @selected(request('monitoring') === 'monitored')>Monitored</option>
            <option value="not_monitored" @selected(request('monitoring') === 'not_monitored')>Not monitored</option>
        </x-itk.select>

        <x-itk.select name="visibility" aria-label="Filter by public status page" class="w-full sm:w-auto" x-on:change="refresh()">
            <option value="">Visibility status</option>
            <option value="public" @selected(request('visibility') === 'public')>Public</option>
            <option value="private" @selected(request('visibility') === 'private')>Private</option>
        </x-itk.select>

        {{-- Filters apply as you type or choose; the button is only needed without JavaScript --}}
        <noscript><x-itk.button type="submit" variant="secondary" icon="list-filter">Apply filters</x-itk.button></noscript>

        {{-- Shown with a style binding, not x-show: x-show shows a frame late, so a quick show-then-hide can stick --}}
        <x-itk.button variant="ghost" icon="x" x-on:click="clearFilters()"
            x-bind:style="hasFilters ? '' : 'display: none'" :style="$hasFilters ? null : 'display: none'">Clear filters</x-itk.button>
    </form>

    {{-- Announces the new result count after a filter change --}}
    <p class="sr-only" aria-live="polite" x-text="statusText"></p>

        @if ($errors->has('ids') || $errors->has('ids.*') || $errors->has('action'))
            <x-itk.alert tone="danger" class="mb-4">
                {{ $errors->first('ids') ?: ($errors->first('ids.*') ?: $errors->first('action')) }}
            </x-itk.alert>
        @endif

        <form x-ref="bulkForm" method="POST" action="{{ route('sites.bulk') }}" class="hidden">
            @csrf
            <input type="hidden" name="action" x-ref="bulkAction">
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
        </form>

        <div id="sites-results" data-page-ids="{{ json_encode($sites->pluck('id')) }}" x-bind:aria-busy="filtering.toString()">
        @if ($sites->isEmpty())
            <x-itk.card>
                @if ($hasFilters)
                    <x-itk.empty title="No websites match these filters" icon="search">
                        <x-slot:action>
                            <x-itk.button variant="secondary" x-on:click="clearFilters()">Clear filters</x-itk.button>
                        </x-slot:action>
                        Try another name, category, responsible person, monitoring or public setting.
                    </x-itk.empty>
                @else
                    <x-itk.empty title="No websites yet" icon="globe">
                        <x-slot:action>
                            <x-itk.button :href="route('sites.create')" icon="plus">Add website</x-itk.button>
                        </x-slot:action>
                        Add the first website to start monitoring it.
                    </x-itk.empty>
                @endif
            </x-itk.card>
        @else
            <x-itk.table caption="Websites" hide-caption caption-id="websites-caption" class="shadow-1">
                <thead>
                    <tr>
                        <th scope="col" class="w-14">
                            <label class="inline-grid place-items-center size-11 -my-3 cursor-pointer">
                                <input type="checkbox" aria-label="{{ $showAll ? 'Select all websites' : 'Select all websites on this page' }}"
                                    class="size-5 accent-primary cursor-pointer"
                                    x-bind:checked="allSelected" x-effect="$el.indeterminate = someSelected"
                                    x-on:change="toggleAll($event.target.checked)">
                            </label>
                        </th>
                        <th scope="col">Name</th>
                        <th scope="col">Category</th>
                        <th scope="col">Base URL</th>
                        <th scope="col" class="itk-num">Pages</th>
                        <th scope="col">Responsible person</th>
                        <th scope="col" class="itk-num"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sites as $site)
                        <tr x-bind:class="selected.includes({{ $site->id }}) && 'bg-primary-soft'">
                            <td>
                                <label class="inline-grid place-items-center size-11 -my-3 cursor-pointer">
                                    <input type="checkbox" value="{{ $site->id }}" x-model.number="selected"
                                        aria-label="Select {{ $site->name }}" class="size-5 accent-primary cursor-pointer">
                                </label>
                            </td>
                            <th scope="row">
                                <span class="flex flex-wrap items-center gap-2">
                                    {{ $site->name }}
                                    @unless ($site->is_monitored)
                                        <x-itk.badge tone="neutral" icon="circle-pause">Not monitored</x-itk.badge>
                                    @endunless
                                    @if ($site->is_public)
                                        <x-itk.badge tone="info" icon="eye">Public</x-itk.badge>
                                    @endif
                                </span>
                            </th>
                            <td>{{ $site->category->name }}</td>
                            <td>
                                <a href="{{ $site->base_url }}" target="_blank" rel="noopener noreferrer"
                                    class="text-link hover:underline underline-offset-3">
                                    {{ $site->base_url }}<span class="sr-only"> (opens in a new tab)</span>
                                </a>
                            </td>
                            <td class="itk-num">
                                <x-itk.tooltip :id="'site-pages-' . $site->id" :content="$site->pages->pluck('path')->join(', ')">
                                    <button type="button" aria-describedby="site-pages-{{ $site->id }}"
                                        class="min-h-9 min-w-9 px-2 rounded-xs underline decoration-dotted underline-offset-3 cursor-help">
                                        {{ $site->pages_count }}<span class="sr-only"> pages</span>
                                    </button>
                                </x-itk.tooltip>
                            </td>
                            <td>{{ $site->responsiblePerson->name }}</td>
                            <td class="itk-num">
                                <div class="inline-flex gap-2">
                                    <x-itk.button variant="ghost" size="sm" icon="pencil" :href="route('sites.edit', $site)"
                                        :aria-label="'Edit ' . $site->name">Edit</x-itk.button>
                                    <x-itk.button variant="ghost" size="sm" icon="trash-2" class="text-error hover:bg-error-soft"
                                        :aria-label="'Delete ' . $site->name"
                                        x-on:click="confirmDelete({{ Js::from($site->name) }}, {{ Js::from(route('sites.destroy', $site)) }})">
                                        Delete
                                    </x-itk.button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-itk.table>
        @endif

        @if ($sites->total() > 0)
            @php $total = $sites->total(); $noun = \Illuminate\Support\Str::plural('website', $total); @endphp
            <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
                <p class="text-footnote text-ink-muted tabular-nums" data-results-count>
                    @if ($showAll || ! $sites->hasPages())
                        Showing all {{ $total }} {{ $noun }}
                    @else
                        Showing {{ $sites->firstItem() }}–{{ $sites->lastItem() }} of {{ $total }} {{ $noun }}
                    @endif
                </p>
                @if ($showAll && $total > 10)
                    <x-itk.button variant="ghost" icon="list" :href="route('sites.index', request()->except(['show', 'page']))">
                        Show 10 per page
                    </x-itk.button>
                @elseif (! $showAll && $sites->hasPages())
                    <x-itk.button variant="ghost" icon="list" :href="route('sites.index', array_merge(request()->except('page'), ['show' => 'all']))">
                        Show all {{ $total }}
                    </x-itk.button>
                @endif
            </div>
        @endif

        @if ($sites->hasPages())
            <div class="mt-4">
                {{ $sites->links('vendor.pagination.daisy') }}
            </div>
        @endif
        </div>

        <x-itk.alert-dialog ref="bulkDialog" action="{{ Js::from(route('sites.bulk')) }}" method="POST"
            confirm-label="Delete websites" id="bulk-delete-sites">
            <x-slot:heading>Delete <span x-text="countLabel"></span>?</x-slot:heading>
            <x-slot:fields>
                <input type="hidden" name="action" value="delete">
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
            </x-slot:fields>
            Their pages and check history are removed, and they are left out of future checking cycles. This cannot be undone.
        </x-itk.alert-dialog>

        <x-itk.alert-dialog action="target.url" confirm-label="Delete website" id="delete-site">
            <x-slot:heading>Delete <span x-text="target.name"></span>?</x-slot:heading>
            It is left out of future checking cycles. This cannot be undone.
        </x-itk.alert-dialog>
    </div>
@endsection
