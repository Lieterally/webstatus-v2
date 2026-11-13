{{--
    Textarea (ITK Textarea): multi-line entry, 3 rows by default, 16px text, resizes vertically.
    Inside x-itk.field it takes id, name, hint, error and required from the field.
    Put the value in the slot.
--}}
@props(['rows' => 3])
@aware(['id' => null, 'name' => null, 'hint' => null, 'error' => null, 'required' => false])

@php
    $controlId = $attributes->get('id', $id ?? $name);
    $controlName = $attributes->get('name', $name);
    $message = $error ?? ($controlName ? $errors->first($controlName) : null);
    $describedBy = array_filter([
        $attributes->get('aria-describedby'),
        $hint ? "{$controlId}-hint" : null,
        $message ? "{$controlId}-error" : null,
    ]);
    $wired = array_filter([
        'id' => $controlId,
        'name' => $controlName,
        'required' => $required ? true : null,
        'aria-describedby' => $describedBy ? implode(' ', $describedBy) : null,
        'aria-invalid' => $message ? 'true' : null,
    ], fn ($v) => $v !== null);
@endphp

<textarea rows="{{ $rows }}" {{ $attributes->merge($wired)->class([
    'w-full min-h-24 py-2.5 rounded-sm bg-surface-sunken text-body text-ink placeholder:text-ink-muted resize-y',
    'hover:border-ink-muted disabled:text-ink-disabled disabled:border-line disabled:cursor-not-allowed',
    'px-3 border border-line-strong' => ! $message,
    'px-2.75 border-2 border-error' => (bool) $message,
]) }}>{{ $slot }}</textarea>
