@extends('layouts.app')

@section('title', 'Edit Category')

@section('content')
    <div class="mb-6">
        <a href="{{ route('categories.index') }}" class="btn btn-ghost btn-sm">&larr; Back to Categories</a>
        <h1 class="text-2xl font-bold text-base-content mt-2">Edit Category</h1>
    </div>

    <div class="card bg-base-100 shadow-sm max-w-lg">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.update', $category) }}">
                @csrf
                @method('PUT')

                <div class="form-control mb-4">
                    <label class="label" for="name">
                        <span class="label-text">Name</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}"
                        class="input input-bordered w-full" required autofocus>
                    @error('name')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-base-200">
                    <a href="{{ route('categories.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
