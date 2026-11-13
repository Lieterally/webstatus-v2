{{--
    Card (ITK Card): hairline border + shadow-1, radius-md, padding 16 / 24 from 768px.
    title (optional) with titleAs (h2 default); the actions slot sits beside the title.
    flush removes the body padding (for tables that run edge to edge).
--}}
@props(['title' => null, 'titleAs' => 'h2', 'flush' => false])

<section {{ $attributes->class([
    'bg-surface text-ink border border-line rounded-md shadow-1',
    'p-4 md:p-6' => ! $flush,
]) }}>
    @if ($title || isset($actions))
        <div @class([
            'flex flex-wrap items-center justify-between gap-3 mb-4',
            'px-4 pt-4 md:px-6 md:pt-6' => $flush,
        ])>
            @if ($title)
                <{{ $titleAs }} class="text-title-3 text-ink">{{ $title }}</{{ $titleAs }}>
            @endif
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    {{ $slot }}
</section>
