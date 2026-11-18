{{--
    One command in an x-itk.dropdown-menu. icon: Lucide name. destructive: red, goes last after a separator.
    Bind x-bind:aria-disabled="'true'" (or pass aria-disabled="true") for an unavailable item: it stays
    visible but dimmed, is skipped by the arrow keys, and doesn't close the menu.
--}}
@props(['icon' => null, 'destructive' => false])

<button type="button" role="menuitem" tabindex="-1"
    {{ $attributes->class([
        'grid grid-cols-[20px_1fr] items-center gap-3 min-h-11 px-3 rounded-sm text-left text-callout font-normal whitespace-nowrap cursor-pointer -outline-offset-2',
        'aria-disabled:text-ink-disabled aria-disabled:cursor-default aria-disabled:bg-transparent',
        'text-ink hover:bg-primary-soft hover:text-primary-soft-content focus-visible:bg-primary-soft focus-visible:text-primary-soft-content' => ! $destructive,
        'text-error hover:bg-error-soft focus-visible:bg-error-soft' => $destructive,
    ]) }}>
    <span class="grid place-items-center">
        @if ($icon)
            <x-itk.icon :name="$icon" size="18" />
        @endif
    </span>
    <span>{{ $slot }}</span>
</button>
