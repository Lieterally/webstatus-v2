{{-- Sidebar navigation link (ITK Sidebar). The current page gets accent-soft, bold text, a gold edge mark and aria-current. --}}
@props(['href', 'icon', 'current' => false])

<li>
    <a href="{{ $href }}" @if ($current) aria-current="page" @endif
        {{ $attributes->class([
            'relative flex items-center gap-3 min-h-11 px-3 rounded-sm text-callout no-underline',
            'bg-primary-soft text-primary-soft-content font-semibold before:absolute before:-left-3 before:top-2.5 before:bottom-2.5 before:w-0.75 before:rounded-r-xs before:bg-gold' => $current,
            'text-ink hover:bg-surface-sunken' => ! $current,
        ]) }}>
        <x-itk.icon :name="$icon" :class="$current ? 'text-primary-soft-content' : 'text-ink-muted'" />
        <span>{{ $slot }}</span>
    </a>
</li>
