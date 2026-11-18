{{-- Empty state (ITK Empty): icon, title, one sentence, optional action slot. --}}
@props(['title', 'icon' => 'inbox', 'titleAs' => 'h3'])

<div {{ $attributes->class('grid justify-items-center text-center gap-2 py-10 px-4 max-w-105 mx-auto') }}>
    <div class="grid place-items-center size-14 mb-2 rounded-full bg-primary-soft text-link">
        <x-itk.icon :name="$icon" size="24" />
    </div>
    <{{ $titleAs }} class="text-title-3 text-ink">{{ $title }}</{{ $titleAs }}>
    @if ($slot->hasActualContent())
        <p class="text-body text-ink-muted">{{ $slot }}</p>
    @endif
    @isset($action)
        <div class="mt-3 flex flex-wrap justify-center gap-2">{{ $action }}</div>
    @endisset
</div>
