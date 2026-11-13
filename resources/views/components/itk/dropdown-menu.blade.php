{{--
    DropdownMenu (ITK DropdownMenu): a button that opens a list of commands (WAI-ARIA menu button).
    Keyboard: ArrowDown or Enter/Space opens and focuses the first enabled item, Up/Down move,
    Home/End jump, typing a letter jumps to the matching item, Esc closes and returns focus to the
    button, Tab closes. Clicking outside closes.

    Props: id, label (accessible name of the menu), variant / icon (the trigger button), align: end | start.
    Slots: trigger (the button's content) and the default slot (x-itk.menu-item / x-itk.menu-separator).
    An item that opens a dialog should call close(true) first, then open the dialog on $nextTick,
    so focus returns to this button when the dialog closes.
--}}
@props(['id', 'label', 'variant' => 'secondary', 'icon' => null, 'align' => 'end'])

<div class="relative inline-flex"
    x-data="{
        open: false,
        items() { return [...this.$refs.menu.querySelectorAll('[role=menuitem]:not([aria-disabled=true])')] },
        show() {
            this.open = true;
            this.$nextTick(() => (this.items()[0] || this.$refs.menu).focus());
        },
        close(returnFocus = true) {
            this.open = false;
            if (returnFocus) this.$refs.button.focus();
        },
        move(step) {
            const els = this.items();
            if (!els.length) return;
            let i = els.indexOf(document.activeElement);
            i = i === -1 ? (step > 0 ? 0 : els.length - 1) : (i + step + els.length) % els.length;
            els[i].focus();
        },
        jump(key) {
            const match = this.items().find((el) => el.textContent.trim().toLowerCase().startsWith(key.toLowerCase()));
            if (match) match.focus();
        },
    }"
    x-on:click.outside="if (open) close(false)"
    x-on:keydown.escape="if (open) { $event.stopPropagation(); close() }"
    {{ $attributes }}>
    <x-itk.button :variant="$variant" :icon="$icon" icon-right="chevron-down" x-ref="button" id="{{ $id }}-button"
        aria-haspopup="menu" aria-controls="{{ $id }}" x-bind:aria-expanded="open.toString()" aria-expanded="false"
        x-on:click="open ? close(false) : show()" x-on:keydown.down.prevent="show()">
        {{ $trigger }}
    </x-itk.button>

    {{-- Shown with a class, not x-show: x-show waits a frame, and the first item must be focusable at once --}}
    <div x-ref="menu" id="{{ $id }}" role="menu" aria-label="{{ $label }}" tabindex="-1" x-cloak
        x-bind:class="open ? 'grid' : 'hidden'"
        x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)"
        x-on:keydown.home.prevent="items()[0]?.focus()" x-on:keydown.end.prevent="items().at(-1)?.focus()"
        x-on:keydown.tab="close(false)"
        x-on:keydown="if ($event.key.length === 1 && /\S/.test($event.key)) jump($event.key)"
        x-on:click="if (open && $event.target.closest('[role=menuitem]:not([aria-disabled=true])')) close(false)"
        @class([
            'absolute top-full z-40 mt-1.5 min-w-60 max-w-80 p-1.5 bg-surface-raised text-ink border border-line rounded-md shadow-3 focus:outline-none',
            'right-0' => $align === 'end',
            'left-0' => $align !== 'end',
        ])>
        {{ $slot }}
    </div>
</div>
