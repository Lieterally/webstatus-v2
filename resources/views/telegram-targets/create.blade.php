@extends('layouts.app')

@section('title', 'Add Telegram Target')

@section('content')
    <div class="mb-6">
        <a href="{{ route('telegram-targets.index') }}" class="btn btn-ghost btn-sm">&larr; Back to Telegram Targets</a>
        <h1 class="text-2xl font-bold text-base-content mt-2">Add Telegram Target</h1>
    </div>

    <div class="card bg-base-100 shadow-sm max-w-lg">
        <div class="card-body">
            <form method="POST" action="{{ route('telegram-targets.store') }}">
                @csrf

                <div class="form-control mb-4">
                    <label class="label" for="chat_id">
                        <span class="label-text">Chat ID</span>
                    </label>
                    <input type="text" name="chat_id" id="chat_id" value="{{ old('chat_id') }}"
                        class="input input-bordered w-full @error('chat_id') input-error @enderror"
                        placeholder="e.g. 123456789" required autofocus maxlength="32">
                    <label class="label">
                        <span class="label-text-alt text-base-content/60">Numeric string, maximum 32 characters</span>
                    </label>
                    @error('chat_id')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4" x-data="{ isActive: {{ old('is_active', '1') == '1' ? 'true' : 'false' }} }">
                    <label class="label">
                        <span class="label-text">Status</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" class="toggle toggle-primary" :checked="isActive"
                            @change="isActive = $event.target.checked" aria-label="Toggle active status">
                        <span class="label-text" x-text="isActive ? 'Active' : 'Inactive'"></span>
                        <input type="hidden" name="is_active" :value="isActive ? '1' : '0'">
                    </div>
                    @error('is_active')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="divider"></div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('telegram-targets.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Create
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
