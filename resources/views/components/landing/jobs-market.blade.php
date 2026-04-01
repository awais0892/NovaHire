@props(['jobs' => collect()])

@php
    $jobs = collect($jobs)->values();
    $locations = $jobs->pluck('location')->filter()->unique()->sort()->values();
@endphp

<section id="live-jobs" data-jobs-section class="border-b border-slate-200 bg-white/80 py-16 dark:border-slate-800 dark:bg-slate-950/60">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70 sm:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-300">Live Job Market</p>
                    <h2 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">Find roles like top job platforms</h2>
                    <p class="mt-3 max-w-3xl text-sm text-slate-600 dark:text-slate-300">Search and filter current openings instantly, then open full role details and apply in one flow.</p>
                </div>
                <a href="{{ route('jobs.index') }}"
                    class="inline-flex items-center rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                    Browse Full Job Board
                </a>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-4" data-jobs-market>
                <label class="block md:col-span-2">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Search</span>
                    <input type="text" data-filter-search placeholder="Role, company, skill, location"
                        class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Location</span>
                    <select data-filter-location
                        class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                        <option value="">Any location</option>
                        @foreach($locations as $location)
                            <option value="{{ strtolower($location) }}">{{ $location }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Work mode</span>
                    <select data-filter-mode
                        class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                        <option value="">Any mode</option>
                        <option value="onsite">On-site</option>
                        <option value="remote">Remote</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-200 pt-4 text-xs dark:border-slate-700">
                <p class="text-slate-500 dark:text-slate-300">Showing <span data-results-count class="font-semibold text-slate-700 dark:text-slate-100">{{ $jobs->count() }}</span> active roles</p>
                <button type="button" data-filter-reset
                    class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Reset</button>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse($jobs as $job)
                    @php
                        $searchText = strtolower(trim(implode(' ', [
                            (string) $job->title,
                            (string) data_get($job, 'company.name'),
                            (string) $job->location,
                            (string) $job->description,
                            collect($job->skills ?? [])->pluck('skill')->implode(' '),
                        ])));
                    @endphp
                    <article
                        data-job-item
                        data-search="{{ $searchText }}"
                        data-location="{{ strtolower((string) $job->location) }}"
                        data-mode="{{ strtolower((string) $job->location_type) }}"
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/60">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full bg-brand-100 px-2.5 py-1 text-[11px] font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">{{ $job->experience_level ?? 'General' }}</span>
                            <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-700 dark:bg-white/10 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', (string) $job->job_type)) }}</span>
                            <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-700 dark:bg-white/10 dark:text-slate-300">{{ ucfirst((string) $job->location_type) }}</span>
                        </div>
                        <h3 class="mt-3 text-lg font-bold text-slate-900 dark:text-white">{{ $job->title }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ data_get($job, 'company.name', 'Confidential Company') }}</p>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $job->location ?: 'Location not specified' }} . {{ $job->salary_range }}</p>
                        <p class="mt-2 line-clamp-2 text-sm text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit(strip_tags((string) $job->description), 130) }}</p>
                        <div class="mt-4 flex items-center justify-between gap-2">
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $job->published_at?->diffForHumans() ?? 'Recently posted' }}</span>
                            <a href="{{ route('jobs.show', $job->slug) }}"
                                class="inline-flex items-center rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-500">
                                View Role
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border-2 border-dashed border-slate-300 p-10 text-center dark:border-slate-700">
                        <p class="text-sm text-slate-500 dark:text-slate-300">No active jobs are published yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const section = document.querySelector('[data-jobs-section]');
            if (!section) return;
            const root = section.querySelector('[data-jobs-market]');
            if (!root) return;

            const search = root.querySelector('[data-filter-search]');
            const location = root.querySelector('[data-filter-location]');
            const mode = root.querySelector('[data-filter-mode]');
            const reset = root.querySelector('[data-filter-reset]');
            const cards = Array.from(section.querySelectorAll('[data-job-item]'));
            const countEl = section.querySelector('[data-results-count]');

            const apply = () => {
                const term = ((search && search.value) || '').trim().toLowerCase();
                const loc = ((location && location.value) || '').trim().toLowerCase();
                const work = ((mode && mode.value) || '').trim().toLowerCase();
                let visible = 0;

                cards.forEach((card) => {
                    const haystack = card.getAttribute('data-search') || '';
                    const cardLoc = card.getAttribute('data-location') || '';
                    const cardMode = card.getAttribute('data-mode') || '';
                    const pass = (term === '' || haystack.includes(term))
                        && (loc === '' || cardLoc === loc)
                        && (work === '' || cardMode === work);

                    card.classList.toggle('hidden', !pass);
                    if (pass) visible += 1;
                });

                if (countEl) countEl.textContent = String(visible);
            };

            if (search) search.addEventListener('input', apply);
            if (location) location.addEventListener('change', apply);
            if (mode) mode.addEventListener('change', apply);
            if (reset) reset.addEventListener('click', () => {
                if (search) search.value = '';
                if (location) location.value = '';
                if (mode) mode.value = '';
                apply();
            });

            apply();
        });
    </script>
</section>
