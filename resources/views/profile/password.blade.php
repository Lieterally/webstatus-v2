@extends('layouts.app')

@section('title', 'Change password')

@section('content')
    <x-itk.page-header title="Change password" description="Use a password you don't use anywhere else." />

    {{-- Success after saving shows as a toast from the layout --}}

    <x-itk.card class="max-w-lg">
        <form method="POST" action="{{ route('profile.password.update') }}" class="grid gap-5">
            @csrf
            @method('PUT')

            <x-itk.field name="current_password" label="Current password" required>
                <x-itk.input type="password" autocomplete="current-password" autofocus />
            </x-itk.field>

            <x-itk.field name="new_password" label="New password" hint="8 to 128 characters." required>
                <x-itk.input type="password" minlength="8" maxlength="128" autocomplete="new-password" />
            </x-itk.field>

            <x-itk.field name="new_password_confirmation" label="Confirm new password" required>
                <x-itk.input type="password" minlength="8" maxlength="128" autocomplete="new-password" />
            </x-itk.field>

            <div class="flex flex-wrap items-center justify-end gap-3 pt-5 border-t border-line">
                <x-itk.button variant="secondary" :href="route('dashboard')">Cancel</x-itk.button>
                <x-itk.button type="submit">Change password</x-itk.button>
            </div>
        </form>
    </x-itk.card>
@endsection
