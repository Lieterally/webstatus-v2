{{-- Link back to the parent page, named after where it goes (ITK Header back label). --}}
@props(['href'])

<a href="{{ $href }}"
    {{ $attributes->class('inline-flex items-center gap-1 min-h-11 text-callout text-link hover:underline underline-offset-3 rounded-xs') }}>
    <x-itk.icon name="arrow-left" size="16" />{{ $slot }}
</a>
