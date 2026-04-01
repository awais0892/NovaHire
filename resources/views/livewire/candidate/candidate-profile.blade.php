<div class="mx-auto max-w-7xl space-y-6 p-4 text-gray-900 dark:text-gray-100 md:p-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Profile</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Keep your profile updated to improve AI matching quality.</p>
        </div>
        <a href="{{ route('candidate.applications') }}" class="btn btn-outline h-11 rounded-xl px-5">My Applications</a>
    </div>

    @if($saved)
        <div class="alert alert-success">Profile updated successfully.</div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="card border-gray-200/80 bg-white/95 p-6 shadow-sm shadow-gray-900/5 dark:border-gray-800/80 dark:bg-gray-900/70 dark:shadow-black/30 xl:col-span-2">
            <form wire:submit="save" class="space-y-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="label">Full Name</label>
                        <input wire:model="name" type="text" class="input" />
                        @error('name') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Email</label>
                        <input value="{{ $email }}" type="email" class="input" disabled />
                    </div>

                    <div>
                        <label class="label">Phone</label>
                        <input wire:model="phone" type="text" class="input" placeholder="+1 555 000 0000" />
                        @error('phone') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Location</label>
                        <input wire:model="location" type="text" class="input" placeholder="City, Country" />
                        @error('location') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">LinkedIn URL</label>
                        <input wire:model="linkedin" type="url" class="input" placeholder="https://linkedin.com/in/..." />
                        @error('linkedin') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">GitHub URL</label>
                        <input wire:model="github" type="url" class="input" placeholder="https://github.com/..." />
                        @error('github') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="label">Portfolio URL</label>
                        <input wire:model="portfolio" type="url" class="input" placeholder="https://your-portfolio.com" />
                        @error('portfolio') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input wire:model="twoFactorEnabled" type="checkbox" class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <span class="block text-sm font-semibold text-gray-900 dark:text-white">Enable 2-step verification</span>
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Require a 6-digit email OTP after password login for this candidate account.</span>
                            </span>
                        </label>
                        @error('twoFactorEnabled') <p class="mt-2 text-xs text-error-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4 dark:border-gray-800">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Save Profile</span>
                        <span wire:loading>Saving...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <div class="card border-gray-200/80 bg-white/95 p-6 shadow-sm shadow-gray-900/5 dark:border-gray-800/80 dark:bg-gray-900/70 dark:shadow-black/30">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Resume</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Upload your latest CV (PDF, max {{ max(1, (int) floor($maxCvUploadKb / 1024)) }}MB based on server limit).
                </p>

                @if($candidate?->cv_original_name)
                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-white/5">
                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $candidate->cv_original_name }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Status: {{ $candidate->cv_status ?? 'pending' }}</p>
                    </div>
                @endif

                <div class="mt-4"
                    x-data="{ uploadError: '' }"
                    x-on:livewire-upload-error="uploadError = 'Upload failed. Try a smaller PDF and re-upload.'"
                    x-on:livewire-upload-finish="uploadError = ''">
                    <input id="profileCvUpload" type="file" wire:model="newCv" accept=".pdf,application/pdf" class="hidden" />
                    <label for="profileCvUpload" class="flex h-28 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 text-sm text-gray-500 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-500/70 dark:hover:text-brand-300">
                        Choose PDF file
                    </label>
                    @if($selectedCvName !== '')
                        <p class="mt-2 text-xs font-medium text-brand-600 dark:text-brand-300">Selected: {{ $selectedCvName }}</p>
                    @endif
                    @error('newCv') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                    <p x-show="uploadError" x-text="uploadError" class="mt-1 text-xs text-error-500"></p>
                    <div wire:loading wire:target="newCv" class="mt-2 text-xs text-brand-500">Uploading file...</div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        class="btn btn-primary"
                        wire:click="uploadResume"
                        wire:loading.attr="disabled"
                        wire:target="uploadResume,newCv"
                    >
                        <span wire:loading.remove wire:target="uploadResume,newCv">Upload and Parse Resume</span>
                        <span wire:loading wire:target="uploadResume,newCv">Parsing Resume...</span>
                    </button>
                    <p class="text-xs text-gray-500 dark:text-gray-400">This updates your saved resume and fills any empty profile fields from the PDF.</p>
                </div>

                <div wire:loading.flex wire:target="uploadResume" class="mt-4 items-center gap-3 rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 text-sm text-brand-700 dark:border-brand-700/30 dark:bg-brand-500/10 dark:text-brand-300">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle>
                        <path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" class="opacity-90"></path>
                    </svg>
                    <div>
                        <p class="font-semibold">Analyzing CV content...</p>
                        <p class="text-xs opacity-80">Extracting profile links, location, and candidate details.</p>
                    </div>
                </div>

                @if($cvStatusMessage !== '')
                    <div class="mt-4 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-300">
                        {{ $cvStatusMessage }}
                    </div>
                @endif

                @if($cvErrorMessage !== '')
                    <div class="mt-4 rounded-lg border border-error-200 bg-error-50 px-3 py-2 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-300">
                        {{ $cvErrorMessage }}
                    </div>
                @endif

                @if($cvUploaded)
                    <p class="mt-3 text-xs font-medium text-success-600 dark:text-success-400">CV uploaded successfully.</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">We auto-filled any empty profile fields from your CV. Review and adjust before applying.</p>
                @endif
            </div>

            <div class="card border-gray-200/80 bg-white/95 p-6 shadow-sm shadow-gray-900/5 dark:border-gray-800/80 dark:bg-gray-900/70 dark:shadow-black/30">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Metrics</h3>
                <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900/70">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Completion</p>
                        <p class="text-sm font-bold {{ $profileProgress['is_ready'] ? 'text-success-600' : 'text-warning-600' }}">
                            {{ $profileProgress['score'] }}%
                        </p>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-2 rounded-full {{ $profileProgress['is_ready'] ? 'bg-success-500' : 'bg-brand-500' }}"
                            style="width: {{ $profileProgress['score'] }}%;"
                        ></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ $profileProgress['completed'] }}/{{ $profileProgress['total'] }} profile items completed.
                    </p>

                    @if(!empty($profileProgress['missing']))
                        <div class="mt-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Missing</p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                {{ implode(', ', $profileProgress['missing']) }}
                            </p>
                        </div>
                    @endif
                </div>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Applications</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $appCount }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Interviews</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $interviewCount }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">CV Status</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $candidate?->cv_status ?? 'pending' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
