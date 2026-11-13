{{-- Fields shared by sites/create and sites/edit. $site is null on create. --}}
@php $site = $site ?? null; @endphp

<x-itk.field name="name" label="Name" required>
    <x-itk.input :value="old('name', $site?->name)" maxlength="100" autofocus />
</x-itk.field>

<x-itk.field name="category_id" label="Category" required>
    <x-itk.select>
        <option value="">Select a category</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $site?->category_id) == $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </x-itk.select>
</x-itk.field>

<x-itk.field name="base_url" label="Base URL" hint="Starts with http:// or https://, for example https://example.itk.ac.id"
    required>
    <x-itk.input type="url" :value="old('base_url', $site?->base_url)" inputmode="url" autocomplete="url"
        spellcheck="false" />
</x-itk.field>

<x-itk.field name="description" label="Description (optional)" hint="Up to 500 characters.">
    <x-itk.textarea maxlength="500">{{ old('description', $site?->description) }}</x-itk.textarea>
</x-itk.field>

<x-itk.field name="responsible_person_id" label="Responsible person" required>
    <x-itk.select>
        <option value="">Select a responsible person</option>
        @foreach ($staffMembers as $staff)
            <option value="{{ $staff->id }}" @selected(old('responsible_person_id', $site?->responsible_person_id) == $staff->id)>
                {{ $staff->name }} – {{ $staff->position }}
            </option>
        @endforeach
    </x-itk.select>
</x-itk.field>

@if ($site)
    <div>
        <x-itk.checkbox name="is_monitored" label="Monitor this site" unchecked="0"
            :checked="(bool) old('is_monitored', $site->is_monitored)"
            description="When off, checking cycles skip this site and it is left out of down alerts. Changing this closes any open outage for the site." />
        @error('is_monitored')
            <p class="flex items-start gap-1 text-footnote font-medium text-error mt-1">
                <x-itk.icon name="circle-alert" size="16" class="mt-px" />{{ $message }}
            </p>
        @enderror
    </div>
@endif

{{-- Public status page --}}
<fieldset class="grid gap-3 pt-5 border-t border-line" x-data="{ isPublic: @js((bool) old('is_public', $site?->is_public ?? false)) }">
    <legend class="text-headline text-ink pt-5 float-left w-full">Public status page</legend>
    <div>
        <x-itk.checkbox name="is_public" label="Show on the public status page" unchecked="0"
            :checked="(bool) old('is_public', $site?->is_public ?? false)" x-on:change="isPublic = $event.target.checked"
            description="Anyone can see this website's status, uptime and outages without logging in. Leave it off for internal or sensitive services." />
        @error('is_public')
            <p class="flex items-start gap-1 text-footnote font-medium text-error mt-1">
                <x-itk.icon name="circle-alert" size="16" class="mt-px" />{{ $message }}
            </p>
        @enderror
    </div>
    <x-itk.field name="public_name" label="Public name (optional)"
        hint="Shown on the public page instead of the name above. Up to 100 characters." x-show="isPublic" x-cloak>
        <x-itk.input :value="old('public_name', $site?->public_name)" maxlength="100" />
    </x-itk.field>
</fieldset>

<fieldset class="grid gap-2" x-data="pageManager()">
    <legend class="text-callout font-semibold text-ink mb-1.5">
        Pages<span class="text-error ml-0.5" aria-hidden="true">*</span>
    </legend>
    <p id="pages-hint" class="text-footnote text-ink-muted">At least 1 page, at most 50. Each path starts with "/".</p>

    @error('pages')
        <x-itk.alert tone="danger">{{ $message }}</x-itk.alert>
    @enderror

    <template x-for="(page, index) in pages" :key="index">
        <div class="flex items-center gap-2">
            <input type="text" :name="'pages[' + index + ']'" x-model="pages[index]" required
                :aria-label="'Page path ' + (index + 1)" aria-describedby="pages-hint" spellcheck="false"
                class="flex-1 min-w-0 min-h-11 px-3 rounded-sm bg-surface-sunken border border-line-strong text-body text-ink hover:border-ink-muted">
            <x-itk.button variant="ghost" icon="trash-2" icon-only x-show="pages.length > 1" x-on:click="removePage(index)"
                x-bind:aria-label="'Remove page ' + (index + 1)" aria-label="Remove page" class="text-error hover:bg-error-soft" />
        </div>
    </template>

    @if ($errors->has('pages.*'))
        <ul class="grid gap-1">
            @foreach ($errors->get('pages.*') as $messages)
                @foreach ($messages as $message)
                    <li class="flex items-start gap-1 text-footnote font-medium text-error">
                        <x-itk.icon name="circle-alert" size="16" class="mt-px" />{{ $message }}
                    </li>
                @endforeach
            @endforeach
        </ul>
    @endif

    <div>
        <x-itk.button variant="secondary" icon="plus" x-show="pages.length < 50" x-on:click="addPage()">
            Add page
        </x-itk.button>
    </div>
</fieldset>

@push('scripts')
    <script>
        function pageManager() {
            return {
                pages: @js(old('pages', $site ? $site->pages->pluck('path')->toArray() : ['/'])),
                addPage() {
                    if (this.pages.length < 50) {
                        this.pages.push('/');
                        this.$nextTick(() => {
                            const inputs = this.$root.querySelectorAll('input[type=text]');
                            inputs[inputs.length - 1]?.focus();
                        });
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
