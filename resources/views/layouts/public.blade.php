<!DOCTYPE html>
<html lang="en">

<head>
    <script>
        try {
            var t = localStorage.getItem('itk-theme') ||
                (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = t;
        } catch (e) {
            document.documentElement.dataset.theme = 'light';
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Website status') - Institut Teknologi Kalimantan</title>
    <meta name="description" content="@yield('description', 'Live status and uptime of Institut Teknologi Kalimantan websites.')">
    <link rel="icon" type="image/png" href="{{ asset('images/brand/lambang-itk.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen flex flex-col bg-canvas text-ink font-sans antialiased">
    <a href="#content"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:inline-flex focus:items-center focus:min-h-11 focus:px-4 focus:rounded-sm focus:bg-surface-raised focus:text-link focus:shadow-3">
        Skip to content
    </a>

    <x-itk.site-header title="Website status" :href="route('status')">
        <x-itk.theme-toggle />
        @auth
            <x-itk.button variant="secondary" size="sm" :href="route('dashboard')" icon="layout-dashboard"
                class="max-lg:min-h-11 max-lg:px-4">Go to dashboard</x-itk.button>
        @else
            <x-itk.button variant="secondary" size="sm" :href="route('login')" icon="log-in"
                class="max-lg:min-h-11 max-lg:px-4">Staff log in</x-itk.button>
        @endauth
    </x-itk.site-header>

    <main id="content" tabindex="-1" class="flex-1 w-full max-w-5xl mx-auto px-4 md:px-6 lg:px-8 py-8 focus:outline-none">
        @yield('content')
    </main>

    <footer class="bg-surface border-t border-line text-footnote text-ink-muted">
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 p-6 text-center">
            <img src="{{ asset('images/brand/lambang-itk.png') }}" alt="" class="size-8 dark:hidden">
            <img src="{{ asset('images/brand/lambang-itk-white.png') }}" alt="" class="size-8 hidden dark:block">
            <p>&copy; {{ date('Y') }} Institut Teknologi Kalimantan. @yield('footer-note')</p>
        </div>
    </footer>

    {{-- Livewire's bundle provides Alpine; the page itself has no Livewire components --}}
    @livewireScripts
    @stack('scripts')
</body>

</html>
