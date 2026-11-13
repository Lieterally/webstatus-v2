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
    <title>Log in - Webstatus ITK</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen flex items-center justify-center bg-canvas text-ink font-sans antialiased">
    <main class="w-full max-w-md px-4 py-10">
        <img src="{{ asset('images/brand/itk-lambang-branding.png') }}" alt="Institut Teknologi Kalimantan"
            class="mx-auto h-14 w-auto mb-6 dark:hidden">
        <img src="{{ asset('images/brand/itk-lambang-branding-white.png') }}" alt="Institut Teknologi Kalimantan"
            class="mx-auto h-14 w-auto mb-6 hidden dark:block">

        <div class="bg-surface border border-line rounded-lg shadow-2 p-6 md:p-8">
            <div class="text-center mb-6">
                <h1 class="text-title-1 text-ink">Log in to Webstatus</h1>
                <p class="text-callout font-normal text-ink-muted mt-1">Website monitoring system</p>
            </div>

            @if (isset($sessionExpired) && $sessionExpired)
                <x-itk.alert tone="warning" class="mb-5">Your session has expired. Log in again to continue.</x-itk.alert>
            @endif

            @if ($errors->has('login'))
                <x-itk.alert tone="danger" class="mb-5">{{ $errors->first('login') }}</x-itk.alert>
            @endif

            <form method="POST" action="{{ route('login') }}" novalidate class="grid gap-5">
                @csrf

                <x-itk.field name="username" label="Username" required>
                    <x-itk.input :value="old('username')" minlength="3" maxlength="64" autocomplete="username"
                        autocapitalize="none" spellcheck="false" autofocus />
                </x-itk.field>

                <x-itk.field name="password" label="Password" required>
                    <x-itk.input type="password" minlength="8" maxlength="128" autocomplete="current-password" />
                </x-itk.field>

                <x-itk.button type="submit" class="w-full mt-1">Log in</x-itk.button>
            </form>
        </div>

        <p class="text-center mt-5">
            <a href="{{ route('status') }}"
                class="inline-flex items-center gap-1.5 min-h-11 text-callout text-link hover:underline underline-offset-3 rounded-xs">
                <x-itk.icon name="radio-tower" size="16" />View website status
            </a>
        </p>

        <p class="text-center text-footnote text-ink-muted mt-2">
            &copy; {{ date('Y') }} Institut Teknologi Kalimantan
        </p>
    </main>
</body>

</html>
