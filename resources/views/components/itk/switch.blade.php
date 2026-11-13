{{--
    Switch (ITK Switch, role="switch") for a setting in a list row. The label names the setting, not the state.
    name, value, checked; unchecked: value sent when off (hidden input before the switch).
    The track is 44×26 and the whole row label is the hit area.
--}}
@props(['label', 'description' => null, 'name', 'value' => '1', 'checked' => false, 'unchecked' => null, 'id' => null])

@php $id = $id ?? $name; @endphp

@if (! is_null($unchecked))
    <input type="hidden" name="{{ $name }}" value="{{ $unchecked }}">
@endif
<label for="{{ $id }}" {{ $attributes->class('flex items-center justify-between gap-4 min-h-13 py-2 cursor-pointer') }}>
    <span class="min-w-0">
        <span class="block text-body text-ink">{{ $label }}</span>
        @if ($description)
            <span id="{{ $id }}-desc" class="block text-footnote text-ink-muted">{{ $description }}</span>
        @endif
    </span>
    <span class="relative inline-flex shrink-0">
        <input type="checkbox" role="switch" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @checked($checked)
            @if ($description) aria-describedby="{{ $id }}-desc" @endif
            class="peer absolute inset-0 m-0 opacity-0 cursor-pointer">
        <span aria-hidden="true"
            class="itk-switch-track relative block w-11 h-6.5 rounded-full border-2 border-line-strong bg-surface-sunken transition-colors duration-120 peer-checked:bg-primary peer-checked:border-primary peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus"></span>
    </span>
</label>
