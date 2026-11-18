@extends('layouts.app')

@section('title', 'User manager')

@section('content')
    <x-itk.page-header title="User manager" description="Manage who can sign in and what they can do.">
        <x-slot:actions>
            <x-itk.button :href="route('users.create')" icon="plus">Add user</x-itk.button>
        </x-slot:actions>
    </x-itk.page-header>

    <div x-data="{ target: { name: '', url: '' }, confirmDelete(name, url) { this.target = { name, url }; $refs.confirmDialog.showModal(); } }">
        @if ($users->isEmpty())
            <x-itk.card>
                <x-itk.empty title="No users yet" icon="users">
                    <x-slot:action>
                        <x-itk.button :href="route('users.create')" icon="plus">Add user</x-itk.button>
                    </x-slot:action>
                    Add a user so they can sign in.
                </x-itk.empty>
            </x-itk.card>
        @else
            <x-itk.table caption="Users" hide-caption caption-id="users-caption" class="shadow-1">
                <thead>
                    <tr>
                        <th scope="col">Username</th>
                        <th scope="col">Role</th>
                        <th scope="col" class="itk-num"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        @php
                            $isOwnAccount = $user->id === auth()->id();
                            $isLastSuperAdmin = $user->role === 'super_admin' && $superAdminCount <= 1;
                            $canDelete = ! $isOwnAccount && ! $isLastSuperAdmin;
                        @endphp
                        <tr>
                            <th scope="row">
                                <span class="flex flex-wrap items-center gap-2">
                                    {{ $user->username }}
                                    @if ($isOwnAccount)
                                        <x-itk.badge tone="neutral">You</x-itk.badge>
                                    @endif
                                </span>
                            </th>
                            <td>
                                @if ($user->role === 'super_admin')
                                    <x-itk.badge tone="info" icon="shield">Super admin</x-itk.badge>
                                @else
                                    <x-itk.badge tone="neutral" icon="user">Admin</x-itk.badge>
                                @endif
                            </td>
                            <td class="itk-num">
                                <div class="inline-flex gap-2">
                                    <x-itk.button variant="ghost" size="sm" icon="pencil" :href="route('users.edit', $user)"
                                        :aria-label="'Edit ' . $user->username">Edit</x-itk.button>
                                    @if ($canDelete)
                                        <x-itk.button variant="ghost" size="sm" icon="trash-2" class="text-error hover:bg-error-soft"
                                            :aria-label="'Delete ' . $user->username"
                                            x-on:click="confirmDelete({{ Js::from($user->username) }}, {{ Js::from(route('users.destroy', $user)) }})">
                                            Delete
                                        </x-itk.button>
                                    @else
                                        <x-itk.tooltip :id="'cannot-delete-' . $user->id"
                                            :content="$isOwnAccount ? 'You cannot delete your own account.' : 'You cannot delete the last super admin.'">
                                            <x-itk.button variant="ghost" size="sm" icon="trash-2" aria-disabled="true"
                                                aria-describedby="cannot-delete-{{ $user->id }}">Delete</x-itk.button>
                                        </x-itk.tooltip>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-itk.table>
        @endif

        <x-itk.alert-dialog action="target.url" confirm-label="Delete user" id="delete-user">
            <x-slot:heading>Delete <span x-text="target.name"></span>?</x-slot:heading>
            They can no longer sign in. This cannot be undone.
        </x-itk.alert-dialog>
    </div>
@endsection
