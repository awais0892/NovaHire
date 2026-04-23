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

    $hasAnalysis = !$isProcessing && (bool) (
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
    $recommendationLabel = strtoupper(str_replace('_', ' ', $analysis->recommendation ?? 'maybe'));
    $modalMatchedSkills = collect($analysis->matched_skills ?? [])->filter()->take(6)->values();
    $modalMissingSkills = collect($analysis->missing_skills ?? [])->filter()->take(6)->values();

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

    $analysisLottiePayload = [
        'autoplay' => true,
        'loop' => true,
        'speed' => 1,
        'size' => 280,
        'src' => '/animations/ai-loading-model.json',
    ];
    $analysisLottiePayloadJson = json_encode($analysisLottiePayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '{}';
@endphp

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6"
    wire:key="analysis-root-{{ $application->id }}"
    x-data="aiAnalysisFlow({
        analysisModalOpen: @entangle('analysisModalOpen').live,
        isProcessingState: @entangle('isProcessingState').live,
        hasAnalysisState: @entangle('hasAnalysisState').live,
        isFailedState: @entangle('isFailedState').live,
    })"
    x-init="init()"
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
            <button type="button" @click="triggerAnalysis('analysis')" :disabled="requestInFlight || isLoadingUi" class="btn btn-primary btn-sm disabled:opacity-50 disabled:cursor-wait">
                <span x-show="!requestInFlight && !isLoadingUi">Run Analysis Now</span>
                <span x-cloak x-show="requestInFlight || isLoadingUi">Analyzing...</span>
            </button>
            <button type="button" @click="triggerAnalysis('re-analysis')" :disabled="requestInFlight || isLoadingUi" class="btn btn-outline btn-sm disabled:opacity-50 disabled:cursor-wait">
                <span x-show="!requestInFlight && !isLoadingUi">Refresh Analysis</span>
                <span x-cloak x-show="requestInFlight || isLoadingUi">Analyzing...</span>
            </button>
            @if($hasAnalysis)
                <a x-cloak x-show="!isLoadingUi" href="{{ route('recruiter.analysis.report', $application->id) }}" class="btn btn-primary btn-sm">
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

    <section x-cloak x-show="analysisModalOpen && !isLoadingUi && hasAnalysisState && !isFailedState" x-transition.opacity.duration.250ms class="fixed inset-0 z-[1100] flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true" aria-label="AI Analysis Result Modal">
        <div class="absolute inset-0 h-full w-full bg-slate-950/70 backdrop-blur-md" aria-hidden="true"></div>

        <div class="relative z-[1] w-full max-w-4xl overflow-hidden rounded-3xl border border-slate-700/60 bg-slate-950 text-white shadow-[0_24px_80px_rgba(2,6,23,0.65)]">
            <div class="border-b border-white/10 bg-white/[0.03] px-5 py-4 sm:px-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-300">
                            AI Recruiter Engine
                        </p>
                        <h2 class="mt-1 text-lg font-bold text-emerald-300 sm:text-xl">
                            Analysis complete
                        </h2>
                        <p class="mt-1 text-sm text-slate-300">
                            Recruiter-ready summary generated from section-by-section CV analysis.
                        </p>
                    </div>
                    <button type="button" @click="closeModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/15 text-slate-300 transition hover:border-white/40 hover:text-white" aria-label="Close analysis result modal">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="max-h-[78vh] overflow-y-auto px-5 py-6 sm:px-7 sm:py-7">
                <div class="space-y-5">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-200">Match Score</p>
                            <p class="mt-2 text-3xl font-bold text-emerald-100">{{ $score }}%</p>
                        </div>
                        <div class="rounded-xl border border-brand-400/30 bg-brand-500/10 p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-200">Recommendation</p>
                            <p class="mt-2 text-lg font-bold text-white">{{ $recommendationLabel }}</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-300">Tokens Used</p>
                            <p class="mt-2 text-2xl font-bold text-white">{{ $analysis->tokens_used ?? 0 }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Strengths</p>
                            <p class="mt-2 text-sm text-slate-100">{{ $analysis->strengths ?: 'No strengths provided.' }}</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Gaps</p>
                            <p class="mt-2 text-sm text-slate-100">{{ $analysis->weaknesses ?: 'No gaps provided.' }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Top Matched Skills</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse($modalMatchedSkills as $skill)
                                    <span class="rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-100">{{ $skill }}</span>
                                @empty
                                    <span class="text-xs text-slate-300">No matched skills listed.</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Top Missing Skills</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse($modalMissingSkills as $skill)
                                    <span class="rounded-full bg-amber-500/20 px-3 py-1 text-xs font-semibold text-amber-100">{{ $skill }}</span>
                                @empty
                                    <span class="text-xs text-slate-300">No missing skills listed.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Reasoning Snapshot</p>
                        <p class="mt-2 text-sm text-slate-100">{{ \Illuminate\Support\Str::limit($analysis->reasoning ?: 'No reasoning available.', 420) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

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

    <section x-cloak x-show="isLoadingUi" x-transition.opacity.duration.220ms class="fixed inset-0 z-[1085]">
        <div class="absolute inset-0 bg-slate-950/38 backdrop-blur-xl backdrop-saturate-150"></div>

        <div class="relative z-[1] flex h-full w-full items-center justify-center px-4 text-center">
            <div>
                <div
                    wire:ignore
                    data-ai-analysis-lottie-root
                    data-ai-analysis-lottie-props="{{ $analysisLottiePayloadJson }}"
                    class="mx-auto flex min-h-[280px] items-center justify-center"
                ></div>

                <p class="mt-2 text-sm font-semibold text-white/95 sm:text-base" x-text="currentAnalyzingLine"></p>
            </div>
        </div>
    </section>

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
                    <button type="button" @click="triggerAnalysis('analysis')" class="btn btn-primary">Retry Analysis</button>
                </div>
            </section>
        @else
            <section class="card p-8 text-center">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">No analysis available yet</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Run AI analysis to generate role-fit scoring, strengths, gaps, and interview guidance.</p>
                <div class="mt-6 flex justify-center">
                    <button type="button" @click="triggerAnalysis('analysis')" class="btn btn-primary">Run Analysis Now</button>
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
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $recommendationLabel }}</p>
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

@once
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endonce

@once
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('aiAnalysisFlow', (config) => ({
                analysisModalOpen: config.analysisModalOpen,
                isProcessingState: config.isProcessingState,
                hasAnalysisState: config.hasAnalysisState,
                isFailedState: config.isFailedState,
                requestInFlight: false,
                forceLoading: false,
                openResultOnComplete: false,
                currentPhaseIndex: 0,
                phaseTimer: null,
                analysisPhases: [
                    'Analyzing candidate contact and identity details',
                    'Analyzing location and social profile context',
                    'Analyzing professional summary and bio',
                    'Analyzing experience timeline and achievements',
                    'Analyzing skill alignment with role requirements',
                    'Analyzing education and certifications',
                    'Computing role-fit score and recommendation',
                    'Generating strengths, risks, and interview prompts',
                ],
                init() {
                    if (this.isProcessingState) {
                        this.forceLoading = true;
                        this.startPhaseTicker();
                    }

                    this.$watch('isProcessingState', (value) => {
                        if (value) {
                            this.forceLoading = true;
                            this.startPhaseTicker();
                            return;
                        }

                        if (!this.requestInFlight) {
                            this.finishLoading();
                        }
                    });

                    this.$watch('hasAnalysisState', (value) => {
                        if (value) {
                            this.finishLoading();
                            if (this.openResultOnComplete) {
                                this.openModal();
                                this.openResultOnComplete = false;
                            }
                        }
                    });

                    this.$watch('isFailedState', (value) => {
                        if (value) {
                            this.finishLoading();
                            this.openResultOnComplete = false;
                            this.closeModal();
                        }
                    });
                },
                get isLoadingUi() {
                    return this.forceLoading || this.requestInFlight || this.isProcessingState;
                },
                get currentAnalyzingLine() {
                    return this.analysisPhases[this.currentPhaseIndex] ?? this.analysisPhases[0];
                },
                openModal() {
                    this.analysisModalOpen = true;
                },
                closeModal() {
                    this.analysisModalOpen = false;
                },
                async triggerAnalysis(mode = 'analysis') {
                    if (this.requestInFlight) {
                        return;
                    }

                    this.closeModal();
                    this.forceLoading = true;
                    this.openResultOnComplete = true;
                    this.currentPhaseIndex = 0;
                    this.startPhaseTicker();
                    this.requestInFlight = true;

                    try {
                        if (mode === 're-analysis') {
                            await this.$wire.reanalyse();
                        } else {
                            await this.$wire.runAnalysisNow();
                        }
                    } catch (error) {
                        this.forceLoading = false;
                        this.openResultOnComplete = false;
                        this.stopPhaseTicker();
                    } finally {
                        this.requestInFlight = false;

                        if (!this.isProcessingState && !this.hasAnalysisState && !this.isFailedState) {
                            this.forceLoading = false;
                            this.openResultOnComplete = false;
                            this.stopPhaseTicker();
                        }
                    }
                },
                startPhaseTicker() {
                    if (this.phaseTimer) {
                        return;
                    }

                    this.phaseTimer = window.setInterval(() => {
                        if (!this.isLoadingUi) {
                            this.stopPhaseTicker();
                            return;
                        }

                        if (this.analysisPhases.length > 1) {
                            this.currentPhaseIndex =
                                (this.currentPhaseIndex + 1) % this.analysisPhases.length;
                        }
                    }, 1650);
                },
                stopPhaseTicker() {
                    if (this.phaseTimer) {
                        window.clearInterval(this.phaseTimer);
                        this.phaseTimer = null;
                    }
                },
                finishLoading() {
                    this.forceLoading = false;
                    this.stopPhaseTicker();
                },
            }));
        });
    </script>
@endonce
