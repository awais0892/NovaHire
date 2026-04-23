<div class="mx-auto max-w-[1680px] space-y-6 p-4 md:p-6">
    @php
        $typeOptions = [
            'full_time' => 'Full-time',
            'part_time' => 'Part-time',
            'contract' => 'Contract',
            'internship' => 'Internship',
        ];
        $modeOptions = [
            'onsite' => 'On-site',
            'remote' => 'Remote',
            'hybrid' => 'Hybrid',
        ];
        $experienceOptions = [
            'Junior' => 'Junior',
            'Mid' => 'Mid',
            'Senior' => 'Senior',
            'Lead' => 'Lead',
        ];
        $radiusOptions = [
            '10' => '10 km',
            '25' => '25 km',
            '50' => '50 km',
            '100' => '100 km',
        ];
        $postedOptions = [
            '1' => 'Last 24 hours',
            '3' => 'Last 3 days',
            '7' => 'Last 7 days',
            '14' => 'Last 14 days',
        ];
        $sortOptions = [
            'published_at' => 'Newest first',
            'salary_max' => 'Highest salary',
            'distance_km' => 'Closest distance',
        ];
    @endphp

    <section class="relative z-20 rounded-[30px] border border-sky-100 bg-gradient-to-r from-rose-50 via-white to-cyan-50 shadow-sm dark:border-slate-800 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900">
        <div class="p-4 md:p-6 lg:p-7">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-brand-600 dark:text-brand-300">Search Jobs</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 md:text-4xl dark:text-white">Find jobs with live suggestions, map search, and dynamic filters</h1>
                    <p class="mt-3 text-sm text-gray-500 md:text-base dark:text-gray-400">Search by role, company, skill, or place. Choose a suggested location to unlock distance filtering and map-driven results.</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                    <span class="rounded-full border border-white/70 bg-white/80 px-3 py-1.5 dark:border-slate-800 dark:bg-slate-900/70">Live suggestions</span>
                    <span class="rounded-full border border-white/70 bg-white/80 px-3 py-1.5 dark:border-slate-800 dark:bg-slate-900/70">Radius search</span>
                    <span class="rounded-full border border-white/70 bg-white/80 px-3 py-1.5 dark:border-slate-800 dark:bg-slate-900/70">Map view</span>
                </div>
            </div>

            <div class="mt-6 rounded-[26px] border border-white/80 bg-white/90 p-4 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-950/70">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Quick Filters</p>
                    <button
                        wire:click="clearFilters"
                        class="inline-flex h-9 items-center rounded-xl border border-gray-200 px-4 text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Clear All
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Distance</label>
                        <select wire:model.defer="radiusKm" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">Any distance</option>
                            @foreach($radiusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($radiusKm && ($locationLatitude === null || $locationLongitude === null))
                            <p class="mt-1 text-[11px] text-amber-600 dark:text-amber-300">Select a mapped location first.</p>
                        @endif
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Job Type</label>
                        <select wire:model.defer="typeFilter" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">All job types</option>
                            @foreach($typeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Work Mode</label>
                        <select wire:model.defer="locationTypeFilter" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">All work modes</option>
                            @foreach($modeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Experience</label>
                        <select wire:model.defer="experienceFilter" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">Any level</option>
                            @foreach($experienceOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Posted</label>
                        <select wire:model.defer="postedWithinDays" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">Any time</option>
                            @foreach($postedOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Sort By</label>
                        <select wire:model.defer="sortBy" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            @foreach($sortOptions as $value => $label)
                                <option value="{{ $value }}" @disabled($value === 'distance_km' && ($locationLatitude === null || $locationLongitude === null))>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Salary Min</label>
                        <input wire:model.defer="salaryMin" type="number" min="0" placeholder="No minimum" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Salary Max</label>
                        <input wire:model.defer="salaryMax" type="number" min="0" placeholder="No maximum" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </div>
                </div>

                @if($locationLatitude === null || $locationLongitude === null)
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Distance sorting and radius filtering require a selected mapped location.</p>
                @endif
            </div>

            <div class="mt-6 rounded-[26px] border border-white/80 bg-white/90 p-4 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-950/70">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(190px,220px)]">
                    <div
                        x-data="asyncSuggestionBox({
                            endpoint: @js(route('jobs.search-suggestions')),
                            initialValue: @js($search),
                        })"
                        @click.outside="close()"
                    >
                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Keywords</label>
                        <div class="relative">
                            <input
                                x-ref="input"
                                wire:model.defer="search"
                                x-model="query"
                                x-on:input="handleInput($event)"
                                x-on:focus="handleFocus()"
                                x-on:keydown.arrow-down.prevent="moveHighlight(1)"
                                x-on:keydown.arrow-up.prevent="moveHighlight(-1)"
                                x-on:keydown.enter.prevent="chooseHighlighted()"
                                x-on:keydown.escape="open = false"
                                type="text"
                                placeholder="Job title, company, skill"
                                class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-4 pr-12 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                            <button
                                x-show="query"
                                x-on:click.prevent="clearQuery()"
                                type="button"
                                class="absolute inset-y-0 right-4 inline-flex items-center text-xs font-semibold text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            >
                                Clear
                            </button>
                            <div x-cloak x-show="open" class="absolute z-30 mt-2 w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900">
                                <template x-for="(suggestion, index) in suggestions" :key="(suggestion.type || 'suggestion') + '-' + (suggestion.label || '') + '-' + index">
                                    <button
                                        type="button"
                                        x-on:click="selectSuggestion(suggestion)"
                                        class="flex w-full items-start justify-between gap-3 border-b border-gray-100 px-4 py-3 text-left last:border-b-0 dark:border-gray-800"
                                        :class="index === highlightedIndex ? 'bg-brand-50 dark:bg-brand-500/10' : ''"
                                    >
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="suggestion.label"></span>
                                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="suggestion.secondary"></span>
                                        </span>
                                        <span class="rounded-full bg-gray-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-800 dark:text-gray-400" x-text="suggestion.type"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Use keyword suggestions for better matches.</p>
                    </div>

                    <div
                        x-data="livewireLocationAutocomplete({
                            endpoint: @js(route('locations.autocomplete')),
                            initialValue: @js($locationSearch),
                            selectMethod: 'selectSearchLocation',
                            clearMethod: 'clearSelectedSearchLocation',
                            browserLocationMethod: 'useBrowserLocation',
                            allowCurrentLocation: true,
                            autoRequestCurrentLocation: true,
                        })"
                        x-init="init()"
                        @click.outside="close()"
                    >
                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Location</label>
                        <div class="relative">
                            <input
                                x-ref="input"
                                wire:model.defer="locationSearch"
                                x-model="query"
                                x-on:input="handleInput($event)"
                                x-on:focus="handleFocus()"
                                x-on:keydown.arrow-down.prevent="moveHighlight(1)"
                                x-on:keydown.arrow-up.prevent="moveHighlight(-1)"
                                x-on:keydown.enter.prevent="chooseHighlighted()"
                                x-on:keydown.escape="open = false"
                                type="text"
                                placeholder="City, region, or country"
                                class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-4 pr-12 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                            <button
                                x-show="query"
                                x-on:click.prevent="clearQuery()"
                                type="button"
                                class="absolute inset-y-0 right-4 inline-flex items-center text-xs font-semibold text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            >
                                Clear
                            </button>

                            <div x-cloak x-show="open" class="absolute z-30 mt-2 w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900">
                                <template x-for="(suggestion, index) in suggestions" :key="(suggestion.place_id || suggestion.description || 'location') + '-' + index">
                                    <button
                                        type="button"
                                        x-on:click="selectSuggestion(suggestion)"
                                        class="flex w-full items-start justify-between gap-3 border-b border-gray-100 px-4 py-3 text-left last:border-b-0 dark:border-gray-800"
                                        :class="index === highlightedIndex ? 'bg-brand-50 dark:bg-brand-500/10' : ''"
                                    >
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="suggestion.main_text || suggestion.description"></span>
                                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="suggestion.secondary_text || suggestion.description"></span>
                                        </span>
                                        <span class="rounded-full bg-gray-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-800 dark:text-gray-400" x-text="suggestion.source"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <p x-show="loading" class="mt-2 text-xs text-gray-500 dark:text-gray-400">Searching locations...</p>
                        <p x-show="feedbackMessage" x-text="feedbackMessage" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></p>

                        <div class="mt-2 flex min-h-8 flex-wrap items-center gap-2">
                            <button
                                type="button"
                                x-on:click="useMyLocation()"
                                x-bind:disabled="usingCurrentLocation"
                                class="inline-flex h-8 items-center rounded-lg border border-gray-200 px-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-700 hover:bg-gray-50 disabled:opacity-60 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                <span x-show="!usingCurrentLocation">Use my location</span>
                                <span x-show="usingCurrentLocation">Locating...</span>
                            </button>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Select a suggestion to unlock radius and distance sorting.</p>
                        </div>
                        <p x-show="geolocationError" x-text="geolocationError" class="mt-2 text-xs text-red-500"></p>
                    </div>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-1 lg:self-end">
                        <button
                            wire:click="applyFilters"
                            wire:loading.attr="disabled"
                            wire:target="applyFilters"
                            class="inline-flex h-10 w-full items-center justify-center rounded-xl bg-gradient-to-r from-brand-500 to-indigo-600 px-4 text-xs font-semibold uppercase tracking-[0.18em] text-white shadow-sm transition hover:from-brand-600 hover:to-indigo-700 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <span wire:loading.remove wire:target="applyFilters">Search Jobs</span>
                            <span wire:loading wire:target="applyFilters">Searching</span>
                        </button>
                        <button
                            wire:click="clearFilters"
                            wire:loading.attr="disabled"
                            wire:target="clearFilters"
                            class="inline-flex h-10 w-full items-center justify-center rounded-xl border border-gray-200 px-4 text-xs font-semibold uppercase tracking-[0.18em] text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-70 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($search || $locationSearch || $radiusKm || $typeFilter || $locationTypeFilter || $experienceFilter || $salaryMin || $salaryMax || $postedWithinDays)
        <div class="flex flex-wrap gap-2">
            @if($search)
                <button type="button" wire:click="$set('search', '')" class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Keyword: {{ $search }} <span class="text-gray-400">x</span>
                </button>
            @endif
            @if($locationSearch)
                <button type="button" wire:click="clearSelectedSearchLocation" class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
                    Location: {{ $locationSearch }} <span class="text-brand-300">x</span>
                </button>
            @endif
            @if($radiusKm)
                <button type="button" wire:click="$set('radiusKm', '')" class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Radius: {{ $radiusKm }} km <span class="text-gray-400">x</span>
                </button>
            @endif
            @if($typeFilter)
                <button type="button" wire:click="$set('typeFilter', '')" class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Type: {{ $typeOptions[$typeFilter] ?? $typeFilter }} <span class="text-gray-400">x</span>
                </button>
            @endif
            @if($locationTypeFilter)
                <button type="button" wire:click="$set('locationTypeFilter', '')" class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Mode: {{ $modeOptions[$locationTypeFilter] ?? ucfirst($locationTypeFilter) }} <span class="text-gray-400">x</span>
                </button>
            @endif
            @if($experienceFilter)
                <button type="button" wire:click="$set('experienceFilter', '')" class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Experience: {{ $experienceFilter }} <span class="text-gray-400">x</span>
                </button>
            @endif
            @if($salaryMin)
                <button type="button" wire:click="$set('salaryMin', '')" class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Min salary: {{ number_format((int) $salaryMin) }} <span class="text-gray-400">x</span>
                </button>
            @endif
            @if($salaryMax)
                <button type="button" wire:click="$set('salaryMax', '')" class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Max salary: {{ number_format((int) $salaryMax) }} <span class="text-gray-400">x</span>
                </button>
            @endif
            @if($postedWithinDays)
                <button type="button" wire:click="$set('postedWithinDays', '')" class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Posted: {{ $postedOptions[$postedWithinDays] ?? ($postedWithinDays . ' days') }} <span class="text-gray-400">x</span>
                </button>
            @endif
        </div>
    @endif
    <div class="grid grid-cols-1 gap-6 2xl:grid-cols-[minmax(0,1fr)_minmax(420px,520px)]">
        <div class="space-y-4 2xl:min-w-0">
            <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-300">Job Results</p>
                        <h2 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $jobs->count() }} roles on this page</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            @if($locationSearch)
                                Searching around <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $locationSearch }}</span>
                            @elseif($search)
                                Matching <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $search }}</span>
                            @else
                                Browse the latest active opportunities.
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                        @if($radiusKm)
                            <span class="rounded-full bg-brand-50 px-3 py-1.5 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">Within {{ $radiusKm }} km</span>
                        @endif
                        @if($locationLatitude !== null && $locationLongitude !== null)
                            <span class="rounded-full bg-gray-100 px-3 py-1.5 text-gray-700 dark:bg-gray-800 dark:text-gray-300">Map enabled</span>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                @php
                    $appliedJobLookup = array_fill_keys($appliedJobIds, true);
                @endphp
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-1">
                @forelse($jobs as $job)
                    @php $alreadyApplied = isset($appliedJobLookup[$job->id]); @endphp
                    <article wire:key="job-card-{{ $job->id }}" class="@class(['h-full rounded-2xl border bg-white p-4 shadow-sm transition md:p-5 dark:bg-slate-950/40','border-brand-300 ring-1 ring-brand-200 dark:border-brand-500/50 dark:ring-brand-500/20' => $selectedMapJob?->id === $job->id,'border-gray-200 hover:border-brand-200 hover:shadow-md dark:border-gray-800 dark:hover:border-brand-500/30' => $selectedMapJob?->id !== $job->id,])">
                        <div class="flex h-full flex-col gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-md bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">{{ $job->experience_level ?? 'General' }}</span>
                                <span class="inline-flex rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $job->job_type)) }}</span>
                                <span class="inline-flex rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ ucfirst($job->location_type) }}</span>
                                @if($job->published_at && $job->published_at->gt(now()->subDays(2)))
                                    <span class="inline-flex rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">New</span>
                                @endif
                                @if($alreadyApplied)
                                    <span class="inline-flex rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">Applied</span>
                                @endif
                            </div>

                            <h2 class="text-xl font-bold text-gray-900 md:text-2xl dark:text-white"><a href="{{ route($jobsShowRoute, $job->slug) }}" class="hover:text-brand-500">{{ $job->title }}</a></h2>
                            <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $job->company->name ?? 'Confidential Company' }}</p>
                            <p class="mt-3 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit(strip_tags((string) $job->description), 180) }}</p>

                            <div class="mt-4 grid grid-cols-1 gap-2 text-sm text-gray-600 sm:grid-cols-3 dark:text-gray-300">
                                <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/5"><span class="block text-[11px] uppercase tracking-[0.16em] text-gray-400">Location</span><span class="mt-1 block font-medium">{{ $job->display_location }}</span></div>
                                <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/5"><span class="block text-[11px] uppercase tracking-[0.16em] text-gray-400">Salary</span><span class="mt-1 block font-medium">{{ $job->salary_range }}</span></div>
                                <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/5"><span class="block text-[11px] uppercase tracking-[0.16em] text-gray-400">Posted</span><span class="mt-1 block font-medium">{{ $job->published_at?->diffForHumans() ?? 'Recently' }}</span></div>
                            </div>

                            @if($job->skills->count())
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($job->skills->take(6) as $skill)
                                        <span class="inline-flex rounded-full border border-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:border-gray-700 dark:text-gray-300">{{ $skill->skill }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if(isset($job->distance_km) && $job->distance_km !== null)
                                <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-300">{{ number_format((float) $job->distance_km, 1) }} km away</p>
                            @endif

                            @if($job->has_precise_location)
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button type="button" wire:click="selectMapJob({{ $job->id }})" class="inline-flex h-9 items-center rounded-lg border border-gray-200 px-3 text-xs font-semibold uppercase tracking-[0.18em] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ $selectedMapJob?->id === $job->id ? 'Showing On Map' : 'Show On Map' }}</button>
                                    <a href="{{ $job->map_open_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 items-center rounded-lg border border-brand-200 px-3 text-xs font-semibold uppercase tracking-[0.18em] text-brand-700 hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-200 dark:hover:bg-brand-500/10">Open Map</a>
                                </div>
                            @endif
                        </div>

                        <div class="mt-auto flex flex-wrap gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                            <a href="{{ route($jobsShowRoute, $job->slug) }}" class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-xs font-semibold uppercase tracking-wider text-white hover:bg-brand-600 sm:w-auto">View Role</a>
                            @if($alreadyApplied)
                                <a href="{{ route('candidate.applications') }}" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-gray-200 px-4 text-xs font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 sm:w-auto">Track Status</a>
                            @elseif($isCandidateUser)
                                <a href="{{ route($applyRoute, $job->slug) }}" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-brand-200 px-4 text-xs font-semibold uppercase tracking-wider text-brand-700 hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-200 dark:hover:bg-brand-500/10 sm:w-auto">Apply Now</a>
                            @elseif(!auth()->check())
                                <a href="{{ route('login') }}" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-gray-200 px-4 text-xs font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 sm:w-auto">Sign In</a>
                            @endif
                        </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-white p-12 text-center lg:col-span-2 2xl:col-span-1 dark:border-gray-700 dark:bg-white/[0.03]">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">No jobs match your filters</h3>
                        <p class="mt-2 text-sm text-gray-500">Try a broader keyword, remove one or two filters, or choose a different location suggestion.</p>
                        <button wire:click="clearFilters" class="mt-6 inline-flex h-10 items-center rounded-lg bg-brand-500 px-5 text-xs font-semibold uppercase tracking-wider text-white hover:bg-brand-600">Reset and Try Again</button>
                    </div>
                @endforelse
                </div>

                <div class="mt-5">{{ $jobs->links() }}</div>
            </section>
        </div>

        <aside class="2xl:sticky 2xl:top-24 2xl:h-[calc(100vh-7rem)] 2xl:self-start">
            @if($selectedMapJob)
                <section class="flex h-full flex-col overflow-hidden rounded-[24px] border border-brand-100 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Focused Map</p>
                        <h2 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">{{ $selectedMapJob->title }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $selectedMapJob->company->name ?? 'Confidential Company' }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $selectedMapJob->display_location }}</span>
                            @if(isset($selectedMapJob->distance_km) && $selectedMapJob->distance_km !== null)
                                <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">{{ number_format((float) $selectedMapJob->distance_km, 1) }} km away</span>
                            @endif
                            @if($locationFromBrowser)
                                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">Distance from your location</span>
                            @endif
                        </div>
                    </div>
                    @php
                        $selectedMapConfig = [
                            'center' => [(float) $selectedMapJob->location_latitude, (float) $selectedMapJob->location_longitude],
                            'zoom' => $locationFromBrowser ? 13 : 12,
                            'startZoom' => 5,
                            'cinematic' => false,
                            'animateKey' => null,
                            'fitToMarkers' => false,
                            'markers' => [
                                [
                                    'lat' => (float) $selectedMapJob->location_latitude,
                                    'lng' => (float) $selectedMapJob->location_longitude,
                                    'kind' => 'job',
                                    'label' => trim($selectedMapJob->title . ' - ' . ($selectedMapJob->company->name ?? 'Confidential Company')),
                                    'openPopup' => true,
                                ],
                            ],
                        ];

                        if ($locationFromBrowser && $locationLatitude !== null && $locationLongitude !== null) {
                            $selectedMapConfig['markers'][] = [
                                'lat' => (float) $locationLatitude,
                                'lng' => (float) $locationLongitude,
                                'kind' => 'user',
                                'label' => 'Your current location',
                                'openPopup' => false,
                            ];
                            $selectedMapConfig['fitToMarkers'] = true;
                        }

                        $selectedMapConfigEncoded = base64_encode(json_encode($selectedMapConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    @endphp
                    <div wire:key="job-board-map-selected-{{ md5($selectedMapConfigEncoded) }}" class="min-h-[420px] w-full flex-1">
                        <div
                            data-job-board-map
                            data-map-config="{{ $selectedMapConfigEncoded }}"
                            x-data="{}"
                            x-init="if (window.mountJobBoardMap) { window.mountJobBoardMap($el) } else { window.addEventListener('job-board-map:ready', () => window.mountJobBoardMap && window.mountJobBoardMap($el), { once: true }) }"
                            wire:ignore
                            class="job-board-map-root h-full min-h-[420px] w-full"
                        ></div>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $selectedMapJob->location_city ?: 'Mapped job location' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">@if($selectedMapJob->location_region){{ $selectedMapJob->location_region }}@endif @if($selectedMapJob->location_country_code)@if($selectedMapJob->location_region) - @endif{{ $selectedMapJob->location_country_code }}@endif</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route($jobsShowRoute, $selectedMapJob->slug) }}" class="inline-flex h-10 items-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">View Role</a>
                            <a href="{{ $selectedMapJob->map_open_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Open Map</a>
                        </div>
                    </div>
                </section>
            @elseif($locationLatitude !== null && $locationLongitude !== null)
                @php
                    $searchMapOpenUrl = sprintf(
                        'https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=%d/%s/%s',
                        $locationLatitude,
                        $locationLongitude,
                        $locationFromBrowser ? 14 : 12,
                        $locationLatitude,
                        $locationLongitude
                    );

                    $searchMapConfig = [
                        'center' => [(float) $locationLatitude, (float) $locationLongitude],
                        'zoom' => $locationFromBrowser ? 14 : 12,
                        'startZoom' => 4,
                        'cinematic' => false,
                        'animateKey' => null,
                        'fitToMarkers' => false,
                        'markers' => [
                            [
                                'lat' => (float) $locationLatitude,
                                'lng' => (float) $locationLongitude,
                                'kind' => $locationFromBrowser ? 'user' : 'job',
                                'label' => $locationFromBrowser ? 'Your current location' : ($locationSearch ?: 'Selected search area'),
                                'openPopup' => false,
                            ],
                        ],
                    ];

                    $searchMapConfigEncoded = base64_encode(json_encode($searchMapConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                @endphp
                <section class="flex h-full flex-col overflow-hidden rounded-[24px] border border-brand-100 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Search Area</p>
                        <h2 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">{{ $locationSearch ?: 'Selected location' }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            @if($locationFromBrowser)
                                The map is centered on your current position with a tighter zoom and marker.
                            @else
                                Select a mapped job from the results to focus the panel on that role.
                            @endif
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if($locationFromBrowser)
                                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">You are here</span>
                            @endif
                            <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">Map marker active</span>
                        </div>
                    </div>
                    <div wire:key="job-board-map-search-{{ md5($searchMapConfigEncoded) }}" class="min-h-[420px] w-full flex-1">
                        <div
                            data-job-board-map
                            data-map-config="{{ $searchMapConfigEncoded }}"
                            x-data="{}"
                            x-init="if (window.mountJobBoardMap) { window.mountJobBoardMap($el) } else { window.addEventListener('job-board-map:ready', () => window.mountJobBoardMap && window.mountJobBoardMap($el), { once: true }) }"
                            wire:ignore
                            class="job-board-map-root h-full min-h-[420px] w-full"
                        ></div>
                    </div>
                    <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                        <a href="{{ $searchMapOpenUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ $locationFromBrowser ? 'Open My Location' : 'Open Search Area' }}</a>
                    </div>
                </section>
            @else
                <section class="flex h-full flex-col overflow-hidden rounded-[24px] border border-brand-100 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Live Map</p>
                                <h2 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">UK jobs overview</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select a location suggestion, use your current location, or click &quot;Show On Map&quot; on a job card to focus this panel.</p>
                            </div>
                            <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">Default View</span>
                        </div>
                    </div>
                    @php
                        $overviewMarkers = $jobs
                            ->getCollection()
                            ->filter(fn ($job) => $job->has_precise_location)
                            ->take(28)
                            ->map(fn ($job) => [
                                'lat' => (float) $job->location_latitude,
                                'lng' => (float) $job->location_longitude,
                                'kind' => 'job',
                                'label' => trim($job->title . ' - ' . ($job->company->name ?? 'Confidential Company')),
                                'openPopup' => false,
                            ])
                            ->values()
                            ->all();

                        $overviewMapConfig = [
                            'center' => [54.7, -2.5],
                            'zoom' => 5,
                            'startZoom' => 4,
                            'cinematic' => false,
                            'animateKey' => null,
                            'fitToMarkers' => !empty($overviewMarkers),
                            'markers' => $overviewMarkers,
                        ];

                        $overviewMapConfigEncoded = base64_encode(json_encode($overviewMapConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    @endphp
                    <div wire:key="job-board-map-overview-{{ md5($overviewMapConfigEncoded) }}" class="min-h-[420px] w-full flex-1">
                        <div
                            data-job-board-map
                            data-map-config="{{ $overviewMapConfigEncoded }}"
                            x-data="{}"
                            x-init="if (window.mountJobBoardMap) { window.mountJobBoardMap($el) } else { window.addEventListener('job-board-map:ready', () => window.mountJobBoardMap && window.mountJobBoardMap($el), { once: true }) }"
                            wire:ignore
                            class="job-board-map-root h-full min-h-[420px] w-full"
                        ></div>
                    </div>
                    <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">Use the location search for city suggestions</span>
                            <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">Pick a radius to filter nearby roles</span>
                            <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">Click any mapped job to focus it here</span>
                        </div>
                    </div>
                </section>
            @endif
        </aside>
    </div>
</div>
