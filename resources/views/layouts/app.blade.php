<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Webstatus') - ITK Webstatus</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-canvas text-ink font-sans antialiased">
    <a href="#content"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:inline-flex focus:items-center focus:min-h-11 focus:px-4 focus:rounded-sm focus:bg-surface-raised focus:text-link focus:shadow-3">
        Skip to content
    </a>

    {{-- Sidebar is a fixed column from 1024px; below that it is a drawer opened by the menu button --}}
    <div class="flex h-screen" x-data="{
            navOpen: false,
            desktop: window.matchMedia('(min-width: 1024px)').matches,
            init() {
                window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => {
                    this.desktop = e.matches;
                    if (e.matches) this.navOpen = false;
                });
            },
            closeNav() { this.navOpen = false; this.$nextTick(() => this.$refs.navButton.focus()); },
        }"
        x-on:keydown.escape.window="if (navOpen) closeNav()"
        x-effect="if (navOpen) $nextTick(() => $refs.sidebar.querySelector('a, button')?.focus())">

        {{-- Scrim behind the open drawer --}}
        <div x-show="navOpen" x-transition.opacity x-cloak x-on:click="closeNav()"
            class="itk-scrim fixed inset-0 z-40 lg:hidden" aria-hidden="true"></div>

        @include('layouts.partials.sidebar')

        {{-- Main content area: inert while the drawer is open on small screens --}}
        <div class="flex-1 min-w-0 flex flex-col overflow-y-auto h-screen" x-bind:inert="navOpen && !desktop">
            {{-- Top bar --}}
            <header class="sticky top-0 z-30 bg-surface border-b border-line text-ink">
                <div class="flex items-center gap-3 h-16 lg:h-18 px-4 md:px-6 lg:px-8">
                    <x-itk.button variant="ghost" icon="menu" icon-only label="Open navigation" class="lg:hidden"
                        x-ref="navButton" aria-controls="sidebar" x-bind:aria-expanded="navOpen.toString()"
                        x-on:click="navOpen = true" />

                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-h-11 rounded-xs no-underline">
                        <img src="{{ asset('images/brand/itk-lambang-branding.png') }}"
                            alt="Institut Teknologi Kalimantan" class="h-10 w-auto dark:hidden">
                        <img src="{{ asset('images/brand/itk-lambang-branding-white.png') }}"
                            alt="Institut Teknologi Kalimantan" class="h-10 w-auto hidden dark:block">
                        <span class="text-headline text-ink hidden sm:inline">Webstatus</span>
                    </a>

                    <div class="ml-auto flex items-center gap-2 sm:gap-3">
                        <div class="text-right hidden sm:block">
                            <div class="text-callout text-ink">{{ auth()->user()->username ?? '' }}</div>
                            <div class="text-footnote text-ink-muted">
                                {{ \Illuminate\Support\Str::ucfirst(str_replace('_', ' ', auth()->user()->role ?? '')) }}
                            </div>
                        </div>

                        <x-itk.theme-toggle />

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-itk.button type="submit" variant="secondary" size="sm" icon="log-out" class="max-lg:min-h-11 max-lg:px-4">
                                Log out
                            </x-itk.button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main id="content" tabindex="-1" class="flex-1 w-full max-w-7xl mx-auto p-4 md:p-6 lg:p-8 focus:outline-none">
                @if (session('error'))
                    <x-itk.alert tone="danger" dismissible class="mb-4">{{ session('error') }}</x-itk.alert>
                @endif

                @yield('content')
            </main>

            {{-- Footer --}}
            <footer class="bg-surface border-t border-line text-footnote text-ink-muted">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 p-6 text-center">
                    <img src="{{ asset('images/brand/lambang-itk.png') }}" alt="" class="size-8 dark:hidden">
                    <img src="{{ asset('images/brand/lambang-itk-white.png') }}" alt="" class="size-8 hidden dark:block">
                    <p>&copy; {{ date('Y') }} Institut Teknologi Kalimantan. All rights reserved.</p>
                </div>
            </footer>
        </div>

    </div>

    <x-itk.toaster />
    @if (session('success'))
        <div x-data x-init="$nextTick(() => window.itkToast(@js(session('success')), 'success'))"></div>
    @endif

    @livewireScripts
    @stack('scripts')
</body>

</html>
