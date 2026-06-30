@extends('layouts.app')

@section('title', 'Telegram Manager')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Telegram Manager</h1>
            <p class="text-sm text-base-content/60 mt-1">Manage Telegram notification targets</p>
        </div>
        <a href="{{ route('telegram-targets.create') }}" class="btn btn-primary btn-sm">
            Add Target
        </a>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Chat ID</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($targets as $target)
                            <tr>
                                <td class="font-mono">{{ $target->chat_id }}</td>
                                <td>
                                    @if ($target->username)
                                        <span class="text-base-content">@ {{ $target->username }}</span>
                                    @else
                                        <span class="text-base-content/40">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($target->is_active)
                                        <span class="badge badge-success badge-sm">Active</span>
                                    @else
                                        <span class="badge badge-ghost badge-sm">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-right space-x-2">
                                    <a href="{{ route('telegram-targets.edit', $target) }}"
                                        class="btn btn-ghost btn-xs">Edit</a>
                                    <div class="inline" x-data="{ showDeleteModal: false }">
                                        <button @click="showDeleteModal = true" type="button"
                                            class="btn btn-ghost btn-xs text-error">Delete</button>

                                        {{-- Confirmation Modal --}}
                                        <div x-show="showDeleteModal" x-cloak
                                            class="fixed inset-0 z-50 flex items-center justify-center"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                            {{-- Backdrop --}}
                                            <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>

                                            {{-- Modal Content --}}
                                            <div class="relative bg-base-100 rounded-lg shadow-xl p-6 w-full max-w-sm mx-4 z-10"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                @click.outside="showDeleteModal = false">
                                                <div class="flex items-center gap-3 mb-4">
                                                    <div
                                                        class="flex-shrink-0 w-10 h-10 rounded-full bg-error/10 flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-error" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h3 class="text-lg font-semibold text-base-content">Delete Telegram
                                                            Target</h3>
                                                        <p class="text-sm text-base-content/60">This action cannot be
                                                            undone.</p>
                                                    </div>
                                                </div>

                                                <p class="text-sm text-base-content/70 mb-6">
                                                    Are you sure you want to delete the Telegram target with Chat ID <strong
                                                        class="font-mono">{{ $target->chat_id }}</strong>?
                                                </p>

                                                <div class="flex items-center justify-end gap-3">
                                                    <button @click="showDeleteModal = false" type="button"
                                                        class="btn btn-ghost btn-sm">
                                                        Cancel
                                                    </button>
                                                    <form action="{{ route('telegram-targets.destroy', $target) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-error btn-sm">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-base-content/60">No Telegram targets found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
