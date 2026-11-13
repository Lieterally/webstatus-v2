@extends('layouts.app')

@section('title', 'IT staff manager')

@section('content')
    <x-itk.page-header title="IT staff manager" description="Manage the IT staff responsible for websites.">
        <x-slot:actions>
            <x-itk.button :href="route('it-staff.create')" icon="plus">Add IT staff</x-itk.button>
        </x-slot:actions>
    </x-itk.page-header>

    <div x-data="{ target: { name: '', url: '' }, confirmDelete(name, url) { this.target = { name, url }; $refs.confirmDialog.showModal(); } }">
        @if ($staff->isEmpty())
            <x-itk.card>
                <x-itk.empty title="No IT staff yet" icon="id-card">
                    <x-slot:action>
                        <x-itk.button :href="route('it-staff.create')" icon="plus">Add IT staff</x-itk.button>
                    </x-slot:action>
                    Add the people who look after websites, then assign them as responsible person.
                </x-itk.empty>
            </x-itk.card>
        @else
            <x-itk.table caption="IT staff" hide-caption caption-id="it-staff-caption" class="shadow-1">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Position</th>
                        <th scope="col" class="itk-num"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staff as $member)
                        <tr>
                            <th scope="row">{{ $member->name }}</th>
                            <td class="text-ink-muted">{{ $member->position }}</td>
                            <td class="itk-num">
                                <div class="inline-flex gap-2">
                                    <x-itk.button variant="ghost" size="sm" icon="pencil" :href="route('it-staff.edit', $member)"
                                        :aria-label="'Edit ' . $member->name">Edit</x-itk.button>
                                    <x-itk.button variant="ghost" size="sm" icon="trash-2" class="text-error hover:bg-error-soft"
                                        :aria-label="'Delete ' . $member->name"
                                        x-on:click="confirmDelete({{ Js::from($member->name) }}, {{ Js::from(route('it-staff.destroy', $member)) }})">
                                        Delete
                                    </x-itk.button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-itk.table>
        @endif

        <x-itk.alert-dialog action="target.url" confirm-label="Delete IT staff" id="delete-it-staff">
            <x-slot:heading>Delete <span x-text="target.name"></span>?</x-slot:heading>
            They are removed from the IT staff list. This cannot be undone.
        </x-itk.alert-dialog>
    </div>
@endsection
