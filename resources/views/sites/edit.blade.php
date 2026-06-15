@extends('layouts.app')

@section('title', 'Edit Site')

@section('content')
    <div class="mb-6">
        <a href="{{ route('sites.index') }}" class="link link-primary text-sm">&larr; Back to Sites</a>
        <h1 class="text-2xl font-bold text-base-content mt-2">Edit Site</h1>
    </div>

    <div class="card bg-base-100 shadow-sm max-w-2xl">
        <div class="card-body">
            <form method="POST" action="{{ route('sites.update', $site) }}" x-data="pageManager()">
                @csrf
                @method('PUT')

                <div class="form-control mb-4">
                    <label class="label" for="name">
                        <span class="label-text">Name <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $site->name) }}"
                        class="input input-bordered w-full" maxlength="100" required autofocus>
                    @error('name')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="category_id">
                        <span class="label-text">Category <span class="text-error">*</span></span>
                    </label>
                    <select name="category_id" id="category_id" class="select select-bordered w-full" required>
                        <option value="">Select a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ old('category_id', $site->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="base_url">
                        <span class="label-text">Base URL <span class="text-error">*</span></span>
                    </label>
                    <input type="url" name="base_url" id="base_url" value="{{ old('base_url', $site->base_url) }}"
                        placeholder="https://example.com" class="input input-bordered w-full" required>
                    <label class="label">
                        <span class="label-text-alt text-base-content/60">Must start with http:// or https://</span>
                    </label>
                    @error('base_url')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="description">
                        <span class="label-text">Description</span>
                    </label>
                    <textarea name="description" id="description" rows="3" maxlength="500" class="textarea textarea-bordered w-full">{{ old('description', $site->description) }}</textarea>
                    @error('description')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="responsible_person_id">
                        <span class="label-text">Responsible Person <span class="text-error">*</span></span>
                    </label>
                    <select name="responsible_person_id" id="responsible_person_id" class="select select-bordered w-full"
                        required>
                        <option value="">Select a responsible person</option>
                        @foreach ($staffMembers as $staff)
                            <option value="{{ $staff->id }}"
                                {{ old('responsible_person_id', $site->responsible_person_id) == $staff->id ? 'selected' : '' }}>
                                {{ $staff->name }} - {{ $staff->position }}
                            </option>
                        @endforeach
                    </select>
                    @error('responsible_person_id')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-6">
                    <label class="label">
                        <span class="label-text">Pages <span class="text-error">*</span></span>
                    </label>
                    <p class="text-xs text-base-content/60 mb-2">At least 1 page required, max 50. Each path must start with
                        "/".</p>

                    @error('pages')
                        <div class="alert alert-error mb-2 py-2">
                            <span class="text-sm">{{ $message }}</span>
                        </div>
                    @enderror

                    <template x-for="(page, index) in pages" :key="index">
                        <div class="flex items-center gap-2 mb-2">
                            <input type="text" :name="'pages[' + index + ']'" x-model="pages[index]" placeholder="/"
                                class="input input-bordered flex-1" required>
                            <button type="button" @click="removePage(index)" x-show="pages.length > 1"
                                class="btn btn-ghost btn-sm text-error" title="Remove page">
                                &times;
                            </button>
                        </div>
                    </template>

                    @if ($errors->has('pages.*'))
                        @foreach ($errors->get('pages.*') as $messages)
                            @foreach ($messages as $message)
                                <p class="mb-1 text-sm text-error">{{ $message }}</p>
                            @endforeach
                        @endforeach
                    @endif

                    <button type="button" @click="addPage()" x-show="pages.length < 50"
                        class="btn btn-outline btn-primary btn-sm mt-2">
                        + Add Page
                    </button>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-base-200">
                    <a href="{{ route('sites.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Update Site
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function pageManager() {
                return {
                    pages: {!! json_encode(old('pages', $site->pages->pluck('path')->toArray())) !!},
                    addPage() {
                        if (this.pages.length < 50) {
                            this.pages.push('/');
                        }
                    },
                    removePage(index) {
                        if (this.pages.length > 1) {
                            this.pages.splice(index, 1);
                        }
                    }
                };
            }
        </script>
    @endpush
@endsection
