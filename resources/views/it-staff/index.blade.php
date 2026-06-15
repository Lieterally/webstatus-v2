@extends('layouts.app')

@section('title', 'IT Staff Manager')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">IT Staff Manager</h1>
            <p class="text-sm text-base-content/60 mt-1">Manage IT staff responsible for websites</p>
        </div>
        <a href="{{ route('it-staff.create') }}" class="btn btn-primary btn-sm">
            Add IT Staff
        </a>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staff as $member)
                            <tr>
                                <td class="font-medium">{{ $member->name }}</td>
                                <td class="text-base-content/70">{{ $member->position }}</td>
                                <td class="text-right space-x-2">
                                    <a href="{{ route('it-staff.edit', $member) }}" class="btn btn-ghost btn-xs">Edit</a>
                                    <form action="{{ route('it-staff.destroy', $member) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Are you sure you want to delete this staff member?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-xs text-error">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-base-content/60">No IT staff members found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
