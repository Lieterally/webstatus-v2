{{--
    Badge (ITK Badge): one or two uppercase words. tone: neutral | info | success | warning | danger | brand.
    icon: a Lucide name, or true for the tone's default glyph. Use an icon wherever tones sit side by side.
--}}
@props(['tone' => 'neutral', 'icon' => null])

@php
    $tones = [
        'neutral' => ['bg-surface-sunken text-ink-muted border-line', 'circle-minus'],
        'info' => ['bg-primary-soft text-primary-soft-content border-transparent', 'info'],
        'success' => ['bg-success-soft text-success border-transparent', 'circle-check'],
        'warning' => ['bg-warning-soft text-warning border-transparent', 'triangle-alert'],
        'danger' => ['bg-error-soft text-error border-transparent', 'circle-x'],
        'brand' => ['bg-gold text-gold-content border-transparent', null],
    ];
    [$classes, $defaultIcon] = $tones[$tone] ?? $tones['neutral'];
    $iconName = $icon === true ? $defaultIcon : $icon;
@endphp

<span {{ $attributes->class("inline-flex items-center gap-1 px-2 py-0.5 rounded-xs border text-label uppercase whitespace-nowrap $classes") }}>
    @if ($iconName)
        <x-itk.icon :name="$iconName" size="14" />
    @endif
    {{ $slot }}
</span>
