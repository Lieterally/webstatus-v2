@extends('layouts.app')

@section('title', 'Categories')

@section('content')
    <x-itk.back-link :href="route('sites.index')">Website manager</x-itk.back-link>
    <x-itk.page-header title="Categories" description="Group websites by category." class="mt-1">
        <x-slot:actions>
            <x-itk.button :href="route('categories.create')" icon="plus">Add category</x-itk.button>
        </x-slot:actions>
    </x-itk.page-header>

    <div x-data="{ target: { name: '', url: '' }, confirmDelete(name, url) { this.target = { name, url }; $refs.confirmDialog.showModal(); } }">
        @if ($categories->isEmpty())
            <x-itk.card>
                <x-itk.empty title="No categories yet" icon="folder">
                    <x-slot:action>
                        <x-itk.button :href="route('categories.create')" icon="plus">Add category</x-itk.button>
                    </x-slot:action>
                    Add a category, then assign websites to it.
                </x-itk.empty>
            </x-itk.card>
        @else
            <x-itk.table caption="Categories" hide-caption caption-id="categories-caption" class="shadow-1">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col" class="itk-num">Websites</th>
                        <th scope="col" class="itk-num"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <th scope="row">{{ $category->name }}</th>
                            <td class="itk-num">{{ $category->sites_count }}</td>
                            <td class="itk-num">
                                <div class="inline-flex gap-2">
                                    <x-itk.button variant="ghost" size="sm" icon="pencil" :href="route('categories.edit', $category)"
                                        :aria-label="'Edit ' . $category->name">Edit</x-itk.button>
                                    <x-itk.button variant="ghost" size="sm" icon="trash-2" class="text-error hover:bg-error-soft"
                                        :aria-label="'Delete ' . $category->name"
                                        x-on:click="confirmDelete({{ Js::from($category->name) }}, {{ Js::from(route('categories.destroy', $category)) }})">
                                        Delete
                                    </x-itk.button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-itk.table>
        @endif

        <x-itk.alert-dialog action="target.url" confirm-label="Delete category" id="delete-category">
            <x-slot:heading>Delete <span x-text="target.name"></span>?</x-slot:heading>
            The category is removed. This cannot be undone.
        </x-itk.alert-dialog>
    </div>
@endsection
