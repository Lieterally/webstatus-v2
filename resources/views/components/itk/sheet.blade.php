{{--
    Side sheet (ITK Sheet, right side) for a panel that Livewire shows and hides with @if.
    close: a $wire call run on Esc, the scrim and the close button (for example "$wire.toggleLogDrawer()").
    returnFocus: id of the trigger that gets focus back once Livewire has removed the sheet.
    Focus is trapped inside while open (x-trap) and the page behind is inert.
    Not a native <dialog>: Livewire re-renders this panel every poll, and morphing would drop the
    dialog's open state.
--}}
@props(['title', 'close', 'returnFocus' => null, 'titleId' => 'sheet-title'])

@php
    $closeJs = $returnFocus
        ? "{$close}.then(() => document.getElementById('{$returnFocus}')?.focus())"
        : $close;
@endphp

<div class="fixed inset-0 z-50 flex justify-end" x-data x-on:keydown.escape.window="{{ $closeJs }}">
    <div class="itk-scrim absolute inset-0" x-on:click="{{ $closeJs }}" aria-hidden="true"></div>

    <div role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}" x-trap.inert.noscroll="true"
        {{ $attributes->class('relative flex flex-col w-full max-w-lg h-full bg-surface-raised text-ink shadow-3') }}>
        <div class="flex items-center justify-between gap-3 py-3 pl-5 pr-3 border-b border-line">
            <div class="flex items-center gap-2 min-w-0">
                <h2 id="{{ $titleId }}" class="text-headline text-ink">{{ $title }}</h2>
                {{ $meta ?? '' }}
            </div>
            <x-itk.button variant="ghost" icon="x" icon-only label="Close" x-on:click="{{ $closeJs }}" />
        </div>
        {{ $slot }}
    </div>
</div>
