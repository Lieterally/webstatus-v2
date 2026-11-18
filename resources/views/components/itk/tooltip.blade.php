{{--
    Tooltip (ITK Tooltip): a few words shown on hover and keyboard focus after 400 ms, kept while hovered,
    closed by Esc. Supplementary only: the trigger must make sense without it.
    The slot is the trigger and must be focusable; give it aria-describedby="{{ $id }}".
--}}
@props(['content', 'id'])

<span class="relative inline-flex"
    x-data="{
        open: false,
        timer: null,
        show() { clearTimeout(this.timer); this.timer = setTimeout(() => this.open = true, 400) },
        hide() { clearTimeout(this.timer); this.open = false },
    }"
    x-on:mouseenter="show()" x-on:mouseleave="hide()" x-on:focusin="show()" x-on:focusout="hide()"
    x-on:keydown.escape="hide()">
    {{ $slot }}
    <span role="tooltip" id="{{ $id }}" x-show="open" x-cloak x-transition.opacity.duration.120ms
        class="absolute left-1/2 bottom-full z-40 mb-2 -translate-x-1/2 w-max max-w-70 px-2.5 py-1.5 rounded-sm bg-surface-inverse text-ink-inverse text-footnote font-medium shadow-2 whitespace-normal text-left">
        {{ $content }}
    </span>
</span>
