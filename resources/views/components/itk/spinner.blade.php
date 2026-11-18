{{-- Activity indicator for short waits. Pair it with text when it stands alone. --}}
@props(['size' => 16, 'label' => 'Loading'])

<span role="status" {{ $attributes->merge(['class' => 'inline-flex shrink-0']) }}>
    <span class="inline-block animate-spin rounded-full border-2 border-current border-r-transparent"
        style="width: {{ (int) $size }}px; height: {{ (int) $size }}px;" aria-hidden="true"></span>
    <span class="sr-only">{{ $label }}</span>
</span>
