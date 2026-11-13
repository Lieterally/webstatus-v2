@extends('layouts.app')

@section('title', 'Edit user')

@section('content')
    <x-itk.back-link :href="route('users.index')">User manager</x-itk.back-link>
    <x-itk.page-header :title="'Edit ' . $user->username" class="mt-1" />

    <x-itk.card class="max-w-lg">
        <form method="POST" action="{{ route('users.update', $user) }}" class="grid gap-5">
            @csrf
            @method('PUT')

            <x-itk.field name="username" label="Username" hint="3 to 50 characters." required>
                <x-itk.input :value="old('username', $user->username)" minlength="3" maxlength="50" autocomplete="off"
                    autocapitalize="none" spellcheck="false" autofocus />
            </x-itk.field>

            <x-itk.field name="password" label="New password (optional)"
                hint="Leave blank to keep the current password. 8 to 128 characters.">
                <x-itk.input type="password" minlength="8" maxlength="128" autocomplete="new-password" />
            </x-itk.field>

            <x-itk.field name="role" label="Role"
                :hint="$user->role === 'super_admin' && $isLastSuperAdmin ? 'This is the last super admin, so the role cannot change to Admin.' : null"
                required>
                <x-itk.select>
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                    <option value="super_admin" @selected(old('role', $user->role) === 'super_admin')>Super admin</option>
                </x-itk.select>
            </x-itk.field>

            <div class="flex flex-wrap items-center justify-end gap-3 pt-5 border-t border-line">
                <x-itk.button variant="secondary" :href="route('users.index')">Cancel</x-itk.button>
                <x-itk.button type="submit">Save changes</x-itk.button>
            </div>
        </form>
    </x-itk.card>
@endsection
