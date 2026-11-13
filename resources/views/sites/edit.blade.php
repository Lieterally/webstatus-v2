@extends('layouts.app')

@section('title', 'Edit website')

@section('content')
    <x-itk.back-link :href="route('sites.index')">Website manager</x-itk.back-link>
    <x-itk.page-header :title="'Edit ' . $site->name" class="mt-1" />

    <x-itk.card class="max-w-2xl">
        <form method="POST" action="{{ route('sites.update', $site) }}" class="grid gap-5">
            @csrf
            @method('PUT')

            @include('sites._form', ['site' => $site])

            <div class="flex flex-wrap items-center justify-end gap-3 pt-5 border-t border-line">
                <x-itk.button variant="secondary" :href="route('sites.index')">Cancel</x-itk.button>
                <x-itk.button type="submit">Save changes</x-itk.button>
            </div>
        </form>
    </x-itk.card>
@endsection
