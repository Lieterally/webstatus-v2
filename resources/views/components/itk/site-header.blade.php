{{--
    Public site header (ITK SiteHeader): logo lockup (colour in light, white in dark), page name,
    and actions on the right. 64px tall, 72px from 1024px; bg-surface with a hairline.
    Put the actions (theme toggle, log in) in the slot.
--}}
@props(['title', 'href' => '/'])

<header {{ $attributes->class('bg-surface border-b border-line text-ink') }}>
    <div class="flex items-center gap-3 h-16 lg:h-18 max-w-5xl mx-auto px-4 md:px-6 lg:px-8">
        <a href="{{ $href }}" class="flex items-center gap-3 min-h-11 rounded-xs no-underline">
            <img src="{{ asset('images/brand/itk-lambang-branding.png') }}" alt="Institut Teknologi Kalimantan"
                class="h-10 lg:h-12 w-auto dark:hidden">
            <img src="{{ asset('images/brand/itk-lambang-branding-white.png') }}" alt="Institut Teknologi Kalimantan"
                class="h-10 lg:h-12 w-auto hidden dark:block">
            <span class="text-headline text-ink hidden sm:inline">{{ $title }}</span>
        </a>
        <div class="ml-auto flex items-center gap-2">
            {{ $slot }}
        </div>
    </div>
</header>
