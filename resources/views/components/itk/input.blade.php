{{--
    Text input (ITK Input): 44px, 16px text, surface-sunken, border-line-strong; error border is 2px danger.
    Inside x-itk.field it takes id, name, hint, error and required from the field and wires
    aria-describedby / aria-invalid. Used alone it needs aria-label.
--}}
@aware(['id' => null, 'name' => null, 'hint' => null, 'error' => null, 'required' => false])
@props(['type' => 'text'])

@php
    $controlId = $attributes->get('id', $id ?? $name);
    $controlName = $attributes->get('name', $name);
    $message = $error ?? ($controlName ? $errors->first(str_replace(['[', ']'], ['.', ''], $controlName)) : null);
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

<input type="{{ $type }}" {{ $attributes->merge($wired)->class([
    'w-full min-h-11 rounded-sm bg-surface-sunken text-body text-ink placeholder:text-ink-muted',
    'hover:border-ink-muted disabled:text-ink-disabled disabled:border-line disabled:cursor-not-allowed',
    'px-3 border border-line-strong' => ! $message,
    'px-2.75 border-2 border-error' => (bool) $message,
]) }}>
