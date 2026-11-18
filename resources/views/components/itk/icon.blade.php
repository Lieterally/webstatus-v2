{{--
    Lucide icon, rendered server-side so Livewire morphing keeps it.
    Sizes: 16 in badges and small buttons, 20 beside body text (default), 24 standalone.
    Decorative by default; the control or text beside it carries the meaning.
--}}
@props(['name', 'size' => 20])

{!! preg_replace('/ stroke-width="2"/', '', svg('lucide-' . $name, '', [
    'width' => $size,
    'height' => $size,
    'stroke-width' => '1.75',
    'aria-hidden' => 'true',
    'focusable' => 'false',
    'class' => trim('shrink-0 ' . $attributes->get('class', '')),
] + $attributes->except('class')->getAttributes())->toHtml(), 1) !!}
