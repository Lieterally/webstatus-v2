@extends('layouts.app')

@section('title', 'Edit Telegram target')

@section('content')
    <x-itk.back-link :href="route('telegram-targets.index')">Telegram manager</x-itk.back-link>
    <x-itk.page-header title="Edit Telegram target" class="mt-1" />

    <x-itk.card class="max-w-lg">
        <form method="POST" action="{{ route('telegram-targets.update', $telegramTarget) }}" class="grid gap-5">
            @csrf
            @method('PUT')

            @include('telegram-targets._form', ['telegramTarget' => $telegramTarget])

            <div class="flex flex-wrap items-center justify-end gap-3 pt-5 border-t border-line">
                <x-itk.button variant="secondary" :href="route('telegram-targets.index')">Cancel</x-itk.button>
                <x-itk.button type="submit">Save changes</x-itk.button>
            </div>
        </form>
    </x-itk.card>
@endsection
