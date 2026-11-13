{{--
    Dashboard tile (ITK Widget): title, icon, value and a caption slot.
    tone colours the value and icon (neutral | info | success | warning | danger); the title and icon
    always name what the number means, so tone is never the only signal.
--}}
@props(['title', 'value' => null, 'icon' => null, 'tone' => 'neutral'])

@php
    $toneClass = [
        'neutral' => 'text-ink',
        'info' => 'text-link',
        'success' => 'text-success',
        'warning' => 'text-warning',
        'danger' => 'text-error',
    ][$tone] ?? 'text-ink';
    $iconClass = $tone === 'neutral' ? 'text-ink-muted' : $toneClass;
@endphp

<div {{ $attributes->class('flex flex-col gap-1 p-4 bg-surface border border-line rounded-md shadow-1 min-w-0') }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-callout text-ink-muted">{{ $title }}</p>
        @if ($icon)
            <x-itk.icon :name="$icon" :class="$iconClass" />
        @endif
    </div>
    @if (! is_null($value))
        <p class="text-title-1 tabular-nums {{ $toneClass }}">{{ $value }}</p>
    @endif
    @if ($slot->hasActualContent())
        <div class="text-footnote text-ink-muted">{{ $slot }}</div>
    @endif
</div>
