@props(['features' => []])
@php
    $opsDefaults = [
        [
            'title' => 'AI CV Parsing',
            'desc' => 'Extract skills, tenure, and role relevance from every uploaded resume.',
            'src' => '/animations/roles/recruiter-role.mp4',
            'poster' => '/animations/roles/recruiter-role.png',
            'label' => 'Recruiter screening animation',
            'zoom' => 1.08,
            'position' => '52% 44%',
        ],
        [
            'title' => 'Automated Shortlisting',
            'desc' => 'Move strong candidates to recruiter review using score thresholds.',
            'src' => '/animations/roles/manager-role.mp4',
            'poster' => '/animations/roles/manager-role.png',
            'label' => 'Shortlisting checklist animation',
            'zoom' => 1.07,
            'position' => '50% 42%',
        ],
        [
            'title' => 'Collaborative Hiring',
            'desc' => 'Recruiters and managers align using shared notes and decision history.',
            'src' => '/animations/roles/hiring-manager-role.mp4',
            'poster' => '/animations/roles/hiring-manager-role.png',
            'label' => 'Hiring decision animation',
            'zoom' => 1.08,
            'position' => '52% 45%',
        ],
        [
            'title' => 'Interview Coordination',
            'desc' => 'Trigger interview actions as soon as candidate confidence is met.',
            'src' => '/animations/roles/candidate-role.mp4',
            'poster' => '/animations/roles/candidate-role.png',
            'label' => 'Candidate interview readiness animation',
            'zoom' => 1.1,
            'position' => '50% 48%',
        ],
    ];

    $opsFeatures = collect($features)
        ->filter(function ($feature) {
            $title = strtolower((string) data_get($feature, 'title', ''));
            return !str_contains($title, 'billing');
        })
        ->take(4)
        ->values()
        ->map(function ($feature, $index) use ($opsDefaults) {
            return [
                'title' => data_get($feature, 'title', data_get($opsDefaults, $index . '.title', 'Recruiting Step')),
                'desc' => data_get($feature, 'desc', data_get($opsDefaults, $index . '.desc', '')),
                'src' => data_get($opsDefaults, $index . '.src'),
                'poster' => data_get($opsDefaults, $index . '.poster'),
                'label' => data_get($opsDefaults, $index . '.label'),
                'zoom' => (float) data_get($opsDefaults, $index . '.zoom', 1.08),
                'position' => data_get($opsDefaults, $index . '.position', '50% 50%'),
            ];
        });

    if ($opsFeatures->count() < 4) {
        $opsFeatures = collect($opsDefaults);
    }

    $orbitNodes = [
        [
            'src' => '/animations/roles/recruiter-role.mp4',
            'poster' => '/animations/roles/recruiter-role.png',
            'label' => 'Resume screening orbit animation',
            'zoom' => 1.12,
            'position' => '50% 40%',
        ],
        [
            'src' => '/animations/roles/manager-role.mp4',
            'poster' => '/animations/roles/manager-role.png',
            'label' => 'Role controls orbit animation',
            'zoom' => 1.08,
            'position' => '50% 40%',
        ],
        [
            'src' => '/animations/roles/hiring-manager-role.mp4',
            'poster' => '/animations/roles/hiring-manager-role.png',
            'label' => 'Interview review orbit animation',
            'zoom' => 1.12,
            'position' => '54% 45%',
        ],
    ];

    $signalCards = [
        [
            'label' => 'Candidate Match Confidence',
            'context' => 'Model confidence against job criteria',
            'value' => '94%',
            'progress' => 94,
            'src' => '/animations/roles/manager-role.mp4',
            'poster' => '/animations/roles/manager-role.png',
            'media_label' => 'Hiring score signal animation',
            'zoom' => 1.08,
            'position' => '50% 45%',
        ],
        [
            'label' => 'Screening Completion',
            'context' => 'Profiles processed in this hiring cycle',
            'value' => '81%',
            'progress' => 81,
            'src' => '/animations/roles/recruiter-role.mp4',
            'poster' => '/animations/roles/recruiter-role.png',
            'media_label' => 'Screening completion animation',
            'zoom' => 1.1,
            'position' => '52% 43%',
        ],
        [
            'label' => 'Bias-Aware Signal Health',
            'context' => 'Structured scoring consistency',
            'value' => '99.2%',
            'progress' => 99,
            'src' => '/animations/roles/candidate-role.mp4',
            'poster' => '/animations/roles/candidate-role.png',
            'media_label' => 'Candidate health signal animation',
            'zoom' => 1.1,
            'position' => '50% 46%',
        ],
    ];

@endphp

<section id="features" class="nh-section cv-auto overflow-hidden border-y border-slate-200/70 bg-[radial-gradient(900px_circle_at_18%_16%,rgba(56,189,248,0.10),transparent_52%),radial-gradient(700px_circle_at_84%_18%,rgba(16,185,129,0.08),transparent_48%),linear-gradient(180deg,rgba(255,255,255,0.96),rgba(248,250,252,0.93))] dark:border-slate-800/80 dark:bg-[radial-gradient(900px_circle_at_18%_16%,rgba(37,99,235,0.18),transparent_52%),radial-gradient(700px_circle_at_84%_18%,rgba(20,184,166,0.14),transparent_48%),linear-gradient(180deg,rgba(2,6,23,0.96),rgba(2,6,23,0.98))]" data-ai-ops-section>
    <div class="nh-container">
        <div class="mb-10 grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div class="max-w-3xl">
                <p data-animate="reveal" class="nh-reveal nh-eyebrow">Core Platform</p>
                <h2 data-animate="reveal" data-delay="1" class="nh-reveal nh-h2">Built for AI recruiting operations</h2>
                <p data-animate="reveal" data-delay="2" class="nh-reveal nh-lead max-w-2xl">
                    A clear operations layer for CV parsing, ranking decisions, and interview orchestration in one live workflow.
                </p>
            </div>
            <div
                data-animate="reveal" data-delay="3"
                class="hidden h-fit rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-[11px] font-semibold tracking-[0.06em] text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-300 sm:inline-flex sm:items-center sm:gap-2">
                <span class="ai-ops-live-dot"></span>
                Live AI recruiter intelligence
            </div>
        </div>

        <div data-animate="reveal" data-delay="2" class="nh-reveal ai-ops-stage cv-auto-tight">
            <div class="ai-ops-stage-grid">
                <div class="ai-ops-lane ai-ops-lane--workflow">
                    <p class="ai-ops-lane-title">Workflow Engine</p>
                    <p class="ai-ops-lane-copy">From resume ingestion to recruiter handoff.</p>

                    <div class="ai-ops-rail">
                        @foreach($opsFeatures as $feature)
                            <article class="ai-ops-step">
                                <span class="ai-ops-step-icon">
                                    <x-ui.hiring-motion-icon
                                        :src="data_get($feature, 'src')"
                                        :poster="data_get($feature, 'poster')"
                                        :label="data_get($feature, 'label', 'Hiring workflow animation')"
                                        :zoom="data_get($feature, 'zoom', 1.08)"
                                        :position="data_get($feature, 'position', '50% 50%')"
                                        playback-rate="1.02"
                                        :priority="4"
                                        class="h-full w-full" />
                                </span>
                                <div class="min-w-0">
                                    <h3 class="ai-ops-step-title">{{ data_get($feature, 'title') }}</h3>
                                    <p class="ai-ops-step-desc">{{ data_get($feature, 'desc') }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="ai-ops-lane ai-ops-lane--core">
                    <div class="ai-ops-core">
                        <div class="ai-ops-core-ring ai-ops-core-ring--lg"></div>
                        <div class="ai-ops-core-ring ai-ops-core-ring--md"></div>
                        <div class="ai-ops-core-ring ai-ops-core-ring--sm"></div>

                        <x-ui.hiring-motion-icon
                            src="/animations/roles/recruiter-role.mp4"
                            poster="/animations/roles/recruiter-role.png"
                            label="Core CV intelligence animation"
                            position="50% 42%"
                            :zoom="1.1"
                            playback-rate="0.95"
                            :priority="10"
                            class="ai-ops-core-lottie rounded-[1rem]" />

                        <div class="ai-ops-orbit ai-ops-orbit--a">
                            <span class="ai-ops-orbit-node">
                                <x-ui.hiring-motion-icon
                                    :src="data_get($orbitNodes, '0.src')"
                                    :poster="data_get($orbitNodes, '0.poster')"
                                    :label="data_get($orbitNodes, '0.label')"
                                    :zoom="data_get($orbitNodes, '0.zoom', 1.1)"
                                    :position="data_get($orbitNodes, '0.position', '50% 50%')"
                                    playback-rate="0.96"
                                    :priority="1"
                                    class="h-full w-full rounded-[0.8rem]" />
                            </span>
                        </div>
                        <div class="ai-ops-orbit ai-ops-orbit--b">
                            <span class="ai-ops-orbit-node">
                                <x-ui.hiring-motion-icon
                                    :src="data_get($orbitNodes, '1.src')"
                                    :poster="data_get($orbitNodes, '1.poster')"
                                    :label="data_get($orbitNodes, '1.label')"
                                    :zoom="data_get($orbitNodes, '1.zoom', 1.1)"
                                    :position="data_get($orbitNodes, '1.position', '50% 50%')"
                                    playback-rate="0.92"
                                    :priority="1"
                                    class="h-full w-full rounded-[0.8rem]" />
                            </span>
                        </div>
                        <div class="ai-ops-orbit ai-ops-orbit--c">
                            <span class="ai-ops-orbit-node">
                                <x-ui.hiring-motion-icon
                                    :src="data_get($orbitNodes, '2.src')"
                                    :poster="data_get($orbitNodes, '2.poster')"
                                    :label="data_get($orbitNodes, '2.label')"
                                    :zoom="data_get($orbitNodes, '2.zoom', 1.1)"
                                    :position="data_get($orbitNodes, '2.position', '50% 50%')"
                                    playback-rate="0.98"
                                    :priority="1"
                                    class="h-full w-full rounded-[0.8rem]" />
                            </span>
                        </div>

                        <span class="ai-ops-core-chip ai-ops-core-chip--top">CV Parse</span>
                        <span class="ai-ops-core-chip ai-ops-core-chip--left">AI Score</span>
                        <span class="ai-ops-core-chip ai-ops-core-chip--right">Team Review</span>
                    </div>
                </div>

                <div class="ai-ops-lane ai-ops-lane--signals">
                    <p class="ai-ops-lane-title">Live Hiring Signals</p>
                    <p class="ai-ops-lane-copy">Animated operational metrics synced to recruiter flow.</p>

                    <div class="space-y-4">
                        @foreach($signalCards as $signal)
                            <article class="ai-ops-signal" style="--signal-delay: {{ 220 * $loop->index }}ms; --signal-progress: {{ max(0, min(100, (int) data_get($signal, 'progress', 0))) }}%;">
                                <div class="mb-3 flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        <span class="ai-ops-signal-icon">
                                            <x-ui.hiring-motion-icon
                                                :src="data_get($signal, 'src')"
                                                :poster="data_get($signal, 'poster')"
                                                :label="data_get($signal, 'media_label', 'Hiring signal animation')"
                                                :zoom="data_get($signal, 'zoom', 1.08)"
                                                :position="data_get($signal, 'position', '50% 50%')"
                                                playback-rate="1"
                                                :priority="2"
                                                class="h-full w-full rounded-[0.65rem]" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="ai-ops-signal-label">{{ data_get($signal, 'label') }}</p>
                                            <p class="ai-ops-signal-context">{{ data_get($signal, 'context') }}</p>
                                        </div>
                                    </div>
                                    <p class="ai-ops-signal-value">{{ data_get($signal, 'value') }}</p>
                                </div>
                                <div class="ai-ops-signal-meter">
                                    <span></span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
