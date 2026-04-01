<div class="mx-auto max-w-[1680px] space-y-6 p-4 md:p-6" @if($isProcessing) wire:poll.8s @endif>
    @php
        $statusOptions = [
            '' => 'All',
            'applied' => 'Applied',
            'screening' => 'Screening',
            'shortlisted' => 'Shortlisted',
            'interview' => 'Interview',
            'offer' => 'Offer',
            'hired' => 'Hired',
            'rejected' => 'Rejected',
        ];

        $sortOptions = [
            'recent' => 'Most recent',
            'oldest' => 'Oldest first',
            'status' => 'Status stage',
        ];
    @endphp

    <section class="rounded-[28px] border border-sky-100 bg-gradient-to-r from-rose-50 via-white to-cyan-50 p-5 shadow-sm md:p-6 dark:border-slate-800 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600 dark:text-brand-300">Candidate Pipeline</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 md:text-4xl dark:text-white">My Applications</h1>
                <p class="mt-2 text-sm text-gray-500 md:text-base dark:text-gray-400">Track progress, monitor interviews, and manage every role from one place.</p>
            </div>
            <a href="{{ route('candidate.jobs.index') }}" class="inline-flex h-11 items-center rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600">
                Browse Jobs
            </a>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-white/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-white/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Active</p>
                <p class="mt-1 text-2xl font-bold text-brand-600">{{ $stats['active'] }}</p>
            </div>
            <div class="rounded-2xl border border-white/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Interviews</p>
                <p class="mt-1 text-2xl font-bold text-warning-600">{{ $stats['interviews'] }}</p>
            </div>
            <div class="rounded-2xl border border-white/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Offers</p>
                <p class="mt-1 text-2xl font-bold text-success-600">{{ $stats['offers'] }}</p>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-300">
            {{ session('error') }}
        </div>
    @endif

    @if($isProcessing)
        <div class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-700 dark:border-brand-700/30 dark:bg-brand-500/10 dark:text-brand-300">
            AI analysis is in progress for your latest CV. Status updates refresh automatically.
        </div>
    @endif

    <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto]">
            <div>
                <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Search</label>
                <input
                    wire:model.live.debounce.350ms="search"
                    type="text"
                    placeholder="Role title, company, or status"
                    class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />
            </div>
            <div>
                <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Sort</label>
                <select wire:model.live="sortBy" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                @if($activeFilterCount > 0)
                    <button wire:click="clearFilters" class="inline-flex h-11 items-center rounded-xl border border-gray-200 px-4 text-xs font-semibold uppercase tracking-[0.18em] text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Clear Filters
                    </button>
                @endif
                <span class="inline-flex h-11 items-center rounded-xl bg-gray-100 px-3 text-xs font-semibold uppercase tracking-[0.14em] text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ $activeFilterCount }} Active
                </span>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
            @foreach($statusOptions as $value => $label)
                @php $count = $value === '' ? $stats['total'] : (int) ($statusCounts[$value] ?? 0); @endphp
                <button
                    wire:click="$set('statusFilter', '{{ $value }}')"
                    class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] transition {{ $statusFilter === $value ? 'border-brand-500 bg-brand-500 text-white' : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                >
                    <span>{{ $label }}</span>
                    <span class="rounded-md bg-black/10 px-1.5 py-0.5 text-[10px] {{ $statusFilter === $value ? 'text-white/90' : 'text-gray-500 dark:text-gray-400' }}">{{ $count }}</span>
                </button>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div wire:loading.delay class="mb-4 rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-medium text-brand-700 dark:border-brand-700/30 dark:bg-brand-500/10 dark:text-brand-300">
            Updating applications...
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            @forelse($applications as $application)
                @php
                    $statusClass = match ($application->status) {
                        'hired' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
                        'offer' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300',
                        'interview' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
                        'rejected' => 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300',
                        default => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
                    };
                    $job = $application->jobListing;
                    $canViewRole = $job && !empty($job->slug);
                @endphp

                <article wire:key="application-card-{{ $application->id }}" class="h-full rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-slate-950/40">
                    <div class="flex h-full flex-col gap-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">{{ $job?->company?->name ?? 'Company' }}</p>
                                <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                                    @if($canViewRole)
                                        <a href="{{ route('candidate.jobs.show', $job->slug) }}" class="hover:text-brand-500">{{ $job->title }}</a>
                                    @else
                                        {{ $job?->title ?? 'Role unavailable' }}
                                    @endif
                                </h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Applied {{ $application->created_at->format('d M Y') }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ $statusClass }}">
                                    {{ $application->status }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 text-sm text-gray-600 sm:grid-cols-3 dark:text-gray-300">
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/5">
                                <span class="block text-[11px] uppercase tracking-[0.16em] text-gray-400">Location</span>
                                <span class="mt-1 block font-medium">{{ $job?->display_location ?? 'Not specified' }}</span>
                            </div>
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/5">
                                <span class="block text-[11px] uppercase tracking-[0.16em] text-gray-400">Salary</span>
                                <span class="mt-1 block font-medium">{{ $job?->salary_range ?? 'Not specified' }}</span>
                            </div>
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/5">
                                <span class="block text-[11px] uppercase tracking-[0.16em] text-gray-400">Updated</span>
                                <span class="mt-1 block font-medium">{{ $application->updated_at?->diffForHumans() ?? 'Recently' }}</span>
                            </div>
                        </div>

                        @if(($interviewsEnabled ?? false) && $application->upcomingInterview)
                            @php
                                $interview = $application->upcomingInterview;
                                $interviewStart = $interview->starts_at?->timezone($interview->timezone);
                                $interviewEnd = $interview->ends_at?->timezone($interview->timezone);
                                $responseBadgeClass = match($interview->candidate_response) {
                                    'accepted' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
                                    'declined' => 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300',
                                    default => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                                };
                            @endphp
                            <div class="rounded-xl border border-warning-200 bg-warning-50 p-3 text-xs dark:border-warning-700/30 dark:bg-warning-500/10">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="font-semibold uppercase tracking-[0.16em] text-warning-700 dark:text-warning-300">Interview Scheduled</p>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] {{ $responseBadgeClass }}">
                                        {{ $interview->candidate_response ? 'Response: ' . $interview->candidate_response : 'Response Pending' }}
                                    </span>
                                </div>

                                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 text-gray-700 dark:text-gray-300">
                                    <p><span class="font-semibold">Date:</span> {{ $interviewStart?->format('d M Y') ?? '-' }}</p>
                                    <p><span class="font-semibold">Time:</span> {{ $interviewStart?->format('H:i') ?? '-' }}@if($interviewEnd) - {{ $interviewEnd->format('H:i') }}@endif ({{ $interview->timezone }})</p>
                                    <p><span class="font-semibold">Mode:</span> {{ ucfirst($interview->mode) }}</p>
                                    <p><span class="font-semibold">Status:</span> {{ ucfirst($interview->status) }}</p>
                                    <p class="sm:col-span-2"><span class="font-semibold">Interviewer:</span> {{ $interview->scheduler?->name ?? 'Recruiting team' }}</p>
                                </div>

                                @if($interview->meeting_link)
                                    <a href="{{ $interview->meeting_link }}" target="_blank" rel="noopener" class="mt-3 inline-flex h-9 items-center rounded-lg bg-brand-500 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-white hover:bg-brand-600">
                                        Join Meeting
                                    </a>
                                @endif

                                @if($interview->location)
                                    <p class="mt-3 text-gray-700 dark:text-gray-300"><span class="font-semibold">Location:</span> {{ $interview->location }}</p>
                                @endif

                                @if($interview->notes)
                                    <p class="mt-2 text-gray-700 dark:text-gray-300"><span class="font-semibold">Notes:</span> {{ $interview->notes }}</p>
                                @endif

                                <a href="{{ route('candidate.interviews.invitation.show', $interview) }}" class="mt-3 inline-flex h-9 items-center rounded-lg border border-warning-300 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-warning-700 hover:bg-warning-100 dark:border-warning-600/40 dark:text-warning-300 dark:hover:bg-warning-500/20">
                                    Open Interview Invitation
                                </a>
                            </div>
                        @elseif(($interviewsEnabled ?? false) && $application->status === 'interview')
                            <div class="rounded-xl border border-warning-200 bg-warning-50 p-3 text-xs text-warning-700 dark:border-warning-700/30 dark:bg-warning-500/10 dark:text-warning-300">
                                Interview stage is active. Detailed schedule will appear once the recruiter confirms it.
                            </div>
                        @endif

                        @php
                            $notesThread = $application->notesThread ?? collect();
                            $emailHistory = $application->emailHistory ?? collect();
                        @endphp

                        @if($notesThread->isNotEmpty())
                            <div class="rounded-xl border border-brand-200 bg-brand-50/70 p-3 text-xs dark:border-brand-700/30 dark:bg-brand-500/10">
                                <p class="font-semibold uppercase tracking-[0.16em] text-brand-700 dark:text-brand-300">Recruiter Notes Timeline</p>
                                <div class="mt-3 space-y-2">
                                    @foreach($notesThread as $note)
                                        <div class="rounded-lg border border-brand-200/70 bg-white/80 px-3 py-2 dark:border-brand-700/40 dark:bg-slate-950/40">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-700 dark:text-brand-300">
                                                    {{ strtoupper((string) ($note->source ?? 'system')) }} · {{ $note->author?->name ?? 'System' }}
                                                </p>
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $note->created_at?->diffForHumans() }}</p>
                                            </div>
                                            @if(filled($note->subject))
                                                <p class="mt-1 text-[11px] font-semibold text-gray-700 dark:text-gray-200">{{ $note->subject }}</p>
                                            @endif
                                            <p class="mt-1 text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::limit((string) $note->content, 360) }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif(filled($application->recruiter_notes))
                            <div class="rounded-xl border border-brand-200 bg-brand-50/70 p-3 text-xs dark:border-brand-700/30 dark:bg-brand-500/10">
                                <p class="font-semibold uppercase tracking-[0.16em] text-brand-700 dark:text-brand-300">AI Recruiter Note</p>
                                <p class="mt-2 text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($application->recruiter_notes, 420) }}</p>
                            </div>
                        @endif

                        @if($emailHistory->isNotEmpty())
                            <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-3 text-xs dark:border-gray-700 dark:bg-white/5">
                                <p class="font-semibold uppercase tracking-[0.16em] text-gray-700 dark:text-gray-200">Email History</p>
                                <div class="mt-3 space-y-2">
                                    @foreach($emailHistory as $mailLog)
                                        @php
                                            $mailStatusClass = match((string) ($mailLog->status ?? 'queued')) {
                                                'sent' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
                                                'failed' => 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300',
                                                default => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
                                            };
                                            $mailTime = $mailLog->sent_at ?? $mailLog->failed_at ?? $mailLog->created_at;
                                        @endphp
                                        <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-slate-950/40">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                                                    {{ $mailLog->subject ?: 'Application update email' }}
                                                </p>
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] {{ $mailStatusClass }}">
                                                    {{ $mailLog->status ?? 'queued' }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                To {{ $mailLog->recipient_email ?? 'candidate email' }}
                                                @if($mailTime)
                                                    · {{ $mailTime->diffForHumans() }}
                                                @endif
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            @foreach($application->timeline ?? [] as $step)
                                @php
                                    $stepClass = match($step['state']) {
                                        'complete' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
                                        'current' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300',
                                        default => 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold {{ $stepClass }}">
                                    {{ $step['label'] }}
                                </span>
                            @endforeach
                        </div>

                        <div class="mt-auto flex flex-wrap gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                            @if($canViewRole)
                                <a href="{{ route('candidate.jobs.show', $job->slug) }}" class="inline-flex h-10 items-center rounded-lg bg-brand-500 px-4 text-xs font-semibold uppercase tracking-[0.16em] text-white hover:bg-brand-600">View Role</a>
                            @endif
                            @if($application->status === 'offer')
                                <button wire:click="acceptOffer({{ $application->id }})" class="inline-flex h-10 items-center rounded-lg bg-success-500 px-4 text-xs font-semibold uppercase tracking-[0.16em] text-white hover:bg-success-600">
                                    Accept Offer
                                </button>
                            @endif
                            @if(in_array($application->status, ['applied', 'screening'], true))
                                <button wire:click="confirmWithdraw({{ $application->id }})" class="inline-flex h-10 items-center rounded-lg border border-error-200 px-4 text-xs font-semibold uppercase tracking-[0.16em] text-error-600 hover:bg-error-50 dark:border-error-500/30 dark:text-error-300 dark:hover:bg-error-500/10">Withdraw</button>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-white p-10 text-center xl:col-span-2 dark:border-gray-700 dark:bg-white/[0.03]">
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-white">No applications found</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Try another status, clear filters, or apply to new jobs.</p>
                    <a href="{{ route('candidate.jobs.index') }}" class="mt-6 inline-flex h-10 items-center rounded-lg bg-brand-500 px-5 text-sm font-semibold text-white hover:bg-brand-600">Find Jobs</a>
                </div>
            @endforelse
        </div>

        @if($applications->hasPages())
            <div class="mt-5">
                {{ $applications->links() }}
            </div>
        @endif
    </section>

    @if($showWithdrawModal)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/60 p-4">
            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-800 dark:bg-slate-950">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Withdraw this application?</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">You can only withdraw applications that are still in applied or screening status.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button wire:click="$set('showWithdrawModal', false)" class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Cancel</button>
                    <button wire:click="withdraw" class="inline-flex h-10 items-center rounded-lg bg-error-500 px-4 text-sm font-semibold text-white hover:bg-error-600">Withdraw</button>
                </div>
            </div>
        </div>
    @endif
</div>
