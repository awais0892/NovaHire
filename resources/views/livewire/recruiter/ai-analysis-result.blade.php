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

    $syncQueueBlocksGemini = (string) config('queue.default', 'sync') === 'sync'
        && !filter_var((string) env('AI_ALLOW_GEMINI_WITH_SYNC_QUEUE', false), FILTER_VALIDATE_BOOL)
        && !filter_var((string) env('AI_FORCE_GEMINI', false), FILTER_VALIDATE_BOOL);

    $resumePreviewUrl = $application->candidate->cv_path
        ? route('recruiter.candidates.resume.download', [
            'candidate' => $application->candidate->id,
            'disposition' => 'inline',
        ])
        : null;
    $resumeDownloadUrl = $application->candidate->cv_path
        ? route('recruiter.candidates.resume.download', ['candidate' => $application->candidate->id])
        : null;

    $rawText = trim((string) ($application->candidate->cv_raw_text ?? ''));
    $rawLines = collect(preg_split('/\r\n|\r|\n/', $rawText) ?: [])
        ->map(fn ($line) => trim((string) $line))
        ->filter()
        ->values();

    $summaryPreview = $rawLines
        ->reject(function (string $line) {
            return str_contains($line, '@')
                || str_starts_with(strtolower($line), 'http')
                || preg_match('/(\+?\d[\d\-\s()]{7,}\d)/', $line);
        })
        ->slice(1, 4)
        ->values();

    if ($summaryPreview->isEmpty()) {
        $summaryPreview = collect([
            trim((string) ($analysis->strengths ?? '')),
            trim((string) ($analysis->reasoning ?? '')),
            'NovaHire is structuring recruiter-ready signals directly from the uploaded CV.',
        ])
            ->filter()
            ->map(fn ($line) => \Illuminate\Support\Str::limit((string) $line, 135))
            ->take(3)
            ->values();
    }

    $experienceCollection = collect($application->candidate->extracted_experience ?? [])
        ->filter(function ($item) {
            return is_array($item)
                && collect($item)->filter(fn ($value) => filled($value))->isNotEmpty();
        })
        ->values();

    $educationCollection = collect($application->candidate->extracted_education ?? [])
        ->filter(function ($item) {
            return is_array($item)
                && collect($item)->filter(fn ($value) => filled($value))->isNotEmpty();
        })
        ->values();

    $experiencePreview = $experienceCollection->take(2)->values();
    $educationPreview = $educationCollection->take(2)->values();
    $skillsPreview = collect($application->candidate->extracted_skills ?? [])
        ->map(fn ($skill) => trim((string) $skill))
        ->filter()
        ->take(14)
        ->values();

    $profileSignals = collect([
        $application->candidate->linkedin,
        $application->candidate->github,
        $application->candidate->portfolio,
        $application->candidate->cv_original_name,
    ])
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->values();

    $primaryHeadline = trim((string) ($experiencePreview->first()['title'] ?? $application->jobListing->title ?? 'Candidate Profile'));
    $resumeFileLabel = trim((string) ($application->candidate->cv_original_name ?? 'uploaded-resume.pdf'));
    $experienceCount = max($experienceCollection->count(), 1);
    $educationCount = max($educationCollection->count(), 1);
    $skillsMappedCount = max($modalMatchedSkills->count(), $skillsPreview->count(), 1);
    $missingSkillsCount = max($modalMissingSkills->count(), 0);
    $completionScoreLabel = $score > 0 ? "{$score}% Match" : 'Screening Complete';

    $scanSectionsPayload = [
        [
            'key' => 'header',
            'label' => 'Header',
            'top' => 6,
            'height' => 9,
            'log' => 'Identity extracted - name, contact verified',
        ],
        [
            'key' => 'summary',
            'label' => 'Summary',
            'top' => 18,
            'height' => 12,
            'log' => 'Professional summary parsed - profile context normalised',
        ],
        [
            'key' => 'experience',
            'label' => 'Experience',
            'top' => 32,
            'height' => 27,
            'log' => $experienceCount . ' position' . ($experienceCount === 1 ? '' : 's') . ' detected - parsing tenure & roles',
        ],
        [
            'key' => 'education',
            'label' => 'Education',
            'top' => 62,
            'height' => 11,
            'log' => $educationCount . ' qualification' . ($educationCount === 1 ? '' : 's') . ' indexed',
        ],
        [
            'key' => 'skills',
            'label' => 'Skills',
            'top' => 76,
            'height' => 9,
            'log' => $skillsMappedCount . ' skills mapped to job requirements',
        ],
        [
            'key' => 'signals',
            'label' => 'Other',
            'top' => 87,
            'height' => 8,
            'log' => 'Final pass - strengths, gaps, and score calibration',
        ],
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6"
    wire:key="analysis-root-{{ $application->id }}"
    x-data="aiAnalysisFlow({
        analysisModalOpen: @entangle('analysisModalOpen').live,
        isProcessingState: @entangle('isProcessingState').live,
        hasAnalysisState: @entangle('hasAnalysisState').live,
        isFailedState: @entangle('isFailedState').live,
        scanSections: @js($scanSectionsPayload),
        completionScoreLabel: @js($completionScoreLabel),
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
            @if($syncQueueBlocksGemini)
                Local sync queue guard is blocking Gemini calls. Set `AI_ALLOW_GEMINI_WITH_SYNC_QUEUE=true` or `AI_FORCE_GEMINI=true`, then click Refresh Analysis.
            @else
                Gemini request failed or is unavailable. Verify key/network/model settings, then click Refresh Analysis.
            @endif
        </div>
    @endif

    <section x-cloak x-show="isLoadingUi" x-transition.opacity.duration.220ms class="fixed inset-0 z-[1085] overflow-y-auto">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(245,197,66,0.12),transparent_32%),radial-gradient(circle_at_top_right,rgba(52,211,153,0.10),transparent_28%),rgba(2,6,23,0.82)] backdrop-blur-xl backdrop-saturate-150"></div>

        <div class="relative z-[1] mx-auto flex min-h-screen w-full max-w-7xl items-center px-4 py-6 lg:px-8">
            <div class="grid w-full gap-5 xl:grid-cols-[minmax(0,1.35fr)_360px]">
                <div class="cv-scan-panel">
                    <div class="flex flex-col gap-3 border-b border-white/10 pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#f5c542]/80">NovaHire CV Analysis</p>
                            <h3 class="mt-1 text-xl font-semibold text-white sm:text-2xl">Candidate resume remains visible while the AI reads section by section.</h3>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2 text-xs text-slate-300">
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5">{{ $application->candidate->name ?? 'Candidate' }}</span>
                            <span class="rounded-full border border-[#f5c542]/25 bg-[#f5c542]/10 px-3 py-1.5 text-[#f7d46f]">{{ $resumeFileLabel }}</span>
                            @if($resumePreviewUrl)
                                <a href="{{ $resumePreviewUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-slate-200 transition hover:border-[#f5c542]/35 hover:text-white">
                                    Open CV
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="cv-scan-stage">
                        <div class="cv-scan-stage__glow"></div>

                        <div class="mx-auto w-full max-w-[640px]">
                            <div class="mb-3 flex items-center justify-between gap-3 text-[11px] uppercase tracking-[0.2em] text-slate-400">
                                <span class="flex items-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#f5c542] shadow-[0_0_10px_rgba(245,197,66,0.85)]"></span>
                                    A4 resume preview
                                </span>
                                <span x-text="scanCycleLabel"></span>
                            </div>

                            <div class="cv-scan-paper-wrap">
                                <div class="cv-scan-paper" :class="{ 'is-complete': scanIsComplete }">
                                    @if($resumePreviewUrl)
                                        <iframe
                                            src="{{ $resumePreviewUrl }}#toolbar=0&navpanes=0&scrollbar=0&view=FitH"
                                            class="cv-scan-frame"
                                            title="Candidate resume preview"
                                            loading="eager"
                                        ></iframe>
                                    @else
                                        @include('livewire.recruiter.partials.ai-analysis-resume-fallback')
                                    @endif

                                    <div class="cv-scan-paper-sheen"></div>
                                    <div class="cv-scan-paper-flash" :class="{ 'is-visible': scanIsComplete }"></div>

                                    <template x-for="(section, index) in scanSections" :key="section.key">
                                        <div class="cv-scan-zone" :class="zoneClasses(index)" :style="zoneStyle(section)">
                                            <div class="cv-scan-zone__beam cv-scan-zone__beam--top"></div>
                                            <div class="cv-scan-zone__beam cv-scan-zone__beam--bottom"></div>
                                            <div class="cv-scan-zone__shimmer"></div>
                                        </div>
                                    </template>

                                    <div class="cv-scan-bar" x-show="showScanBar" x-transition.opacity.duration.150ms :style="`top: ${scanBarTop}%;`"></div>

                                    <div class="cv-scan-complete-badge" :class="{ 'is-visible': scanIsComplete }">
                                        <span class="cv-scan-complete-badge__dot"></span>
                                        <span x-text="`Analysis Complete - ${completionScoreLabel}`"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="space-y-4">
                    <div class="cv-side-card">
                        <div class="flex items-start gap-3">
                            <span class="cv-side-status" :class="scanIsComplete ? 'is-done' : 'is-live'"></span>
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Live analysis state</p>
                                <p class="mt-1 text-sm font-medium text-white" x-text="statusLine"></p>
                                <p class="mt-1 text-xs leading-5 text-slate-400" x-text="currentAnalyzingLine"></p>
                            </div>
                        </div>
                    </div>

                    <div class="cv-side-card">
                        <div class="flex items-center justify-between gap-3 border-b border-white/10 pb-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Analysis log</p>
                                <p class="mt-1 text-sm text-white">Structured extraction feed</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#f5c542]/80">Match engine</p>
                                <p class="mt-1 text-sm text-[#f8d36a]" x-text="scanIsComplete ? completionScoreLabel : 'Calibrating...'"></p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <template x-for="entry in scanVisibleLogs" :key="entry.id">
                                <div class="cv-log-entry">
                                    <span class="cv-log-entry__icon">✦</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="cv-log-entry__label" x-text="entry.label"></p>
                                        <p class="cv-log-entry__text" x-text="entry.text"></p>
                                    </div>
                                    <span class="cv-log-entry__time" x-text="entry.time"></span>
                                </div>
                            </template>

                            <div x-show="!scanIsComplete" class="flex items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-slate-500">
                                <span class="h-2 w-2 animate-pulse rounded-full bg-[#f5c542]"></span>
                                Processing current section
                            </div>
                        </div>
                    </div>

                    <div class="cv-side-card">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Parse progress</p>
                                <p class="mt-1 text-sm text-white">Sequential section sweep</p>
                            </div>
                            <p class="text-lg font-semibold text-[#f5c542]" x-text="`${scanProgressPercent}%`"></p>
                        </div>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/8">
                            <div class="h-full rounded-full bg-[linear-gradient(90deg,rgba(245,197,66,0.38),rgba(245,197,66,0.95))] transition-[width] duration-500 ease-out" :style="`width: ${scanProgressPercent}%;`"></div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <template x-for="(section, index) in scanSections" :key="`${section.key}-chip`">
                                <div class="cv-progress-chip" :class="{ 'is-done': getScanSectionState(index) === 'parsed' || scanIsComplete, 'is-active': getScanSectionState(index) === 'active' }">
                                    <span x-text="section.label"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="cv-side-card">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Signal summary</p>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="cv-metric-tile">
                                <span>Positions</span>
                                <strong>{{ $experienceCount }}</strong>
                            </div>
                            <div class="cv-metric-tile">
                                <span>Qualifications</span>
                                <strong>{{ $educationCount }}</strong>
                            </div>
                            <div class="cv-metric-tile">
                                <span>Mapped skills</span>
                                <strong>{{ $skillsMappedCount }}</strong>
                            </div>
                            <div class="cv-metric-tile" :class="{ 'is-ready': scanIsComplete }">
                                <span>AI score</span>
                                <strong>{{ $score > 0 ? $score . '%' : '...' }}</strong>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 text-xs leading-5 text-slate-300">
                            <span class="font-semibold text-[#f8d36a]">{{ $missingSkillsCount }}</span>
                            role gaps are being contrasted against the uploaded CV while recruiter-facing notes are prepared.
                        </div>
                    </div>
                </aside>
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

        <section class="card overflow-hidden p-0">
            <div class="flex flex-col gap-4 border-b border-gray-200 px-6 py-5 dark:border-gray-800 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Candidate CV</p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">Actual uploaded resume preview</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Review the candidate document alongside AI reasoning and scoring.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-600 dark:border-gray-700 dark:bg-white/5 dark:text-gray-300">
                        {{ $resumeFileLabel }}
                    </span>
                    @if($resumePreviewUrl)
                        <a href="{{ $resumePreviewUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">
                            Open CV
                        </a>
                    @endif
                    @if($resumeDownloadUrl)
                        <a href="{{ $resumeDownloadUrl }}" class="btn btn-primary btn-sm">
                            Download CV
                        </a>
                    @endif
                </div>
            </div>

            <div class="bg-gray-50/70 p-4 dark:bg-slate-950/60 md:p-6">
                <div class="cv-resume-card mx-auto">
                    @if($resumePreviewUrl)
                        <iframe
                            src="{{ $resumePreviewUrl }}#toolbar=1&navpanes=0&view=FitH"
                            class="cv-resume-frame"
                            title="Candidate CV document"
                            loading="lazy"
                        ></iframe>
                    @else
                        <div class="cv-resume-empty">
                            @include('livewire.recruiter.partials.ai-analysis-resume-fallback')
                        </div>
                    @endif
                </div>
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

        .cv-scan-panel {
            position: relative;
            overflow: hidden;
            border-radius: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background:
                radial-gradient(circle at top, rgba(245, 197, 66, 0.12), transparent 34%),
                linear-gradient(180deg, rgba(15, 17, 23, 0.96), rgba(9, 12, 18, 0.98));
            padding: 1.25rem;
            box-shadow: 0 32px 80px rgba(2, 6, 23, 0.52);
        }

        .cv-scan-stage {
            position: relative;
            margin-top: 1rem;
        }

        .cv-scan-stage__glow {
            position: absolute;
            inset: 0;
            border-radius: 1.75rem;
            background:
                radial-gradient(circle at 50% 22%, rgba(245, 197, 66, 0.14), transparent 26%),
                radial-gradient(circle at 52% 78%, rgba(59, 130, 246, 0.1), transparent 36%);
            filter: blur(10px);
            pointer-events: none;
        }

        .cv-scan-paper-wrap {
            position: relative;
            border-radius: 1.75rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: linear-gradient(180deg, rgba(20, 24, 32, 0.92), rgba(11, 14, 20, 0.98));
            padding: 1rem;
        }

        .cv-scan-paper {
            position: relative;
            aspect-ratio: 1 / 1.414;
            overflow: hidden;
            border-radius: 1.35rem;
            background: #fff;
            box-shadow:
                0 0 0 1px rgba(15, 17, 23, 0.08),
                0 14px 42px rgba(15, 17, 23, 0.28);
        }

        .cv-scan-frame {
            display: block;
            height: 100%;
            width: 100%;
            background: #fff;
            border: 0;
        }

        .cv-resume-card {
            overflow: hidden;
            border-radius: 1.25rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: #fff;
            box-shadow:
                0 0 0 1px rgba(15, 23, 42, 0.04),
                0 18px 44px rgba(15, 23, 42, 0.1);
        }

        .cv-resume-frame {
            display: block;
            width: 100%;
            height: min(78vh, 1100px);
            border: 0;
            background: #fff;
        }

        .cv-resume-empty {
            min-height: 720px;
        }

        .cv-scan-paper-sheen {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), transparent 18%, transparent 70%, rgba(255, 239, 169, 0.18));
            mix-blend-mode: screen;
            pointer-events: none;
        }

        .cv-scan-paper-flash {
            position: absolute;
            inset: 0;
            opacity: 0;
            background: rgba(255, 220, 60, 0.16);
            pointer-events: none;
        }

        .cv-scan-paper-flash.is-visible {
            animation: cvScanFlash 720ms ease forwards;
        }

        .cv-scan-zone {
            position: absolute;
            left: 5.5%;
            right: 5.5%;
            overflow: hidden;
            border-left: 2px solid transparent;
            border-radius: 0.75rem;
            background: transparent;
            box-shadow: none;
            transform: scale(1);
            transition:
                background 0.32s ease,
                border-color 0.32s ease,
                box-shadow 0.32s ease,
                transform 0.32s ease;
            pointer-events: none;
        }

        .cv-scan-zone.is-active {
            background: rgba(255, 220, 60, 0.18);
            box-shadow: 0 12px 30px rgba(15, 17, 23, 0.12);
            transform: scale(1.008);
        }

        .cv-scan-zone.is-parsed {
            background: rgba(255, 220, 60, 0.06);
            border-left-color: rgba(255, 200, 0, 0.4);
        }

        .cv-scan-zone__beam {
            position: absolute;
            left: 0;
            right: 0;
            height: 1px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .cv-scan-zone__beam--top {
            top: 0;
            background: rgba(255, 215, 0, 0.55);
        }

        .cv-scan-zone__beam--bottom {
            bottom: 0;
            background: rgba(255, 215, 0, 0.38);
        }

        .cv-scan-zone.is-active .cv-scan-zone__beam {
            opacity: 1;
        }

        .cv-scan-zone__shimmer {
            position: absolute;
            inset: 0 auto 0 0;
            width: 78px;
            transform: translateX(-90px);
            opacity: 0;
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(255, 235, 100, 0.34) 38%,
                rgba(255, 248, 185, 0.25) 50%,
                rgba(255, 235, 100, 0.34) 62%,
                transparent 100%
            );
        }

        .cv-scan-zone.is-active .cv-scan-zone__shimmer {
            animation: cvScanShimmer 1.12s ease-in-out forwards;
        }

        .cv-scan-bar {
            position: absolute;
            left: 0;
            right: 0;
            z-index: 6;
            height: 2px;
            background: rgba(255, 210, 50, 0.72);
            box-shadow:
                0 0 12px rgba(255, 210, 50, 0.68),
                0 0 28px rgba(255, 210, 50, 0.22);
            transition: top 1.1s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease;
            pointer-events: none;
            will-change: top;
        }

        .cv-scan-complete-badge {
            position: absolute;
            left: 50%;
            top: 50%;
            z-index: 8;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transform: translate(-50%, -50%) scale(0.92);
            opacity: 0;
            border: 1px solid rgba(245, 197, 66, 0.55);
            border-radius: 999px;
            background: rgba(26, 26, 46, 0.92);
            padding: 0.8rem 1.25rem;
            color: #f5c542;
            font-size: 0.76rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
            box-shadow: 0 18px 44px rgba(2, 6, 23, 0.36);
            pointer-events: none;
            transition: opacity 0.28s ease, transform 0.28s ease;
        }

        .cv-scan-complete-badge.is-visible {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }

        .cv-scan-complete-badge__dot {
            height: 0.55rem;
            width: 0.55rem;
            flex-shrink: 0;
            border-radius: 999px;
            background: #f5c542;
            box-shadow: 0 0 12px rgba(245, 197, 66, 0.8);
        }

        .cv-side-card {
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.96), rgba(10, 14, 20, 0.98));
            padding: 1rem;
            box-shadow: 0 18px 44px rgba(2, 6, 23, 0.32);
        }

        .cv-side-status {
            margin-top: 0.25rem;
            height: 0.7rem;
            width: 0.7rem;
            flex-shrink: 0;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.45);
        }

        .cv-side-status.is-live {
            background: #f5c542;
            box-shadow: 0 0 14px rgba(245, 197, 66, 0.65);
            animation: cvStatusPulse 1.7s ease-in-out infinite;
        }

        .cv-side-status.is-done {
            background: #34d399;
            box-shadow: 0 0 14px rgba(52, 211, 153, 0.55);
        }

        .cv-log-entry {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 0.75rem;
        }

        .cv-log-entry:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .cv-log-entry__icon {
            color: #f5c542;
            font-size: 0.9rem;
            line-height: 1.2;
        }

        .cv-log-entry__label {
            color: rgba(226, 232, 240, 0.68);
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .cv-log-entry__text {
            margin-top: 0.35rem;
            color: #f6d168;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.78rem;
            line-height: 1.6;
        }

        .cv-log-entry__time {
            color: rgba(148, 163, 184, 0.72);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.68rem;
            white-space: nowrap;
        }

        .cv-progress-chip {
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            padding: 0.55rem 0.75rem;
            color: rgba(203, 213, 225, 0.7);
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: all 0.22s ease;
        }

        .cv-progress-chip.is-active {
            border-color: rgba(245, 197, 66, 0.45);
            background: rgba(245, 197, 66, 0.15);
            color: #f8d36a;
        }

        .cv-progress-chip.is-done {
            border-color: rgba(245, 197, 66, 0.22);
            background: rgba(245, 197, 66, 0.08);
            color: rgba(248, 211, 106, 0.84);
        }

        .cv-metric-tile {
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            padding: 0.85rem 0.9rem;
        }

        .cv-metric-tile span {
            display: block;
            color: rgba(148, 163, 184, 0.76);
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .cv-metric-tile strong {
            display: block;
            margin-top: 0.45rem;
            color: #fff;
            font-size: 1.35rem;
            font-weight: 700;
        }

        .cv-metric-tile.is-ready strong {
            color: #34d399;
        }

        .cv-scan-paper-fallback {
            display: flex;
            height: 100%;
            flex-direction: column;
            gap: 1rem;
            padding: 1.45rem 1.4rem;
            background: linear-gradient(180deg, #fff, #faf7f2);
            color: #1f2937;
        }

        .cv-fallback-header {
            text-align: center;
        }

        .cv-fallback-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .cv-fallback-header p {
            margin-top: 0.25rem;
            color: #6b7280;
            font-size: 0.78rem;
        }

        .cv-fallback-contact {
            margin-top: 0.6rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.35rem 0.75rem;
            color: #6b7280;
            font-size: 0.64rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .cv-fallback-section {
            border-top: 1px solid rgba(203, 213, 225, 0.7);
            padding-top: 0.8rem;
        }

        .cv-fallback-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .cv-fallback-heading {
            color: #94a3b8;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .cv-fallback-entry {
            margin-top: 0.6rem;
        }

        .cv-fallback-entry.compact {
            margin-top: 0.55rem;
        }

        .cv-fallback-entry-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .cv-fallback-entry-head p {
            font-size: 0.72rem;
            font-weight: 700;
            color: #0f172a;
        }

        .cv-fallback-entry-head span {
            color: #94a3b8;
            font-size: 0.58rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .cv-fallback-meta {
            margin-top: 0.18rem;
            color: #64748b;
            font-size: 0.64rem;
        }

        .cv-fallback-copy {
            margin-top: 0.35rem;
            color: #475569;
            font-size: 0.67rem;
            line-height: 1.65;
        }

        .cv-fallback-tags {
            margin-top: 0.65rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .cv-fallback-tags span {
            border-radius: 999px;
            background: rgba(203, 213, 225, 0.52);
            padding: 0.32rem 0.6rem;
            color: #334155;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        @keyframes cvScanShimmer {
            0% {
                transform: translateX(-90px);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            86% {
                opacity: 1;
            }
            100% {
                transform: translateX(calc(100% + 30px));
                opacity: 0;
            }
        }

        @keyframes cvScanFlash {
            0% {
                opacity: 0;
            }
            22% {
                opacity: 0.95;
            }
            100% {
                opacity: 0;
            }
        }

        @keyframes cvStatusPulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.45;
                transform: scale(0.92);
            }
        }

        @media (max-width: 767px) {
            .cv-scan-panel,
            .cv-side-card {
                border-radius: 1.5rem;
            }

            .cv-scan-paper-wrap {
                padding: 0.75rem;
            }

            .cv-scan-paper-fallback {
                padding: 1.1rem 1rem;
            }

            .cv-fallback-grid {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }

            .cv-resume-frame {
                height: 68vh;
            }

            .cv-resume-empty {
                min-height: 560px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .cv-scan-zone,
            .cv-scan-bar,
            .cv-scan-paper-flash,
            .cv-scan-complete-badge,
            .cv-side-status.is-live {
                animation: none !important;
                transition: none !important;
            }
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
                scanSections: Array.isArray(config.scanSections) ? config.scanSections : [],
                completionScoreLabel: config.completionScoreLabel || 'Screening Complete',
                requestInFlight: false,
                forceLoading: false,
                openResultOnComplete: false,
                currentPhaseIndex: 0,
                phaseTimer: null,
                scanTimer: null,
                completionTimer: null,
                logTimers: [],
                scanStartedAt: null,
                scanActiveSection: -1,
                scanParsedThrough: -1,
                scanIsComplete: false,
                scanVisibleLogs: [],
                scanBarTop: 0,
                scanCycle: 1,
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
                        this.beginLoadingUi();
                    }

                    this.$watch('isProcessingState', (value) => {
                        if (value) {
                            this.beginLoadingUi();
                            return;
                        }

                        if (!this.requestInFlight && !this.hasAnalysisState) {
                            this.finishLoading();
                        }
                    });

                    this.$watch('hasAnalysisState', (value) => {
                        if (value) {
                            this.completeLoadingUi();
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
                    if (this.scanIsComplete) {
                        return 'Recruiter-ready scoring package generated from the uploaded CV.';
                    }

                    return this.analysisPhases[this.currentPhaseIndex] ?? this.analysisPhases[0];
                },
                get statusLine() {
                    if (this.scanIsComplete) {
                        return 'Analysis complete';
                    }

                    if (this.scanActiveSection >= 0) {
                        return `Scanning ${this.scanSections[this.scanActiveSection]?.label ?? 'resume'} section`;
                    }

                    return 'Preparing document scan';
                },
                get showScanBar() {
                    return !this.scanIsComplete && this.scanActiveSection >= 0;
                },
                get scanProgressPercent() {
                    if (this.scanSections.length === 0) {
                        return 0;
                    }

                    const parsedCount = this.scanIsComplete
                        ? this.scanSections.length
                        : Math.max(0, this.scanParsedThrough + 1);

                    return Math.min(100, Math.round((parsedCount / this.scanSections.length) * 100));
                },
                get scanCycleLabel() {
                    return this.scanCycle > 1 ? `Validation pass ${this.scanCycle}` : 'Primary parsing pass';
                },
                openModal() {
                    this.analysisModalOpen = true;
                },
                closeModal() {
                    this.analysisModalOpen = false;
                },
                beginLoadingUi({ restart = false } = {}) {
                    this.forceLoading = true;
                    this.startPhaseTicker();

                    if (restart || this.scanStartedAt === null || this.scanIsComplete) {
                        this.resetScan();
                    }

                    if (!this.scanTimer && this.scanVisibleLogs.length === 0 && this.scanActiveSection === -1) {
                        this.scanTimer = window.setTimeout(() => this.runScan(0), 450);
                    }
                },
                async triggerAnalysis(mode = 'analysis') {
                    if (this.requestInFlight) {
                        return;
                    }

                    this.closeModal();
                    this.openResultOnComplete = true;
                    this.currentPhaseIndex = 0;
                    this.requestInFlight = true;
                    this.beginLoadingUi({ restart: true });

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
                            this.openResultOnComplete = false;
                            this.finishLoading();
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
                clearLogTimers() {
                    while (this.logTimers.length) {
                        const timer = this.logTimers.pop();
                        if (timer) {
                            window.clearTimeout(timer);
                        }
                    }
                },
                clearScanTimers({ clearCompletion = true } = {}) {
                    if (this.scanTimer) {
                        window.clearTimeout(this.scanTimer);
                        this.scanTimer = null;
                    }

                    if (clearCompletion && this.completionTimer) {
                        window.clearTimeout(this.completionTimer);
                        this.completionTimer = null;
                    }
                },
                resetScan() {
                    this.clearScanTimers();
                    this.clearLogTimers();
                    this.scanStartedAt = Date.now();
                    this.scanActiveSection = -1;
                    this.scanParsedThrough = -1;
                    this.scanIsComplete = false;
                    this.scanVisibleLogs = [];
                    this.scanBarTop = this.scanSections[0]?.top ?? 0;
                    this.scanCycle = 1;
                },
                finishLoading() {
                    this.forceLoading = false;
                    this.stopPhaseTicker();
                    this.resetScan();
                },
                completeLoadingUi() {
                    this.clearScanTimers({ clearCompletion: false });
                    this.stopPhaseTicker();
                    this.scanActiveSection = -1;
                    this.scanParsedThrough = Math.max(this.scanSections.length - 1, 0);
                    this.scanIsComplete = true;
                    this.scanBarTop = 100;

                    if (this.completionTimer) {
                        return;
                    }

                    this.completionTimer = window.setTimeout(() => {
                        this.forceLoading = false;

                        if (this.openResultOnComplete) {
                            this.openModal();
                            this.openResultOnComplete = false;
                        }

                        this.completionTimer = null;

                        window.setTimeout(() => {
                            if (!this.isLoadingUi) {
                                this.resetScan();
                            }
                        }, 120);
                    }, 950);
                },
                pushTypedLog(section) {
                    const fullText = section?.log || '';
                    const nextEntry = {
                        id: `${section?.key || 'section'}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
                        label: section?.label || 'Section',
                        text: '',
                        time: this.formatElapsed(),
                    };

                    this.scanVisibleLogs = [...this.scanVisibleLogs, nextEntry];
                    const entryIndex = this.scanVisibleLogs.length - 1;
                    let charIndex = 0;

                    const typeNext = () => {
                        const currentLogs = this.scanVisibleLogs.slice();
                        const currentEntry = currentLogs[entryIndex];

                        if (!currentEntry) {
                            return;
                        }

                        currentLogs[entryIndex] = {
                            ...currentEntry,
                            text: fullText.slice(0, charIndex + 1),
                        };
                        this.scanVisibleLogs = currentLogs;
                        charIndex += 1;

                        if (charIndex < fullText.length) {
                            const timer = window.setTimeout(typeNext, 16);
                            this.logTimers.push(timer);
                        }
                    };

                    typeNext();
                },
                formatElapsed() {
                    if (!this.scanStartedAt) {
                        return '0.0s';
                    }

                    return `${((Date.now() - this.scanStartedAt) / 1000).toFixed(1)}s`;
                },
                runScan(current) {
                    if (!this.isLoadingUi && !this.hasAnalysisState) {
                        return;
                    }

                    if (this.scanSections.length === 0) {
                        return;
                    }

                    if (current >= this.scanSections.length) {
                        this.scanActiveSection = -1;
                        this.scanTimer = window.setTimeout(() => {
                            if (!this.isLoadingUi || this.hasAnalysisState) {
                                return;
                            }

                            this.scanCycle += 1;
                            this.runScan(0);
                        }, 320);
                        return;
                    }

                    const section = this.scanSections[current];
                    this.scanActiveSection = current;
                    this.scanBarTop = section.top;

                    if (this.scanCycle === 1 && this.scanVisibleLogs.length <= current) {
                        this.pushTypedLog(section);
                    }

                    window.requestAnimationFrame(() => {
                        window.requestAnimationFrame(() => {
                            this.scanBarTop = Math.min(100, section.top + section.height);
                        });
                    });

                    this.scanTimer = window.setTimeout(() => {
                        if (!this.isLoadingUi && !this.hasAnalysisState) {
                            return;
                        }

                        this.scanParsedThrough = Math.max(this.scanParsedThrough, current);
                        this.scanActiveSection = -1;

                        this.scanTimer = window.setTimeout(() => {
                            this.runScan(current + 1);
                        }, 220);
                    }, 1450);
                },
                getScanSectionState(index) {
                    if (this.scanIsComplete) return 'parsed';
                    if (this.scanActiveSection === index) return 'active';
                    if (index <= this.scanParsedThrough) return 'parsed';
                    return 'idle';
                },
                zoneStyle(section) {
                    return `top: ${section.top}%; height: ${section.height}%;`;
                },
                zoneClasses(index) {
                    const state = this.getScanSectionState(index);

                    return {
                        'is-active': state === 'active',
                        'is-parsed': state === 'parsed',
                    };
                },
            }));
        });
    </script>
@endonce
