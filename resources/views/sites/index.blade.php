@extends('layouts.app')

@section('title', 'Website Manager')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Website Manager</h1>
            <p class="text-sm text-base-content/60 mt-1">Manage monitored websites and their pages</p>
        </div>
        <a href="{{ route('sites.create') }}" class="btn btn-primary">
            Add Site
        </a>
    </div>

    {{-- Search & Filters --}}
    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('sites.index') }}" class="flex flex-wrap items-center gap-3">
                {{-- Search --}}
                <label class="input input-sm input-bordered flex items-center gap-2 w-auto sm:w-64">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search site name..."
                        class="grow">
                </label>

                {{-- Category Filter --}}
                <select name="category" class="select select-sm select-bordered w-auto sm:w-auto">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                {{-- Responsible Person Filter --}}
                <select name="person" class="select select-sm select-bordered w-auto sm:w-auto">
                    <option value="">All Persons</option>
                    @foreach ($staffMembers as $staff)
                        <option value="{{ $staff->id }}" {{ request('person') == $staff->id ? 'selected' : '' }}>
                            {{ $staff->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-sm btn-primary">Filter</button>

                @if (request('search') || request('category') || request('person'))
                    <a href="{{ route('sites.index') }}" class="btn btn-sm btn-ghost">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm" x-data="deleteModal()">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Base URL</th>
                            <th>Pages</th>
                            <th>Responsible Person</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sites as $site)
                            <tr class="hover">
                                <td class="font-medium">{{ $site->name }}</td>
                                <td>{{ $site->category->name }}</td>
                                <td>
                                    <a href="{{ $site->base_url }}" target="_blank"
                                        class="link link-primary">{{ $site->base_url }}</a>
                                </td>
                                <td>
                                    <div class="tooltip tooltip-right"
                                        data-tip="{{ $site->pages->pluck('path')->join(', ') }}">
                                        <span
                                            class="cursor-help underline decoration-dotted">{{ $site->pages_count }}</span>
                                    </div>
                                </td>
                                <td>{{ $site->responsiblePerson->name }}</td>
                                <td class="text-right space-x-2">
                                    <a href="{{ route('sites.edit', $site) }}" class="btn btn-ghost btn-sm">Edit</a>
                                    <button type="button"
                                        @click="openModal('{{ $site->name }}', '{{ route('sites.destroy', $site) }}')"
                                        class="btn btn-error btn-sm btn-ghost">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-base-content/60">No sites found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="row p-4">

                    {{ $sites->links('vendor.pagination.daisy') }}
                </div>

            </div>
        </div>

        {{-- Delete Confirmation Modal --}}
        <div x-show="showModal" x-cloak class="modal modal-open" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            {{-- Backdrop --}}
            <div class="modal-backdrop" @click="closeModal()"></div>

            {{-- Modal Content --}}
            <div class="modal-box" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95" @keydown.escape.window="closeModal()">
                <div class="flex items-start gap-4">
                    {{-- Warning Icon --}}
                    <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-error/10">
                        <svg class="w-5 h-5 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>

                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-base-content">Delete Site</h3>
                        <p class="mt-2 text-sm text-base-content/70">
                            Are you sure you want to delete <span class="font-medium text-base-content"
                                x-text="siteName"></span>? This action cannot be undone. The site will be excluded from
                            future checking cycles.
                        </p>
                    </div>
                </div>

                <div class="modal-action">
                    <button type="button" @click="closeModal()" class="btn btn-ghost">
                        Cancel
                    </button>
                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-error">
                            Delete Site
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>



    @push('scripts')
        <script>
            function deleteModal() {
                return {
                    showModal: false,
                    siteName: '',
                    deleteUrl: '',
                    openModal(name, url) {
                        this.siteName = name;
                        this.deleteUrl = url;
                        this.showModal = true;
                    },
                    closeModal() {
                        this.showModal = false;
                        this.siteName = '';
                        this.deleteUrl = '';
                    }
                };
            }
        </script>
    @endpush
@endsection
