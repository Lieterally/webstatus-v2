@extends('layouts.app')

@section('title', 'Add user')

@section('content')
    <x-itk.back-link :href="route('users.index')">User manager</x-itk.back-link>
    <x-itk.page-header title="Add user" class="mt-1" />

    <x-itk.card class="max-w-lg">
        <form method="POST" action="{{ route('users.store') }}" class="grid gap-5">
            @csrf

            <x-itk.field name="username" label="Username" hint="3 to 50 characters." required>
                <x-itk.input :value="old('username')" minlength="3" maxlength="50" autocomplete="off"
                    autocapitalize="none" spellcheck="false" autofocus />
            </x-itk.field>

            <x-itk.field name="password" label="Password" hint="8 to 128 characters." required>
                <x-itk.input type="password" minlength="8" maxlength="128" autocomplete="new-password" />
            </x-itk.field>

            <x-itk.field name="role" label="Role" required>
                <x-itk.select>
                    <option value="">Select a role</option>
                    <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                    <option value="super_admin" @selected(old('role') === 'super_admin')>Super admin</option>
                </x-itk.select>
            </x-itk.field>

            <div class="flex flex-wrap items-center justify-end gap-3 pt-5 border-t border-line">
                <x-itk.button variant="secondary" :href="route('users.index')">Cancel</x-itk.button>
                <x-itk.button type="submit">Add user</x-itk.button>
            </div>
        </form>
    </x-itk.card>
@endsection
