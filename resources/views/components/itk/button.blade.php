{{--
    Button (ITK Button). variant: primary | secondary | ghost | danger. size: sm (36px, dense desktop
    table rows only) | md (44px, default) | lg (52px). icon / iconRight: Lucide names. href renders <a>.
    iconOnly + label: square button whose label becomes aria-label.
    loading: a Livewire action name; while it runs the button disables and its icon becomes a
    spinner, and the text label stays.
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconRight' => null,
    'iconOnly' => false,
    'href' => null,
    'type' => 'button',
    'label' => null,
    'loading' => null,
])

@php
    $sizes = [
        'sm' => $iconOnly ? 'size-9' : 'min-h-9 px-3 text-callout',
        'md' => $iconOnly ? 'size-11' : 'min-h-11 px-5 text-body',
        'lg' => $iconOnly ? 'size-13' : 'min-h-13 px-6 text-body-l',
    ];
    $variants = [
        'primary' => 'bg-primary text-primary-content border-transparent hover:bg-primary-hover active:bg-primary-pressed',
        'secondary' => 'bg-surface text-ink border-line-strong hover:bg-surface-sunken',
        'ghost' => 'bg-transparent text-link border-transparent hover:bg-primary-soft',
        'danger' => 'bg-error-fill text-error-content border-transparent hover:brightness-95',
    ];
    $iconSize = $size === 'sm' ? 16 : 20;
    $classes = implode(' ', [
        'inline-flex items-center justify-center gap-2 shrink-0 rounded-sm border font-semibold whitespace-nowrap',
        'no-underline cursor-pointer transition-colors duration-120',
        'disabled:cursor-not-allowed disabled:bg-surface-sunken disabled:text-ink-disabled disabled:border-line disabled:filter-none',
        'aria-disabled:cursor-not-allowed aria-disabled:bg-surface-sunken aria-disabled:text-ink-disabled aria-disabled:border-line',
        $sizes[$size] ?? $sizes['md'],
        $variants[$variant] ?? $variants['primary'],
    ]);
    $extra = $label ? ['aria-label' => $label] : [];
    if ($loading) {
        $extra['wire:loading.attr'] = 'disabled';
        $extra['wire:target'] = $loading;
    }
@endphp

@if ($href)
<a href="{{ $href }}" {{ $attributes->merge($extra)->class($classes) }}>
@else
<button type="{{ $type }}" {{ $attributes->merge($extra)->class($classes) }}>
@endif
    @if ($loading)
        <x-itk.spinner :size="$iconSize" wire:loading wire:target="{{ $loading }}" />
        @if ($icon)
            <span class="inline-flex" wire:loading.remove wire:target="{{ $loading }}"><x-itk.icon :name="$icon" :size="$iconSize" /></span>
        @endif
    @elseif ($icon)
        <x-itk.icon :name="$icon" :size="$iconSize" />
    @endif
    {{ $slot }}
    @if ($iconRight)
        <x-itk.icon :name="$iconRight" :size="$iconSize" />
    @endif
@if ($href)
</a>
@else
</button>
@endif
