<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Webstatus') - ITK Webstatus</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/pro.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/sharp-duotone-thin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/sharp-duotone-solid.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/sharp-duotone-regular.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/sharp-duotone-light.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/sharp-thin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/sharp-solid.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/sharp-regular.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/sharp-light.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/duotone-thin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/duotone-regular.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/duotone-light.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/woff2.css') }}">
</head>

<body class="min-h-screen bg-base-200 font-sans antialiased">
    <div class="drawer lg:drawer-open h-screen">
        <input id="sidebar-drawer" type="checkbox" class="drawer-toggle" />

        {{-- Main content area --}}
        <div class="drawer-content flex flex-col overflow-y-auto h-screen">
            {{-- Navbar --}}
            <div class="navbar bg-primary text-primary-content sticky top-0 z-30 shadow-md">
                <div class="flex-none lg:hidden">
                    <label for="sidebar-drawer" class="btn btn-square btn-ghost">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="flex-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <img src="{{ asset('images/Logo_ITK_White.webp') }}" alt="ITK Logo" class="h-9 w-auto">
                        <span class="font-semibold text-lg hidden sm:inline">Webstatus</span>
                    </a>
                </div>
                <div class="flex-none">
                    <div class="flex items-center gap-3">

                        <div class="text-right">
                            <div class="text-sm font-medium">
                                {{ auth()->user()->username ?? '' }}
                            </div>
                            <div class="text-xs opacity-70">
                                {{ auth()->user()->role ?? '' }}
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm btn-primary-content">
                                Logout
                            </button>
                        </form>

                    </div>
                </div>
            </div>

            {{-- Page content --}}
            <main class="flex-1 p-4 md:p-6 lg:p-8">
                {{-- Flash messages --}}
                @if (session('success'))
                    <div role="alert" class="alert alert-success mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div role="alert" class="alert alert-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </main>

            {{-- Footer --}}
            <footer class="footer footer-center bg-neutral text-neutral-content p-6">
                <div>
                    <img src="{{ asset('images/Logo_ITK.webp') }}" alt="ITK Logo" class="h-10 w-auto">
                    <p class="text-sm opacity-70">&copy; {{ date('Y') }} Institut Teknologi Kalimantan. All rights
                        reserved.</p>
                </div>
            </footer>
        </div>

        {{-- Sidebar --}}
        <div class="drawer-side z-40">
            <label for="sidebar-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <aside class="bg-base-100 w-64 min-h-full border-r border-base-300">
                {{-- Sidebar header (visible on mobile when open) --}}
                <div class="p-4 border-b border-base-300 lg:hidden">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <img src="{{ asset('images/Logo_ITK.webp') }}" alt="ITK Logo" class="h-8 w-auto">
                        <span class="font-semibold text-lg">Webstatus</span>
                    </a>
                </div>

                <ul class="menu p-4 gap-1">
                    {{-- Dashboard --}}
                    <li>
                        <a href="{{ route('dashboard') }}"
                            class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="fa-regular fa-home fa-lg fa-fw"></i>
                            Dashboard
                        </a>
                    </li>

                    {{-- Downtime Details --}}
                    <li>
                        <a href="{{ route('downtime.index') }}"
                            class="{{ request()->routeIs('downtime.*') ? 'active' : '' }}">
                            <i class="fa-regular fa-triangle-exclamation fa-lg fa-fw"></i>
                            Downtime Details
                        </a>
                    </li>

                    {{-- Website Manager --}}
                    <li>
                        <a href="{{ route('sites.index') }}"
                            class="{{ request()->routeIs('sites.*') || request()->routeIs('categories.*') ? 'active' : '' }}">
                            <i class="fa-regular fa-globe fa-lg fa-fw"></i>
                            Website Manager
                        </a>
                    </li>

                    @if (auth()->user() && auth()->user()->role === 'super_admin')
                        <li class="menu-title mt-4">Administration</li>

                        <li>
                            <a href="{{ route('users.index') }}"
                                class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <i class="fa-regular fa-users fa-lg fa-fw"></i>
                                User Manager
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('it-staff.index') }}"
                                class="{{ request()->routeIs('it-staff.*') ? 'active' : '' }}">
                                <i class="fa-regular fa-id-card fa-lg fa-fw"></i>
                                IT Staff Manager
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('telegram-targets.index') }}"
                                class="{{ request()->routeIs('telegram-targets.*') ? 'active' : '' }}">
                                <i class="fa-brands fa-telegram fa-lg fa-fw"></i>
                                Telegram Manager
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('system-config.index') }}"
                                class="{{ request()->routeIs('system-config.*') ? 'active' : '' }}">
                                <i class="fa-regular fa-gear fa-lg fa-fw"></i>
                                System Config
                            </a>
                        </li>
                    @endif

                    <li class="menu-title mt-4">Settings</li>

                    <li>
                        <a href="{{ route('profile.password') }}"
                            class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                            <i class="fa-regular fa-key fa-lg fa-fw"></i>
                            Change Password
                        </a>
                    </li>
                </ul>
            </aside>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>
