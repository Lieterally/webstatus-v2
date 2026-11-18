{{-- Page header (ITK Header, large title): h1, optional description, actions on the right. --}}
@props(['title', 'description' => null])

<div {{ $attributes->class('mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4') }}>
    <div class="min-w-0">
        <h1 class="text-title-1 text-ink">{{ $title }}</h1>
        @if ($description)
            <p class="text-body text-ink-muted mt-1">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
