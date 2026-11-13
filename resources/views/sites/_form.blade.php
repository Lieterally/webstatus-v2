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
            description="Anyone can see this website's status, uptime and outages without logging in. Leave it off for internal or sensitive websites." />
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

    <div class="flex flex-wrap gap-2">
        <x-itk.button variant="secondary" icon="plus" x-show="pages.length < 50" x-on:click="addPage()">
            Add page
        </x-itk.button>
        <x-itk.button variant="secondary" x-on:click="suggest()" x-bind:disabled="discovering" aria-describedby="suggest-hint">
            <x-itk.icon name="wand-sparkles" x-bind:style="discovering ? 'display: none' : ''" />
            <x-itk.spinner size="20" label="Finding pages" style="display: none" x-bind:style="discovering ? '' : 'display: none'" />
            <span x-text="discovering ? 'Finding pages…' : 'Suggest main pages'">Suggest main pages</span>
        </x-itk.button>
    </div>
    <p id="suggest-hint" class="text-footnote text-ink-muted">
        Reads the website's menu (or its sitemap) at the base URL above. You choose which pages to add.
    </p>

    <p class="sr-only" aria-live="polite" x-text="announcement"></p>

    <div role="alert" style="display: none" x-bind:style="discoverError ? '' : 'display: none'">
        <x-itk.alert tone="warning"><span x-text="discoverError"></span></x-itk.alert>
    </div>

    {{-- Suggestions: grouped by section, the homepage and one page per section ticked --}}
    <div role="region" aria-labelledby="suggest-title" style="display: none" x-bind:style="discovery ? '' : 'display: none'"
        class="grid gap-4 p-4 rounded-md border border-line bg-surface-sunken">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h3 id="suggest-title" x-ref="suggestTitle" tabindex="-1" class="text-headline text-ink focus:outline-none">Suggested pages</h3>
            <span class="text-footnote text-ink-muted" x-text="discovery && discovery.source === 'sitemap' ? 'Found in the sitemap' : 'Found in the website menu'"></span>
        </div>
        <p class="text-footnote text-ink-muted" style="display: none" x-bind:style="discovery && discovery.message ? '' : 'display: none'"
            x-text="discovery ? discovery.message : ''"></p>

        <template x-for="group in groups" :key="group.section">
            <fieldset class="grid">
                <legend class="text-label uppercase text-ink-muted mb-1" x-text="group.section"></legend>
                <template x-for="page in group.pages" :key="page.path">
                    <label class="flex items-center gap-3 min-h-11 cursor-pointer">
                        <input type="checkbox" class="size-5 shrink-0 accent-primary cursor-pointer" x-bind:value="page.path"
                            x-model="picked" x-bind:disabled="pages.includes(page.path)">
                        <span class="min-w-0">
                            <span class="block text-callout text-ink truncate" x-text="page.label"></span>
                            <span class="block text-footnote text-ink-muted truncate">
                                <span x-text="page.path"></span><span x-show="pages.includes(page.path)"> · Already added</span>
                            </span>
                        </span>
                    </label>
                </template>
            </fieldset>
        </template>

        <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-line">
            <x-itk.button icon="plus" x-on:click="addPicked()" x-bind:disabled="!picked.length">
                <span x-text="picked.length === 1 ? 'Add 1 page' : 'Add ' + picked.length + ' pages'">Add pages</span>
            </x-itk.button>
            <x-itk.button variant="ghost" x-on:click="discovery = null; picked = []">Cancel</x-itk.button>
        </div>
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
                },

                // "Suggest main pages": ask the server to read the website's menu or sitemap
                discovering: false,
                discovery: null,
                picked: [],
                discoverError: '',
                announcement: '',
                get groups() {
                    if (!this.discovery) return [];
                    const groups = [];
                    for (const page of this.discovery.pages) {
                        let group = groups.find((g) => g.section === page.section);
                        if (!group) groups.push(group = { section: page.section, pages: [] });
                        group.pages.push(page);
                    }
                    return groups;
                },
                async suggest() {
                    const field = document.getElementById('base_url');
                    const baseUrl = (field?.value || '').trim();
                    this.discoverError = '';
                    this.discovery = null;
                    this.announcement = '';
                    if (!baseUrl) {
                        this.discoverError = 'Enter the base URL first, then suggest pages.';
                        field?.focus();
                        return;
                    }
                    this.discovering = true;
                    try {
                        const response = await fetch(@js(route('sites.discover')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({ base_url: baseUrl }),
                        });
                        const data = await response.json().catch(() => ({}));
                        if (response.status === 429) {
                            this.discoverError = 'Too many suggestions in a row. Wait a minute, then try again.';
                        } else if (!response.ok) {
                            this.discoverError = data.message || 'Something went wrong. Try again, or add pages by hand.';
                        } else if (!data.pages || !data.pages.length) {
                            this.discoverError = data.message || 'No pages were found. Add pages by hand.';
                        } else {
                            this.discovery = data;
                            this.picked = data.suggested.filter((path) => !this.pages.includes(path));
                            this.announcement = data.pages.length + ' pages found.';
                            this.$nextTick(() => this.$refs.suggestTitle.focus());
                        }
                    } catch (e) {
                        this.discoverError = "Couldn't reach the server. Check your connection and try again.";
                    } finally {
                        this.discovering = false;
                    }
                },
                addPicked() {
                    const room = 50 - this.pages.length;
                    const toAdd = this.picked.filter((path) => !this.pages.includes(path)).slice(0, room);
                    this.pages.push(...toAdd);
                    const skipped = this.picked.length - toAdd.length;
                    this.announcement = 'Added ' + toAdd.length + (toAdd.length === 1 ? ' page.' : ' pages.')
                        + (skipped > 0 ? ' ' + skipped + ' not added: a website can have at most 50 pages.' : '');
                    this.discoverError = skipped > 0 ? this.announcement : '';
                    this.discovery = null;
                    this.picked = [];
                },
            };
        }
    </script>
@endpush
