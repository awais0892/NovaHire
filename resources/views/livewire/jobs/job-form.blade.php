<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ $isEditing ? 'Edit Job Listing' : 'Create Job Listing' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fill in role details and publish when ready.</p>
        </div>
        <a href="{{ route('recruiter.jobs.index') }}"
            class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            Back to Jobs
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Basic Information</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Job Title *</label>
                    <input wire:model="title" type="text" placeholder="e.g. Senior Laravel Developer"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Department</label>
                    <input wire:model="department" type="text" placeholder="e.g. Engineering"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Experience Level</label>
                    <select wire:model="experience_level"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Select level</option>
                        <option value="Junior">Junior (0-2 years)</option>
                        <option value="Mid">Mid (2-5 years)</option>
                        <option value="Senior">Senior (5+ years)</option>
                        <option value="Lead">Lead / Principal</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Job Type *</label>
                    <select wire:model="job_type"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="full_time">Full Time</option>
                        <option value="part_time">Part Time</option>
                        <option value="contract">Contract</option>
                        <option value="internship">Internship</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Location Type *</label>
                    <select wire:model="location_type"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="onsite">On-site</option>
                        <option value="remote">Remote</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </div>

                <div class="md:col-span-2"
                    x-data="livewireLocationAutocomplete({
                        endpoint: @js(route('locations.autocomplete')),
                        initialValue: @js($location),
                        selectMethod: 'selectLocationSuggestion',
                        clearMethod: 'clearSelectedLocation',
                    })"
                    x-init="init()"
                    @click.outside="close()">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Location *</label>
                    <div class="relative">
                        <input x-ref="input" wire:model.live.debounce.250ms="location" x-model="query" x-on:input="handleInput($event)" x-on:focus="handleFocus()" x-on:keydown.arrow-down.prevent="moveHighlight(1)"
                            x-on:keydown.arrow-up.prevent="moveHighlight(-1)" x-on:keydown.enter.prevent="chooseHighlighted()" x-on:keydown.escape="open = false"
                            type="text" placeholder="Type a city or region"
                            class="h-11 w-full rounded-lg border border-gray-300 px-3 pr-10 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        <button x-show="query" x-on:click.prevent="clearQuery()" type="button"
                            class="absolute inset-y-0 right-3 inline-flex items-center text-xs font-semibold text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                            Clear
                        </button>

                        <div x-cloak x-show="open"
                            class="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900">
                            <template x-for="(suggestion, index) in suggestions" :key="(suggestion.place_id || suggestion.description || 'location') + '-' + index">
                                <button type="button" x-on:click="selectSuggestion(suggestion)"
                                    class="flex w-full items-start justify-between gap-3 border-b border-gray-100 px-3 py-3 text-left last:border-b-0 dark:border-gray-800"
                                    :class="index === highlightedIndex ? 'bg-brand-50 dark:bg-brand-500/10' : ''">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="suggestion.main_text || suggestion.description"></span>
                                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="suggestion.secondary_text || suggestion.description"></span>
                                    </span>
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                        x-text="suggestion.source"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <p x-show="loading" class="mt-2 text-xs text-gray-500 dark:text-gray-400">Searching locations...</p>
                    <p x-show="feedbackMessage" x-text="feedbackMessage" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></p>
                    @error('location') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Select a suggested place to save coordinates for map and radius search. Manual text still works if needed.
                    </p>

                    @if($location_latitude && $location_longitude)
                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-brand-50 px-3 py-1 font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
                                {{ $location_city ?: 'Mapped location' }}
                            </span>
                            @if($location_region)
                                <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    {{ $location_region }}
                                </span>
                            @endif
                            @if($location_country_code)
                                <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    {{ $location_country_code }}
                                </span>
                            @endif
                            <span class="rounded-full bg-emerald-50 px-3 py-1 font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">
                                {{ number_format($location_latitude, 4) }}, {{ number_format($location_longitude, 4) }}
                            </span>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50">
                            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Map Preview</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $location_label ?: $location }}</p>
                                </div>
                                <a href="https://www.openstreetmap.org/?mlat={{ $location_latitude }}&mlon={{ $location_longitude }}#map=13/{{ $location_latitude }}/{{ $location_longitude }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex h-9 items-center rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Open Map
                                </a>
                            </div>
                            <iframe
                                title="Selected job location map"
                                src="https://www.openstreetmap.org/export/embed.html?bbox={{ $location_longitude - 0.08 }},{{ $location_latitude - 0.08 }},{{ $location_longitude + 0.08 }},{{ $location_latitude + 0.08 }}&layer=mapnik&marker={{ $location_latitude }},{{ $location_longitude }}"
                                class="h-64 w-full border-0"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Compensation</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Min Salary</label>
                    <input wire:model="salary_min" type="number" placeholder="30000"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Max Salary</label>
                    <input wire:model="salary_max" type="number" placeholder="50000"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Currency</label>
                    <select wire:model="salary_currency"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="GBP">GBP</option>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <input wire:model="salary_visible" type="checkbox" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                        Show salary on public job board
                    </label>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Required Skills</h2>
            <div class="mt-4 flex flex-col gap-3 md:flex-row">
                <input wire:model="newSkill" wire:keydown.enter.prevent="addSkill" type="text" placeholder="e.g. Laravel"
                    class="h-11 flex-1 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                <select wire:model="newSkillLevel"
                    class="h-11 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="required">Required</option>
                    <option value="preferred">Preferred</option>
                </select>
                <button type="button" wire:click="addSkill"
                    class="inline-flex h-11 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                    Add Skill
                </button>
            </div>

            @error('newSkill') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror

            <div class="mt-4 flex flex-wrap gap-2">
                @forelse($skills as $index => $skill)
                    <span class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        {{ $skill['skill'] }} ({{ $skill['level'] }})
                        <button type="button" wire:click="removeSkill({{ $index }})" class="text-red-500 hover:text-red-600">x</button>
                    </span>
                @empty
                    <p class="text-sm text-gray-500">No skills added yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Description and Requirements</h2>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Job Description *</label>
                    <textarea wire:model="description" rows="7" placeholder="Describe responsibilities, team, and expectations"
                        class="w-full rounded-lg border border-gray-300 p-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Requirements</label>
                    <textarea wire:model="requirements" rows="4" placeholder="Qualifications, certifications, years of experience"
                        class="w-full rounded-lg border border-gray-300 p-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Benefits</label>
                    <textarea wire:model="benefits" rows="4" placeholder="Perks, health insurance, remote policy, bonuses"
                        class="w-full rounded-lg border border-gray-300 p-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Publishing</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Status</label>
                    <select wire:model="status"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="draft">Save as Draft</option>
                        <option value="active">Publish Now</option>
                        <option value="paused">Paused</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Vacancies</label>
                    <input wire:model="vacancies" type="number" min="1"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    @error('vacancies') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Expiry Date</label>
                    <input wire:model="expires_at" type="date"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    @error('expires_at') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('recruiter.jobs.index') }}"
                class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                Cancel
            </a>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex h-10 items-center rounded-lg bg-brand-500 px-5 text-sm font-semibold text-white hover:bg-brand-600 disabled:opacity-60">
                <span wire:loading.remove>{{ $isEditing ? 'Update Job' : 'Create Job' }}</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>
    </form>
</div>
