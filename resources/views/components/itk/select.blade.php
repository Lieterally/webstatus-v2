{{--
    Native select (ITK NativeSelect) with the field chrome: 44px, 16px text, chevron.
    Inside x-itk.field it takes id, name, hint, error and required from the field.
    Used alone it needs aria-label. Options go in the slot as <option> elements.
--}}
@aware(['id' => null, 'name' => null, 'hint' => null, 'error' => null, 'required' => false])

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
    $wrapperClass = $attributes->get('class', '');
@endphp

<div class="relative {{ $wrapperClass }}">
    <select {{ $attributes->except('class')->merge($wired)->class([
        'w-full min-h-11 appearance-none rounded-sm bg-surface-sunken text-body text-ink pr-10 cursor-pointer',
        'hover:border-ink-muted disabled:text-ink-disabled disabled:border-line disabled:cursor-not-allowed',
        'pl-3 border border-line-strong' => ! $message,
        'pl-2.75 border-2 border-error' => (bool) $message,
    ]) }}>
        {{ $slot }}
    </select>
    <x-itk.icon name="chevron-down" class="pointer-events-none absolute right-3 top-3 text-ink-muted" />
</div>
