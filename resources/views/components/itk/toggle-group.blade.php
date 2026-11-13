{{--
    Segmented control (ITK ToggleGroup): 2 to 7 mutually exclusive options that change one view.
    options: [value => 'Label'] for text, or [value => ['icon' => 'table', 'label' => 'Table view']] for icons
    (the label becomes aria-label). model: the Livewire property each option sets with wire:click.
    Arrow keys move and select, as in a radio group. Every option is 44px.
--}}
@props(['label', 'options' => [], 'value' => null, 'model'])

<div role="radiogroup" aria-label="{{ $label }}"
    x-data="{
        move(step) {
            const items = [...$el.querySelectorAll('[role=radio]')];
            let i = items.indexOf(document.activeElement);
            i = (i + step + items.length) % items.length;
            items[i].focus();
            items[i].click();
        }
    }"
    x-on:keydown.right.prevent="move(1)" x-on:keydown.down.prevent="move(1)"
    x-on:keydown.left.prevent="move(-1)" x-on:keydown.up.prevent="move(-1)"
    {{ $attributes->class('inline-flex max-w-full overflow-x-auto gap-0.5 p-1 rounded-md bg-surface-sunken border border-line') }}>
    @foreach ($options as $optionValue => $option)
        @php
            $checked = (string) $optionValue === (string) $value;
            $isIcon = is_array($option);
        @endphp
        <button type="button" role="radio" aria-checked="{{ $checked ? 'true' : 'false' }}"
            tabindex="{{ $checked ? '0' : '-1' }}"
            wire:click="$set('{{ $model }}', '{{ $optionValue }}')"
            @if ($isIcon) aria-label="{{ $option['label'] }}" @endif
            @class([
                'inline-flex shrink-0 items-center justify-center gap-1.5 min-h-11 min-w-11 rounded-sm border text-callout cursor-pointer',
                'px-3' => ! $isIcon,
                'bg-surface text-ink font-semibold border-line-strong shadow-1' => $checked,
                'bg-transparent text-ink-muted border-transparent hover:text-ink' => ! $checked,
            ])>
            @if ($isIcon)
                <x-itk.icon :name="$option['icon']" />
            @else
                {{ $option }}
            @endif
        </button>
    @endforeach
</div>
