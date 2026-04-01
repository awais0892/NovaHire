@php
    $jobStatus = strtolower((string) ($job->status ?? 'draft'));
    $jobStatusLabel = str($jobStatus)->replace('_', ' ')->title();
    $jobStatusClass = match ($jobStatus) {
        'active' => 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-300',
        'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-700/60 dark:text-slate-200',
        'paused' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
        'closed' => 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
        default => 'bg-slate-100 text-slate-700 dark:bg-slate-700/60 dark:text-slate-200',
    };

    $hiringTeam = collect([
        [
            'name' => $job->creator?->name,
            'role' => 'Primary Recruiter',
            'badge' => 'R',
            'badge_class' => 'text-brand-600 bg-brand-100 dark:bg-brand-500/20 dark:text-brand-300',
        ],
        [
            'name' => auth()->user()?->name,
            'role' => 'HR Admin',
            'badge' => 'H',
            'badge_class' => 'text-purple-600 bg-purple-100 dark:bg-purple-500/20 dark:text-purple-300',
        ],
    ])->filter(fn(array $member) => filled($member['name']))
        ->unique('name')
        ->values();
@endphp

<div class="space-y-6" x-data="{ tab: 'pipeline' }">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <div class="mb-1 flex items-center gap-3">
                <a href="{{ route('recruiter.jobs.index') }}" class="text-gray-500 transition hover:text-brand-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $job->title }}</h1>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $jobStatusClass }}">
                    {{ $jobStatusLabel }}
                </span>
            </div>
            <div class="ml-8 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    {{ $job->department ?: 'General' }}
                </span>
                <span class="flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    {{ $job->display_location }} ({{ str((string) $job->location_type)->replace('_', ' ')->title() }})
                </span>
                <span class="flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ str((string) $job->job_type)->replace('_', ' ')->title() }}
                </span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('recruiter.jobs.kanban', $job) }}"
                class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-white dark:hover:bg-white/5">
                Open Kanban
            </a>
            <a href="{{ route('recruiter.jobs.edit', $job) }}"
                class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-white dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                    </path>
                </svg>
                Edit Job
            </a>
            <a href="{{ route('recruiter.candidates.create') }}"
                class="flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Candidate
            </a>
        </div>
    </div>

    <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex space-x-8">
            <button @click="tab = 'pipeline'"
                :class="{ 'border-brand-500 text-brand-600 dark:text-brand-400': tab === 'pipeline', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300': tab !== 'pipeline' }"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition">
                Candidate Pipeline
                <span
                    class="ml-2 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $pipelineTotal }}</span>
            </button>
            <button @click="tab = 'details'"
                :class="{ 'border-brand-500 text-brand-600 dark:text-brand-400': tab === 'details', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300': tab !== 'details' }"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition">
                Job Details
            </button>
        </nav>
    </div>

    <div x-show="tab === 'pipeline'" class="pt-4" style="display: none;">
        <div
            class="grid min-h-[500px] grid-cols-1 gap-6 pb-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6">
            @foreach($pipelineColumns as $column)
                <div class="flex min-w-0 flex-col gap-4">
                    <div class="flex items-center justify-between border-b-2 pb-2 {{ $column['accent_class'] }}">
                        <h3 class="font-bold text-gray-800 dark:text-white/90">{{ $column['label'] }}</h3>
                        <span
                            class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $column['count'] }}</span>
                    </div>

                    @forelse($column['applications'] as $application)
                        @php
                            $candidate = $application->candidate;
                            $candidateName = trim((string) ($candidate?->name ?? 'Unknown Candidate'));
                            $candidateEmail = trim((string) ($candidate?->email ?? 'No email available'));
                            $nameParts = preg_split('/\s+/', $candidateName) ?: [];
                            $initials = collect($nameParts)
                                ->filter(fn(string $part) => $part !== '')
                                ->take(2)
                                ->map(fn(string $part) => strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');
                            $initials = $initials !== '' ? $initials : 'C';

                            $score = is_numeric($application->ai_score ?? null) ? (int) $application->ai_score : null;
                            $scoreLabel = is_null($score) ? 'Pending AI' : "{$score}% Match";
                            $scoreBadgeClass = is_null($score)
                                ? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'
                                : ($score >= 80
                                    ? 'bg-green-100 text-green-700 border border-green-200 dark:bg-green-500/20 dark:text-green-300 dark:border-green-500/30'
                                    : ($score >= 60
                                        ? 'bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-500/20 dark:text-amber-300 dark:border-amber-500/30'
                                        : 'bg-red-100 text-red-700 border border-red-200 dark:bg-red-500/20 dark:text-red-300 dark:border-red-500/30'));

                            $statusTagLabel = match ((string) $application->status) {
                                'applied' => 'Pending review',
                                'shortlisted' => 'Shortlisted',
                                'screening' => 'In screening',
                                'interview' => 'Interview stage',
                                'offer' => 'Offer sent',
                                'hired' => 'Hired',
                                'rejected' => 'Rejected',
                                default => str((string) $application->status)->replace('_', ' ')->title(),
                            };
                            $statusTagClass = match ((string) $application->status) {
                                'applied' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                'shortlisted' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300',
                                'screening' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300',
                                'interview' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
                                'offer' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300',
                                'hired' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                                'rejected' => 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
                                default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                            };

                            $upcomingInterview = $application->upcomingInterview;
                            $interviewLabel = null;
                            if ($upcomingInterview?->starts_at) {
                                $displayTimezone = $upcomingInterview->timezone ?: config('app.timezone', 'UTC');
                                try {
                                    $interviewLabel = $upcomingInterview->starts_at
                                        ->copy()
                                        ->timezone($displayTimezone)
                                        ->format('D g:i A');
                                } catch (\Throwable $exception) {
                                    $interviewLabel = $upcomingInterview->starts_at->format('D g:i A');
                                }
                            }
                        @endphp

                        <article
                            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-brand-700">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-600 dark:bg-brand-500/20 dark:text-brand-300">
                                        {{ $initials }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="truncate text-sm font-bold text-gray-800 dark:text-white/90">{{ $candidateName }}</h4>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $candidateEmail }}</p>
                                        <span class="text-xs text-gray-500">{{ $application->created_at?->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between text-xs">
                                <span class="rounded px-2 py-1 font-medium {{ $statusTagClass }}">{{ $statusTagLabel }}</span>
                                <span class="rounded px-2 py-1 font-medium font-mono {{ $scoreBadgeClass }}">{{ $scoreLabel }}</span>
                            </div>

                            @if(($application->status === 'interview') && filled($interviewLabel))
                                <div class="mt-2 text-xs font-medium text-brand-600 dark:text-brand-300">
                                    Interview {{ $interviewLabel }}
                                </div>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 dark:border-gray-800">
                                @if($candidate && is_null($candidate->deleted_at))
                                    <a href="{{ route('recruiter.candidates.show', $candidate->id) }}"
                                        class="rounded-md border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                                        View Candidate
                                    </a>
                                @endif
                                <a href="{{ route('recruiter.analysis', $application->id) }}"
                                    class="rounded-md bg-brand-500 px-2.5 py-1 text-xs font-medium text-white transition hover:bg-brand-600">
                                    AI Analysis
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 px-3 py-6 text-center text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            {{ $column['empty_message'] }}
                        </div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>

    <div x-show="tab === 'details'" class="pt-4" style="display: none;">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Description</h2>
                    <div class="prose max-w-none text-gray-600 dark:prose-invert dark:text-gray-400">
                        {!! nl2br(e($job->description)) !!}
                    </div>
                </div>

                @if($job->requirements)
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Requirements</h2>
                        <div class="prose max-w-none text-gray-600 dark:prose-invert dark:text-gray-400">
                            {!! nl2br(e($job->requirements)) !!}
                        </div>
                    </div>
                @endif

                @if($job->benefits)
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Benefits</h2>
                        <div class="prose max-w-none text-gray-600 dark:prose-invert dark:text-gray-400">
                            {!! nl2br(e($job->benefits)) !!}
                        </div>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Hiring Team</h3>
                    <div class="space-y-4">
                        @forelse($hiringTeam as $member)
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full font-bold {{ $member['badge_class'] }}">
                                    {{ $member['badge'] }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $member['name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $member['role'] }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No hiring team information available yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Candidate Link</h3>
                    <p class="mb-3 text-sm text-gray-500">
                        Share this link directly with candidates to let them apply.
                    </p>
                    <div class="flex">
                        <input type="text" x-ref="shareLink" readonly value="{{ route('jobs.show', $job->slug) }}"
                            class="w-full flex-1 rounded-l-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-600 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <button @click="$store.clip.copy($refs.shareLink.value)"
                            class="rounded-r-lg bg-gray-200 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            aria-label="Copy candidate link">
                            Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
