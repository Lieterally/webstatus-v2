{{--
    Collapsible (ITK Collapsible): a disclosure button (aria-expanded, 48px) that shows or hides a panel.
    open: initial state. closedBelow: 'sm' | 'md' | null — start closed on narrow screens unless forceOpen.
    as: element wrapping the button (h2, h3 ... when the trigger is a section heading).
    The trigger slot is the button's content (phrasing content only); the default slot is the panel.
--}}
@props(['id', 'open' => true, 'closedBelow' => null, 'forceOpen' => false, 'as' => 'div'])

@php
    $breakpoints = ['sm' => 640, 'md' => 768];
    $initial = $open ? 'true' : 'false';
    if ($closedBelow && ! $forceOpen && isset($breakpoints[$closedBelow])) {
        $initial = "window.matchMedia('(min-width: {$breakpoints[$closedBelow]}px)').matches";
    }
@endphp

<div x-data="{ open: {{ $initial }} }" {{ $attributes }}>
    <{{ $as }} class="m-0">
    <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-expanded="true"
        aria-controls="{{ $id }}"
        class="flex w-full items-center justify-between gap-3 min-h-12 text-left cursor-pointer rounded-sm">
        {{ $trigger }}
        <x-itk.icon name="chevron-down" class="shrink-0 text-ink-muted transition-transform duration-200"
            x-bind:class="open ? 'rotate-180' : ''" />
    </button>
    </{{ $as }}>
    <div id="{{ $id }}" x-show="open">
        {{ $slot }}
    </div>
</div>
