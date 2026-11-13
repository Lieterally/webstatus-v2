{{--
    Field (ITK Field): label, one control, optional hint and error.
    The control inside (x-itk.input / select / textarea) picks up id, hint and error through @aware
    and sets aria-describedby and aria-invalid. error defaults to $errors->first($name).
    Usage: <x-itk.field name="email" label="Email" hint="..." required><x-itk.input type="email" /></x-itk.field>
--}}
@props(['label', 'name' => null, 'id' => null, 'hint' => null, 'error' => null, 'required' => false])

@php
    $id = $id ?? $name;
    $message = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div {{ $attributes->class('grid gap-1.5') }}>
    <label for="{{ $id }}" class="text-callout font-semibold text-ink">
        {{ $label }}@if ($required)<span class="text-error ml-0.5" aria-hidden="true">*</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)
        <p id="{{ $id }}-hint" class="text-footnote text-ink-muted">{{ $hint }}</p>
    @endif
    @if ($message)
        <p id="{{ $id }}-error" class="flex items-start gap-1 text-footnote font-medium text-error">
            <x-itk.icon name="circle-alert" size="16" class="mt-px" />
            <span>{{ $message }}</span>
        </p>
    @endif
</div>
