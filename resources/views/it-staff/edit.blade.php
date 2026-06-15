@extends('layouts.app')

@section('title', 'Edit IT Staff')

@section('content')
    <div class="mb-6">
        <a href="{{ route('it-staff.index') }}" class="btn btn-ghost btn-sm">&larr; Back to IT Staff</a>
        <h1 class="text-2xl font-bold text-base-content mt-2">Edit IT Staff</h1>
    </div>

    <div class="card bg-base-100 shadow-sm max-w-lg">
        <div class="card-body">
            <form method="POST" action="{{ route('it-staff.update', $itStaff) }}">
                @csrf
                @method('PUT')

                <div class="form-control mb-4">
                    <label class="label" for="name">
                        <span class="label-text">Name</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $itStaff->name) }}"
                        class="input input-bordered w-full @error('name') input-error @enderror" required maxlength="100"
                        autofocus>
                    @error('name')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="position">
                        <span class="label-text">Position</span>
                    </label>
                    <input type="text" name="position" id="position" value="{{ old('position', $itStaff->position) }}"
                        class="input input-bordered w-full @error('position') input-error @enderror" required
                        maxlength="100">
                    @error('position')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="divider"></div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('it-staff.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
