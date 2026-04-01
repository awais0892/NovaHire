<div class="mx-auto max-w-[1400px] space-y-6 p-4 md:p-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            {{ session('warning') }}
        </div>
    @endif

    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Job Application</p>
                <h1 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">Apply for {{ $job->title }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $job->company->name }}</p>
            </div>
            <a
                href="{{ route($jobShowRoute, $job->slug) }}"
                class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
            >
                Back To Job
            </a>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/5">
                <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400">Location</p>
                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $job->display_location }}</p>
            </div>
            <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/5">
                <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400">Salary</p>
                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $job->salary_range }}</p>
            </div>
            <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/5">
                <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400">Type</p>
                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $job->job_type)) }}</p>
            </div>
            <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/5">
                <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400">Work Mode</p>
                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ ucfirst($job->location_type) }}</p>
            </div>
        </div>
    </section>

    @if($isSubmitted)
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <h2 class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">You have already applied to this role</h2>
            <p class="mt-2 text-sm text-emerald-700/80 dark:text-emerald-200/80">Track progress in your applications dashboard.</p>
            <a href="{{ route('candidate.applications') }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-brand-500 px-5 text-sm font-semibold text-white hover:bg-brand-600">
                View My Applications
            </a>
        </section>
    @elseif(!auth()->check())
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center dark:border-amber-500/30 dark:bg-amber-500/10">
            <h2 class="text-2xl font-bold text-amber-700 dark:text-amber-300">Sign in to continue</h2>
            <p class="mt-2 text-sm text-amber-700/80 dark:text-amber-200/80">Login with your candidate account to submit this application.</p>
            <a href="{{ route('login') }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-brand-500 px-5 text-sm font-semibold text-white hover:bg-brand-600">
                Sign In
            </a>
        </section>
    @else
        <form wire:submit="submitApplication" class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <section class="space-y-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm xl:col-span-8 dark:border-gray-800 dark:bg-white/[0.03]">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Candidate Details</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Keep your details accurate so recruiters can move your application forward quickly.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Full Name</label>
                        <input
                            type="text"
                            wire:model.defer="fullName"
                            class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            placeholder="Your full name"
                        />
                        @error('fullName') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Email</label>
                        <input
                            type="email"
                            wire:model.defer="email"
                            class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            placeholder="you@example.com"
                        />
                        @error('email') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Phone</label>
                        <input
                            type="text"
                            wire:model.defer="phone"
                            class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            placeholder="+44 0000 000000"
                        />
                        @error('phone') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Location</label>
                        <input
                            type="text"
                            wire:model.defer="location"
                            class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            placeholder="City, region, country"
                        />
                        @error('location') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">LinkedIn URL</label>
                        <input
                            type="text"
                            wire:model.defer="linkedin"
                            class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            placeholder="https://linkedin.com/in/username"
                        />
                        @error('linkedin') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">GitHub URL</label>
                        <input
                            type="text"
                            wire:model.defer="github"
                            class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            placeholder="https://github.com/username"
                        />
                        @error('github') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Portfolio URL</label>
                        <input
                            type="text"
                            wire:model.defer="portfolio"
                            class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            placeholder="https://your-portfolio.com"
                        />
                        @error('portfolio') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Cover Letter</label>
                        <textarea
                            wire:model.defer="coverLetter"
                            rows="6"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            placeholder="Introduce yourself and explain your fit for this role."
                        ></textarea>
                        @error('coverLetter') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="space-y-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm xl:col-span-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Resume Upload</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload a PDF resume. This file is used for AI skill matching and recruiter review.</p>
                </div>

                <div
                    class="rounded-xl border border-dashed border-gray-300 p-4 dark:border-gray-700"
                    x-data="{ uploading: false, progress: 0 }"
                    x-on:livewire-upload-start="uploading = true"
                    x-on:livewire-upload-finish="uploading = false"
                    x-on:livewire-upload-error="uploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                >
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">CV / Resume (PDF)</label>
                    <input
                        type="file"
                        wire:model="resume"
                        accept=".pdf"
                        class="block w-full rounded-lg border border-gray-200 bg-white text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-500 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                    />
                    @error('resume') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror

                    @if($resume)
                        <div class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:bg-white/5 dark:text-gray-300">
                            <p class="font-semibold">{{ $resume->getClientOriginalName() }}</p>
                            <p class="mt-0.5">{{ number_format($resume->getSize() / 1024, 2) }} KB</p>
                        </div>
                    @endif

                    <div x-show="uploading" class="mt-3">
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                            <div class="h-2 rounded-full bg-brand-500 transition-all" x-bind:style="'width:' + progress + '%'"></div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="progress + '%'"></p>
                    </div>
                </div>

                @if($job->skills->count())
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Role Skills</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($job->skills as $skill)
                                <span class="inline-flex rounded-full border border-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">
                                    {{ $skill->skill }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="space-y-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="submitApplication,resume"
                        class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span wire:loading.remove wire:target="submitApplication">Apply For This Job</span>
                        <span wire:loading wire:target="submitApplication">Submitting...</span>
                    </button>
                    <a
                        href="{{ route($jobShowRoute, $job->slug) }}"
                        class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </a>
                </div>
            </section>
        </form>
    @endif
</div>
