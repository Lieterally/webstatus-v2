@extends('layouts.app')

@section('title', 'Add website')

@section('content')
    <x-itk.back-link :href="route('sites.index')">Website manager</x-itk.back-link>
    <x-itk.page-header title="Add website" description="Add a website and the pages to check." class="mt-1" />

    <x-itk.card class="max-w-2xl">
        <form method="POST" action="{{ route('sites.store') }}" class="grid gap-5">
            @csrf

            @include('sites._form')

            <div class="flex flex-wrap items-center justify-end gap-3 pt-5 border-t border-line">
                <x-itk.button variant="secondary" :href="route('sites.index')">Cancel</x-itk.button>
                <x-itk.button type="submit">Add website</x-itk.button>
            </div>
        </form>
    </x-itk.card>
@endsection
