{{--
    Checkbox (ITK Checkbox) for forms that are submitted. The whole row (box + label) is the hit area, ≥44px.
    label, optional description (linked with aria-describedby), name, value, checked.
    unchecked: value sent when the box is off (a hidden input before the checkbox); omit to send nothing.
--}}
@props(['label', 'description' => null, 'name', 'value' => '1', 'checked' => false, 'unchecked' => null, 'id' => null])

@php $id = $id ?? $name; @endphp

@if (! is_null($unchecked))
    <input type="hidden" name="{{ $name }}" value="{{ $unchecked }}">
@endif
<label for="{{ $id }}" {{ $attributes->class('itk-check relative inline-flex items-start gap-3 min-h-11 pt-2.5 cursor-pointer text-body text-ink') }}>
    <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @checked($checked)
        @if ($description) aria-describedby="{{ $id }}-desc" @endif
        class="peer absolute size-5.5 opacity-0 m-0 cursor-pointer">
    <span class="itk-check-box grid place-items-center size-5.5 shrink-0 mt-px rounded-xs border-2 border-line-strong bg-surface text-transparent peer-checked:bg-primary peer-checked:border-primary peer-checked:text-primary-content peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus peer-disabled:bg-surface-sunken peer-disabled:border-line"
        aria-hidden="true">
        <x-itk.icon name="check" size="16" stroke-width="2.5" />
    </span>
    <span>
        {{ $label }}
        @if ($description)
            <span id="{{ $id }}-desc" class="block text-footnote text-ink-muted">{{ $description }}</span>
        @endif
    </span>
</label>
