@extends('layouts.app')

@section('title', 'Edit IT staff')

@section('content')
    <x-itk.back-link :href="route('it-staff.index')">IT staff manager</x-itk.back-link>
    <x-itk.page-header :title="'Edit ' . $itStaff->name" class="mt-1" />

    <x-itk.card class="max-w-lg">
        <form method="POST" action="{{ route('it-staff.update', $itStaff) }}" class="grid gap-5">
            @csrf
            @method('PUT')

            <x-itk.field name="name" label="Name" required>
                <x-itk.input :value="old('name', $itStaff->name)" maxlength="100" autocomplete="off" autofocus />
            </x-itk.field>

            <x-itk.field name="position" label="Position" required>
                <x-itk.input :value="old('position', $itStaff->position)" maxlength="100" autocomplete="off" />
            </x-itk.field>

            <div class="flex flex-wrap items-center justify-end gap-3 pt-5 border-t border-line">
                <x-itk.button variant="secondary" :href="route('it-staff.index')">Cancel</x-itk.button>
                <x-itk.button type="submit">Save changes</x-itk.button>
            </div>
        </form>
    </x-itk.card>
@endsection
