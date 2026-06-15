@extends('layouts.app')

@section('title', 'User Manager')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">User Manager</h1>
            <p class="text-sm text-base-content/60 mt-1">Manage system users and roles</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
            Add User
        </a>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="font-medium">
                                    {{ $user->username }}
                                    @if ($user->id === auth()->id())
                                        <span class="badge badge-ghost badge-sm ml-2">You</span>
                                    @endif
                                </td>
                                <td>
                                    <span
                                        class="badge {{ $user->role === 'super_admin' ? 'badge-secondary' : 'badge-primary' }} badge-sm">
                                        {{ $user->role === 'super_admin' ? 'Super Admin' : 'Admin' }}
                                    </span>
                                </td>
                                <td class="text-right space-x-2">
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-ghost btn-xs">Edit</a>
                                    @php
                                        $isOwnAccount = $user->id === auth()->id();
                                        $isLastSuperAdmin = $user->role === 'super_admin' && $superAdminCount <= 1;
                                        $canDelete = !$isOwnAccount && !$isLastSuperAdmin;
                                    @endphp
                                    @if ($canDelete)
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure you want to delete this user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-ghost btn-xs text-error">Delete</button>
                                        </form>
                                    @else
                                        <span class="btn btn-ghost btn-xs btn-disabled"
                                            title="{{ $isOwnAccount ? 'You cannot delete your own account' : 'Cannot delete the last Super Admin' }}">Delete</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-base-content/60">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
