{{-- Sidebar (ITK Sidebar). Static column from 1024px; an off-canvas drawer below, driven by navOpen in the layout. --}}
<aside id="sidebar" x-ref="sidebar"
    class="fixed inset-y-0 left-0 z-50 flex flex-col w-68 bg-surface border-r border-line -translate-x-full transition-transform duration-200 lg:static lg:z-auto lg:translate-x-0"
    x-bind:class="navOpen && 'translate-x-0 shadow-3'"
    x-bind:inert="!desktop && !navOpen"
    x-bind:role="desktop ? null : 'dialog'"
    x-bind:aria-modal="(!desktop && navOpen) ? 'true' : null"
    aria-label="Navigation">
    {{-- Header shown when the drawer is open on small screens --}}
    <div class="flex items-center justify-between gap-2 p-4 border-b border-line lg:hidden">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-h-11 rounded-xs no-underline">
            <img src="{{ asset('images/brand/lambang-itk.png') }}" alt="" class="size-8 dark:hidden">
            <img src="{{ asset('images/brand/lambang-itk-white.png') }}" alt="" class="size-8 hidden dark:block">
            <span class="text-headline text-ink">Webstatus</span>
        </a>
        <x-itk.button variant="ghost" icon="x" icon-only label="Close navigation" x-on:click="closeNav()" />
    </div>

    <nav aria-label="Main" class="flex-1 overflow-auto px-3 py-2 grid gap-4 content-start">
        <ul class="grid gap-0.5">
            <x-itk.sidebar-link :href="route('dashboard')" icon="house" :current="request()->routeIs('dashboard')">
                Dashboard
            </x-itk.sidebar-link>
            <x-itk.sidebar-link :href="route('downtime.index')" icon="triangle-alert" :current="request()->routeIs('downtime.*')">
                Downtime details
            </x-itk.sidebar-link>
            <x-itk.sidebar-link :href="route('sites.index')" icon="globe"
                :current="request()->routeIs('sites.*') || request()->routeIs('categories.*')">
                Website manager
            </x-itk.sidebar-link>
        </ul>

        @if (auth()->user() && auth()->user()->role === 'super_admin')
            <div>
                <h2 class="mx-3 mt-2 mb-1 text-label uppercase text-ink-muted">Administration</h2>
                <ul class="grid gap-0.5">
                    <x-itk.sidebar-link :href="route('users.index')" icon="users" :current="request()->routeIs('users.*')">
                        User manager
                    </x-itk.sidebar-link>
                    <x-itk.sidebar-link :href="route('it-staff.index')" icon="id-card" :current="request()->routeIs('it-staff.*')">
                        IT staff manager
                    </x-itk.sidebar-link>
                    <x-itk.sidebar-link :href="route('telegram-targets.index')" icon="send" :current="request()->routeIs('telegram-targets.*')">
                        Telegram manager
                    </x-itk.sidebar-link>
                    <x-itk.sidebar-link :href="route('system-config.index')" icon="settings" :current="request()->routeIs('system-config.*')">
                        System config
                    </x-itk.sidebar-link>
                </ul>
            </div>
        @endif

        <div>
            <h2 class="mx-3 mt-2 mb-1 text-label uppercase text-ink-muted">Settings</h2>
            <ul class="grid gap-0.5">
                <x-itk.sidebar-link :href="route('profile.password')" icon="key-round" :current="request()->routeIs('profile.*')">
                    Change password
                </x-itk.sidebar-link>
            </ul>
        </div>

        <div>
            <h2 class="mx-3 mt-2 mb-1 text-label uppercase text-ink-muted">Public</h2>
            <ul class="grid gap-0.5">
                <x-itk.sidebar-link :href="route('status')" icon="radio-tower" target="_blank" rel="noopener">
                    <span class="inline-flex items-center gap-1.5">
                        Status page<x-itk.icon name="external-link" size="14" class="text-ink-muted" /><span class="sr-only"> (opens in a new tab)</span>
                    </span>
                </x-itk.sidebar-link>
            </ul>
        </div>
    </nav>
</aside>
