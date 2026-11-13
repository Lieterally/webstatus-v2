@extends('layouts.app')

@section('title', 'Website manager')

@section('content')
    <x-itk.page-header title="Website manager" description="Manage monitored websites and their pages.">
        <x-slot:actions>
            <x-itk.button variant="secondary" :href="route('categories.index')" icon="folder">Manage categories</x-itk.button>
            <x-itk.button :href="route('sites.create')" icon="plus">Add website</x-itk.button>
        </x-slot:actions>
    </x-itk.page-header>

    {{-- Search and filters --}}
    <form method="GET" action="{{ route('sites.index') }}" role="search" aria-label="Filter websites"
        class="flex flex-wrap items-center gap-3 mb-4">
        <div class="relative w-full sm:w-64">
            <x-itk.icon name="search" class="pointer-events-none absolute left-3 top-3 text-ink-muted" />
            <x-itk.input type="search" name="search" :value="request('search')" aria-label="Search by website name"
                placeholder="Search by name" class="pl-10" />
        </div>

        <x-itk.select name="category" aria-label="Filter by category" class="w-full sm:w-auto">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </x-itk.select>

        <x-itk.select name="person" aria-label="Filter by responsible person" class="w-full sm:w-auto">
            <option value="">All responsible people</option>
            @foreach ($staffMembers as $staff)
                <option value="{{ $staff->id }}" @selected(request('person') == $staff->id)>{{ $staff->name }}</option>
            @endforeach
        </x-itk.select>

        <x-itk.button type="submit" variant="secondary" icon="list-filter">Apply filters</x-itk.button>

        @if (request('search') || request('category') || request('person'))
            <x-itk.button variant="ghost" :href="route('sites.index')" icon="x">Clear filters</x-itk.button>
        @endif
    </form>

    <div x-data="{ target: { name: '', url: '' }, confirmDelete(name, url) { this.target = { name, url }; $refs.confirmDialog.showModal(); } }">
        @if ($sites->isEmpty())
            <x-itk.card>
                @if (request('search') || request('category') || request('person'))
                    <x-itk.empty title="No websites match these filters" icon="search">
                        <x-slot:action>
                            <x-itk.button variant="secondary" :href="route('sites.index')">Clear filters</x-itk.button>
                        </x-slot:action>
                        Try another name, category or responsible person.
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
                        <tr>
                            <th scope="row">
                                <span class="flex flex-wrap items-center gap-2">
                                    {{ $site->name }}
                                    @unless ($site->is_monitored)
                                        <x-itk.badge tone="neutral" icon="circle-pause">Not monitored</x-itk.badge>
                                    @endunless
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

        @if ($sites->hasPages())
            <div class="mt-4">
                {{ $sites->links('vendor.pagination.daisy') }}
            </div>
        @endif

        <x-itk.alert-dialog action="target.url" confirm-label="Delete website" id="delete-site">
            <x-slot:heading>Delete <span x-text="target.name"></span>?</x-slot:heading>
            It is left out of future checking cycles. This cannot be undone.
        </x-itk.alert-dialog>
    </div>
@endsection
