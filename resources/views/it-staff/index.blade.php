@extends('layouts.app')

@section('title', 'IT Staff Manager')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">IT Staff Manager</h1>
            <p class="text-sm text-base-content/60 mt-1">Manage IT staff responsible for websites</p>
        </div>
        <a href="{{ route('it-staff.create') }}" class="btn btn-primary btn-sm">
            Add IT Staff
        </a>
    </div>

    <div class="card bg-base-100 shadow-sm" x-data="deleteModal()">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staff as $member)
                            <tr>
                                <td class="font-medium">{{ $member->name }}</td>
                                <td class="text-base-content/70">{{ $member->position }}</td>
                                <td class="text-right space-x-2">
                                    <a href="{{ route('it-staff.edit', $member) }}" class="btn btn-ghost btn-xs">Edit</a>
                                    <button type="button"
                                        @click="openModal('{{ $member->name }}', '{{ route('it-staff.destroy', $member) }}')"
                                        class="btn btn-ghost btn-xs text-error">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-base-content/60">No IT staff members found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
                        <h3 class="text-lg font-semibold text-base-content">Delete IT Staff</h3>
                        <p class="mt-2 text-sm text-base-content/70">
                            Are you sure you want to delete <span class="font-medium text-base-content"
                                x-text="itemName"></span>? This action cannot be undone.
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
                            Delete Staff
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
                    itemName: '',
                    deleteUrl: '',
                    openModal(name, url) {
                        this.itemName = name;
                        this.deleteUrl = url;
                        this.showModal = true;
                    },
                    closeModal() {
                        this.showModal = false;
                        this.itemName = '';
                        this.deleteUrl = '';
                    }
                };
            }
        </script>
    @endpush
@endsection
