@extends('layouts.app')

@section('title', 'Edit category')

@section('content')
    <x-itk.back-link :href="route('categories.index')">Categories</x-itk.back-link>
    <x-itk.page-header :title="'Edit ' . $category->name" class="mt-1" />

    <x-itk.card class="max-w-lg">
        <form method="POST" action="{{ route('categories.update', $category) }}" class="grid gap-5">
            @csrf
            @method('PUT')

            <x-itk.field name="name" label="Name" required>
                <x-itk.input :value="old('name', $category->name)" autofocus />
            </x-itk.field>

            <div class="flex flex-wrap items-center justify-end gap-3 pt-5 border-t border-line">
                <x-itk.button variant="secondary" :href="route('categories.index')">Cancel</x-itk.button>
                <x-itk.button type="submit">Save changes</x-itk.button>
            </div>
        </form>
    </x-itk.card>
@endsection
