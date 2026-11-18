{{--
    Inline message (ITK Alert). tone: info | success | warning | danger. Optional title.
    danger and warning announce at once (role=alert); info and success are polite (role=status).
    The icon and a hidden tone word carry the tone, not color alone.
--}}
@props(['tone' => 'info', 'title' => null, 'dismissible' => false])

@php
    $tones = [
        'info' => ['bg-primary-soft border-link', 'text-link', 'info', 'Info'],
        'success' => ['bg-success-soft border-success', 'text-success', 'circle-check', 'Success'],
        'warning' => ['bg-warning-soft border-warning', 'text-warning', 'triangle-alert', 'Warning'],
        'danger' => ['bg-error-soft border-error', 'text-error', 'circle-alert', 'Error'],
    ];
    [$box, $iconColor, $icon, $word] = $tones[$tone] ?? $tones['info'];
@endphp

<div role="{{ in_array($tone, ['danger', 'warning']) ? 'alert' : 'status' }}"
    @if ($dismissible) x-data="{ shown: true }" x-show="shown" @endif
    {{ $attributes->class("flex items-start gap-3 p-4 rounded-md border text-callout font-normal text-ink $box") }}>
    <x-itk.icon :name="$icon" class="{{ $iconColor }} mt-px" />
    <div class="min-w-0 flex-1">
        <span class="sr-only">{{ $word }}:</span>
        @if ($title)
            <strong class="block font-semibold mb-0.5">{{ $title }}</strong>
        @endif
        {{ $slot }}
    </div>
    @if ($dismissible)
        <button type="button" x-on:click="shown = false" aria-label="Dismiss"
            class="-my-3 -mr-3 grid size-11 shrink-0 place-items-center rounded-sm text-ink-muted hover:bg-surface cursor-pointer">
            <x-itk.icon name="x" />
        </button>
    @endif
</div>
