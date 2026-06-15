@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Change Password</h1>
        <p class="text-sm text-base-content/60 mt-1">Update your password to keep your account secure.</p>
    </div>

    {{-- Success message --}}
    @if (session('success'))
        <div class="alert alert-success mb-4">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="card bg-base-100 shadow-sm max-w-lg">
        <div class="card-body">
            <form method="POST" action="{{ route('profile.password.update') }}">
                @csrf
                @method('PUT')

                {{-- Current Password --}}
                <div class="form-control mb-4">
                    <label class="label" for="current_password">
                        <span class="label-text">Current Password</span>
                    </label>
                    <input type="password" name="current_password" id="current_password" class="input input-bordered w-full"
                        required autofocus>
                    @error('current_password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                {{-- New Password --}}
                <div class="form-control mb-4">
                    <label class="label" for="new_password">
                        <span class="label-text">New Password</span>
                    </label>
                    <input type="password" name="new_password" id="new_password" class="input input-bordered w-full"
                        required minlength="8" maxlength="128">
                    <label class="label">
                        <span class="label-text-alt text-base-content/60">Must be between 8 and 128 characters.</span>
                    </label>
                    @error('new_password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                {{-- Confirm New Password --}}
                <div class="form-control mb-4">
                    <label class="label" for="new_password_confirmation">
                        <span class="label-text">Confirm New Password</span>
                    </label>
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                        class="input input-bordered w-full" required minlength="8" maxlength="128">
                    @error('new_password_confirmation')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-base-200">
                    <a href="{{ route('dashboard') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
