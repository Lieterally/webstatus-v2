@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="btn btn-ghost btn-sm">&larr; Back to Users</a>
        <h1 class="text-2xl font-bold text-base-content mt-2">Edit User</h1>
    </div>

    <div class="card bg-base-100 shadow-sm max-w-lg">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="form-control mb-4">
                    <label class="label" for="username">
                        <span class="label-text">Username</span>
                    </label>
                    <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}"
                        class="input input-bordered w-full @error('username') input-error @enderror" required minlength="3"
                        maxlength="50" autofocus>
                    <label class="label">
                        <span class="label-text-alt text-base-content/60">Between 3 and 50 characters.</span>
                    </label>
                    @error('username')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="password">
                        <span class="label-text">Password</span>
                    </label>
                    <input type="password" name="password" id="password"
                        class="input input-bordered w-full @error('password') input-error @enderror" minlength="8"
                        maxlength="128">
                    <label class="label">
                        <span class="label-text-alt text-base-content/60">Leave blank to keep the current password. Between
                            8 and 128 characters.</span>
                    </label>
                    @error('password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="role">
                        <span class="label-text">Role</span>
                    </label>
                    <select name="role" id="role"
                        class="select select-bordered w-full @error('role') select-error @enderror" required>
                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super
                            Admin</option>
                    </select>
                    @if ($user->role === 'super_admin' && $isLastSuperAdmin)
                        <label class="label">
                            <span class="label-text-alt text-warning">This is the last Super Admin account. The role cannot
                                be changed to Admin.</span>
                        </label>
                    @endif
                    @error('role')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="divider"></div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('users.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
