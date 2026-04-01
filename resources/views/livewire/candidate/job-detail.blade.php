<div class="mx-auto max-w-[1400px] space-y-6 p-4 md:p-6">
    <nav class="flex items-center gap-2 text-xs text-gray-500">
        <a href="{{ route($jobsIndexRoute) }}" class="font-medium hover:text-brand-500">Jobs</a>
        <span>/</span>
        <span class="truncate">{{ $job->title }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-8">
            <section class="rounded-2xl border border-gray-100 bg-white p-4 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-xl font-bold text-brand-700 md:h-14 md:w-14 dark:bg-brand-500/20 dark:text-brand-300">
                            {{ strtoupper(substr($job->company->name ?? 'C', 0, 1)) }}
                        </div>
                        <div>
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-md bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">{{ str_replace('_', ' ', ucfirst($job->job_type)) }}</span>
                                <span class="inline-flex rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ ucfirst($job->location_type) }}</span>
                            </div>
                            <h1 class="text-2xl font-bold text-gray-900 md:text-3xl dark:text-white">{{ $job->title }}</h1>
                            <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-300">{{ $job->company->name }}</p>
                        </div>
                    </div>
                    <button type="button" @click="$store.clip.copy('{{ route($jobsShowRoute, $job->slug) }}')"
                        class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Share
                    </button>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="text-xs text-gray-400">Location</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $job->display_location }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="text-xs text-gray-400">Salary</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $job->salary_range }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="text-xs text-gray-400">Experience</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $job->experience_level ?? 'General' }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="text-xs text-gray-400">Vacancies</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $job->vacancies }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Job Overview</h2>
                <div class="prose prose-sm mt-4 max-w-none text-gray-600 dark:prose-invert dark:text-gray-300">
                    {!! nl2br(e($job->description)) !!}
                </div>
            </section>

            @if($job->requirements)
                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Requirements</h2>
                    <div class="prose prose-sm mt-4 max-w-none text-gray-600 dark:prose-invert dark:text-gray-300">
                        {!! nl2br(e($job->requirements)) !!}
                    </div>
                </section>
            @endif

            @if($job->benefits)
                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Benefits</h2>
                    <div class="prose prose-sm mt-4 max-w-none text-gray-600 dark:prose-invert dark:text-gray-300">
                        {!! nl2br(e($job->benefits)) !!}
                    </div>
                </section>
            @endif

            @if($job->has_precise_location)
                <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 md:flex-row md:items-center md:justify-between dark:border-gray-800">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Location</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $job->display_location }}</p>
                        </div>
                        <a href="{{ $job->map_open_url }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                            Open Map
                        </a>
                    </div>
                    <iframe
                        title="Job location map"
                        src="{{ $job->map_embed_url }}"
                        class="h-[340px] w-full border-0"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                </section>
            @endif

        </div>

        <aside class="space-y-6 lg:col-span-4 lg:sticky lg:top-24">
            <section class="rounded-2xl border border-gray-100 bg-white p-4 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                @if($hasApplied)
                    <div class="space-y-4 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Application Submitted</h3>
                        <a href="{{ route('candidate.applications') }}" class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">Track Status</a>
                    </div>
                @else
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ready to apply?</h3>
                        <p class="text-sm text-gray-500">Submit your profile to {{ $job->company->name }} for review.</p>
                        @if(auth()->check() && auth()->user()->hasRole('candidate'))
                            <a href="{{ route($applyRoute, $job->slug) }}"
                                class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                                Apply Now
                            </a>
                        @elseif(!auth()->check())
                            <a href="{{ route('login') }}"
                                class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                                Sign In To Apply
                            </a>
                        @else
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                                Only candidate accounts can apply for jobs.
                            </div>
                        @endif
                    </div>
                @endif
            </section>

            @if($job->skills->count())
                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Required Skills</h3>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($job->skills as $skill)
                            <span class="inline-flex rounded-md border border-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">
                                {{ $skill->skill }}
                            </span>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($relatedJobs->count())
                <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Related Jobs</h3>
                    <div class="mt-4 space-y-4">
                        @foreach($relatedJobs as $relJob)
                            <a href="{{ route($jobsShowRoute, $relJob->slug) }}" class="block rounded-lg border border-gray-100 p-3 hover:border-brand-300 hover:bg-gray-50 dark:border-gray-700 dark:hover:border-brand-700 dark:hover:bg-white/5">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $relJob->title }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $relJob->display_location }} - {{ $relJob->salary_range }}</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </div>
</div>
