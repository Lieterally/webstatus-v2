{{--
    Light/dark switch (ghost icon button). Sets data-theme on <html>, saves it as localStorage
    'itk-theme' and dispatches 'itk-theme-change' so charts can redraw.
--}}
<div x-data="{
    dark: document.documentElement.dataset.theme === 'dark',
    toggle() {
        this.dark = !this.dark;
        const theme = this.dark ? 'dark' : 'light';
        document.documentElement.dataset.theme = theme;
        try { localStorage.setItem('itk-theme', theme); } catch (e) {}
        window.dispatchEvent(new CustomEvent('itk-theme-change', { detail: { theme } }));
    }
}" {{ $attributes }}>
    <x-itk.button variant="ghost" icon-only x-on:click="toggle()"
        x-bind:aria-label="dark ? 'Switch to light theme' : 'Switch to dark theme'" aria-label="Switch theme">
        <x-itk.icon name="moon" x-show="!dark" />
        <x-itk.icon name="sun" x-show="dark" x-cloak />
    </x-itk.button>
</div>
