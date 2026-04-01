@php
    $analysis = $application->aiAnalysis;
    $cvStatus = (string) ($application->candidate->cv_status ?? 'pending');
    $shouldPoll = in_array($cvStatus, ['pending', 'processing'], true) || !$analysis;

    $reasoningText = trim((string) ($analysis->reasoning ?? ''));
    $hasReasoning = filled($reasoningText)
        && !in_array($reasoningText, ['""', "''", '[]', '{}', 'null', '-'], true);
    $isProcessing = in_array($cvStatus, ['pending', 'processing'], true);
    $isFailed = $cvStatus === 'failed';
    $isProcessed = $cvStatus === 'processed';

    $hasAnalysis = (bool) (
        $analysis
        && (
            $hasReasoning
            || (int) ($analysis->match_score ?? 0) > 0
            || !empty($analysis->matched_skills ?? [])
            || !empty($analysis->missing_skills ?? [])
            || $isProcessed
        )
    );

    $score = (int) ($analysis->match_score ?? 0);
    $scoreClass = match (true) {
        $score >= 80 => 'text-success-600',
        $score >= 60 => 'text-warning-600',
        default => 'text-error-600',
    };

    $statusTone = match ($application->status) {
        'hired' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
        'offer' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300',
        'interview' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
        'rejected' => 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300',
        default => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
    };

    $fallbackDetected = (bool) (
        $analysis
        && (int) ($analysis->tokens_used ?? 0) === 0
        && str_contains((string) $analysis->reasoning, 'fallback')
    );

    $syncQueueBlocksOpenAi = (string) config('queue.default', 'sync') === 'sync'
        && !filter_var((string) env('AI_ALLOW_OPENAI_WITH_SYNC_QUEUE', false), FILTER_VALIDATE_BOOL)
        && !filter_var((string) env('AI_FORCE_OPENAI', false), FILTER_VALIDATE_BOOL);
@endphp

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6"
    wire:key="analysis-root-{{ $application->id }}"
    @if($shouldPoll) wire:poll.6s @endif>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <a href="{{ route('recruiter.applications') }}" class="btn btn-outline btn-sm mt-1">
                Back
            </a>
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-bold text-gray-900 dark:text-white md:text-3xl">
                    {{ $application->candidate->name ?? 'Candidate' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $application->jobListing->title ?? 'Role' }}
                    <span class="mx-1">|</span>
                    Application #{{ $application->id }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wider {{ $statusTone }}">
                {{ $application->status }}
            </span>
            <button type="button" wire:click="runAnalysisNow" wire:loading.attr="disabled" class="btn btn-primary btn-sm disabled:opacity-50 disabled:cursor-wait">
                <span wire:loading.remove wire:target="runAnalysisNow">Run Analysis Now</span>
                <span wire:loading wire:target="runAnalysisNow">Analyzing...</span>
            </button>
            <button type="button" wire:click="reanalyse" wire:loading.attr="disabled" class="btn btn-outline btn-sm disabled:opacity-50 disabled:cursor-wait">
                <span wire:loading.remove wire:target="reanalyse">Refresh Analysis</span>
                <span wire:loading wire:target="reanalyse">Analyzing...</span>
            </button>
            @if($hasAnalysis)
                <a href="{{ route('recruiter.analysis.report', $application->id) }}" class="btn btn-primary btn-sm">
                    Export Report
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    @if($fallbackDetected)
        <div class="rounded-xl border border-warning-200 bg-warning-50 px-4 py-3 text-xs font-semibold text-warning-700 dark:border-warning-700/30 dark:bg-warning-500/10 dark:text-warning-300">
            Fallback analysis detected.
            @if($syncQueueBlocksOpenAi)
                Local sync queue guard is blocking GPT calls. Set `AI_ALLOW_OPENAI_WITH_SYNC_QUEUE=true` or `AI_FORCE_OPENAI=true`, then click Refresh Analysis.
            @else
                OpenAI request failed or is unavailable. Verify key/network/model settings, then click Refresh Analysis.
            @endif
        </div>
    @endif

    @if(!$hasAnalysis)
        @if($isFailed)
            <section class="card p-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-error-100 text-error-600 dark:bg-error-500/15 dark:text-error-300">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Analysis failed</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">The CV pipeline failed for this application. Retry analysis after verifying queue and OpenAI configuration.</p>
                <div class="mt-6 flex justify-center">
                    <button type="button" wire:click="runAnalysisNow" class="btn btn-primary">Retry Analysis</button>
                </div>
            </section>
        @else
            <section class="card p-8 md:p-10">
                <div class="grid gap-8 lg:grid-cols-2">
                    <div>
                        <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
                            <svg class="h-6 w-6 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                            </svg>
                            AI analysis in progress
                        </h2>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">We are parsing CV content, mapping role skills, and generating interview insights.</p>

                        <div class="mt-6 space-y-3">
                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-white/5">
                                <span class="h-2 w-2 animate-pulse rounded-full bg-brand-500"></span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">CV parsing and entity extraction</span>
                            </div>
                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-white/5">
                                <span class="h-2 w-2 animate-pulse rounded-full bg-brand-500"></span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Skill matching against job requirements</span>
                            </div>
                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-white/5">
                                <span class="h-2 w-2 animate-pulse rounded-full bg-brand-500"></span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Scoring and recommendation synthesis</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col justify-center rounded-2xl border border-brand-200 bg-brand-50 p-6 dark:border-brand-700/30 dark:bg-brand-500/10">
                        <p class="text-xs font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-300">Pipeline Status</p>
                        <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ ucfirst($cvStatus) }}</p>
                        <div class="mt-5 h-2 w-full overflow-hidden rounded-full bg-white dark:bg-white/10">
                            <div class="h-full w-2/3 animate-pulse rounded-full bg-brand-500"></div>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ config('queue.default') === 'sync' ? 'Processing in local sync mode. Use Refresh Analysis after 20-40 seconds.' : 'Auto-refresh every 6 seconds.' }}</p>
                        @if(config('queue.default') === 'sync')
                            <div class="mt-3">
                                <button type="button" wire:click="$refresh" class="btn btn-outline btn-sm">Check Status</button>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif
    @else
        <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="card p-5 md:col-span-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Match Score</p>
                <p class="mt-2 text-4xl font-bold {{ $scoreClass }}">{{ $score }}%</p>
                <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                    <div class="h-full rounded-full bg-brand-500" style="width: {{ max(0, min(100, $score)) }}%"></div>
                </div>
            </div>
            <div class="card p-5 md:col-span-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Recommendation</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ strtoupper(str_replace('_', ' ', $analysis->recommendation ?? 'maybe')) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Based on skills, experience, and role alignment.</p>
            </div>
            <div class="card p-5 md:col-span-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Tokens Used</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $analysis->tokens_used ?? 0 }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Non-zero typically indicates GPT analysis.</p>
            </div>
        </section>

        <section class="card p-6">
            <div class="flex flex-wrap items-center gap-2">
                @foreach([
                    'analysis' => 'Intelligence',
                    'skills' => 'Skills',
                    'questions' => 'Interviewing',
                    'profile' => 'Profile',
                    'notes' => 'Notes',
                ] as $tab => $label)
                    <button type="button" wire:click="setTab('{{ $tab }}')" class="rounded-lg px-4 py-2 text-xs font-semibold uppercase tracking-wider transition {{ $activeTab === $tab ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="mt-6 min-h-[260px]">
                @if($activeTab === 'analysis')
                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700 lg:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Reasoning</p>
                            <p class="mt-2 text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $analysis->reasoning ?: 'No reasoning available yet.' }}</p>
                        </div>
                        <div class="rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-700/30 dark:bg-success-500/10">
                            <p class="text-xs font-semibold uppercase tracking-wider text-success-700 dark:text-success-300">Strengths</p>
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $analysis->strengths ?: 'Not provided.' }}</p>
                        </div>
                        <div class="rounded-xl border border-error-200 bg-error-50 p-4 dark:border-error-700/30 dark:bg-error-500/10">
                            <p class="text-xs font-semibold uppercase tracking-wider text-error-700 dark:text-error-300">Gaps</p>
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $analysis->weaknesses ?: 'Not provided.' }}</p>
                        </div>
                    </div>
                @endif

                @if($activeTab === 'skills')
                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Matched Skills</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse($analysis->matched_skills ?? [] as $skill)
                                    <span class="badge badge-primary">{{ $skill }}</span>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No matched skills listed.</p>
                                @endforelse
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Missing Skills</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse($analysis->missing_skills ?? [] as $skill)
                                    <span class="badge badge-outline">{{ $skill }}</span>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No missing skills listed.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

                @if($activeTab === 'questions')
                    <div class="space-y-3">
                        @forelse($analysis->interview_questions ?? [] as $index => $q)
                            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Q{{ $index + 1 }}{{ !empty($q['type']) ? ' · ' . strtoupper($q['type']) : '' }}</p>
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $q['question'] ?? '-' }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No interview questions generated.</p>
                        @endforelse
                    </div>
                @endif

                @if($activeTab === 'profile')
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Candidate</p>
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $application->candidate->name }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $application->candidate->email }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $application->candidate->phone ?: 'No phone' }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Application</p>
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Status: {{ $application->status }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Submitted: {{ $application->created_at?->format('d M Y H:i') }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Role: {{ $application->jobListing->title ?? '-' }}</p>
                        </div>
                    </div>
                @endif

                @if($activeTab === 'notes')
                    <div>
                        <label class="label">Recruiter Notes</label>
                        <textarea wire:model="notesDraft" rows="8" class="textarea textarea-bordered" placeholder="Internal notes for this candidate..."></textarea>
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-xs text-gray-500 dark:text-gray-400">These notes are internal and not visible to candidates.</p>
                            <button type="button" wire:click="saveNotes" class="btn btn-primary btn-sm">Save Notes</button>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="card p-5">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Move Application Status</p>
            <div class="flex flex-wrap gap-2">
                @foreach(['shortlisted','interview','offer','hired','rejected'] as $s)
                    <button type="button" wire:click="updateStatus('{{ $s }}')" class="btn btn-outline btn-sm {{ $application->status === $s ? '!border-brand-500 !text-brand-600' : '' }}">
                        {{ ucfirst($s) }}
                    </button>
                @endforeach
            </div>
        </section>
    @endif
</div>



