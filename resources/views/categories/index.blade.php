@extends('layouts.app')

@section('title', 'Categories')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Categories</h1>
            <p class="text-sm text-base-content/60 mt-1">Manage website categories</p>
        </div>
        <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">
            Add Category
        </a>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Sites</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr class="hover">
                                <td class="font-medium">{{ $category->name }}</td>
                                <td>{{ $category->sites_count }}</td>
                                <td class="text-right space-x-2">
                                    <a href="{{ route('categories.edit', $category) }}"
                                        class="btn btn-ghost btn-sm">Edit</a>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                        class="inline"
                                        onsubmit="return confirm('Are you sure you want to delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-error btn-sm btn-ghost">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-base-content/60">No categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
