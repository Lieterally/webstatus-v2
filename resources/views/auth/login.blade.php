<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Webstatus ITK</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen flex items-center justify-center bg-base-200 font-sans">
    <div class="w-full max-w-md mx-4">
        {{-- Login Card --}}
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-8">
                {{-- ITK Logo and Title --}}
                <div class="text-center mb-8">
                    <img src="{{ asset('images/Logo_ITK.webp') }}" alt="Institut Teknologi Kalimantan"
                        class="mx-auto h-20 mb-4">
                    <h1 class="text-2xl font-bold text-base-content">Webstatus</h1>
                    <p class="text-sm text-base-content/60 mt-1">Website Monitoring System</p>
                </div>

                {{-- Session Expired Message --}}
                @if (isset($sessionExpired) && $sessionExpired)
                    <div class="alert alert-warning mb-5">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>Your session has expired. Please log in again.</span>
                    </div>
                @endif

                {{-- Login Error Message (generic - no field-specific hints) --}}
                @if ($errors->has('login'))
                    <div class="alert alert-error mb-5">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>{{ $errors->first('login') }}</span>
                    </div>
                @endif

                {{-- Login Form --}}
                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    {{-- Username Field --}}
                    <div class="form-control mb-5">
                        <label class="label" for="username">
                            <span class="label-text">Username</span>
                        </label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}"
                            class="input input-bordered w-full" placeholder="Enter your username" required
                            minlength="3" maxlength="64" autocomplete="username" autofocus>
                        @error('username')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>

                    {{-- Password Field --}}
                    <div class="form-control mb-6">
                        <label class="label" for="password">
                            <span class="label-text">Password</span>
                        </label>
                        <input type="password" name="password" id="password" class="input input-bordered w-full"
                            placeholder="Enter your password" required minlength="8" maxlength="128"
                            autocomplete="current-password">
                        @error('password')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>

                    {{-- Login Button --}}
                    <button type="submit" class="btn btn-primary w-full">
                        Log In
                    </button>
                </form>
            </div>
        </div>

        {{-- Footer --}}
        <p class="text-center text-xs text-base-content/40 mt-6">
            &copy; {{ date('Y') }} Institut Teknologi Kalimantan
        </p>
    </div>
</body>

</html>
