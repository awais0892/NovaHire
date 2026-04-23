@props(['hero' => [], 'stats' => [], 'roles' => [], 'features' => []])

@php
    $fallbackVisual = asset('images/optimized/vecteezy-silhouette-1600.webp');

    $heroPayload = [
        'badge' => (string) data_get($hero, 'badge', 'AI-first Recruiting Platform'),
        'title' => (string) data_get($hero, 'title', 'Hire faster with structured AI screening and role-based workflows'),
        'description' => (string) data_get($hero, 'subtitle', 'From job posting to candidate ranking, NovaHire helps teams evaluate CVs, reduce manual screening, and move qualified talent through the pipeline with confidence.'),
        'primaryCta' => auth()->check()
            ? ['label' => 'Open Dashboard', 'href' => route('dashboard')]
            : [
                'label' => (string) data_get($hero, 'primary_cta_text', 'Start Free'),
                'href' => (string) data_get($hero, 'primary_cta_url', route('register')),
            ],
        'secondaryCta' => auth()->check()
            ? null
            : [
                'label' => (string) data_get($hero, 'secondary_cta_text', 'Browse Jobs'),
                'href' => (string) data_get($hero, 'secondary_cta_url', route('jobs.index')),
            ],
        'stats' => collect($stats)->map(fn ($stat) => [
            'label' => (string) data_get($stat, 'label', 'Metric'),
            'value' => (string) data_get($stat, 'value', '0'),
        ])->values()->all(),
        'roles' => collect($roles)->map(fn ($role) => [
            'title' => (string) data_get($role, 'title', 'Hiring team'),
            'points' => collect((array) data_get($role, 'points', []))
                ->map(fn ($point) => (string) $point)
                ->filter()
                ->values()
                ->all(),
        ])->values()->all(),
        'features' => collect($features)->map(fn ($feature) => [
            'icon' => (string) data_get($feature, 'icon', 'sparkles'),
            'title' => (string) data_get($feature, 'title', 'Structured automation'),
            'desc' => (string) data_get($feature, 'desc', 'Keep hiring work moving with less manual triage.'),
        ])->values()->all(),
        'visualImage' => filled((string) data_get($hero, 'image'))
            ? (string) data_get($hero, 'image')
            : $fallbackVisual,
    ];

    $featuredStats = collect($heroPayload['stats'] ?? [])->take(3);
    $featuredFeatures = collect($heroPayload['features'] ?? [])->take(3);
    $featuredRoles = collect($heroPayload['roles'] ?? [])->take(3);
    $speedMetric = $featuredStats->get(1);
    $title = trim((string) data_get($heroPayload, 'title', 'Hire faster with structured AI screening'));
    $titleWords = preg_split('/\s+/', $title) ?: [];

    if ($title === '') {
        $title1 = 'Recruiting Beyond';
        $title2 = 'The Pipeline';
    } elseif (count($titleWords) <= 4) {
        $title1 = $title;
        $title2 = '';
    } else {
        $targetLength = (int) round(strlen($title) / 2);
        $splitIndex = (int) ceil(count($titleWords) / 2);
        $bestDistance = PHP_INT_MAX;

        for ($index = 2; $index <= count($titleWords) - 2; $index++) {
            $firstSegmentLength = strlen(implode(' ', array_slice($titleWords, 0, $index)));
            $distance = abs($firstSegmentLength - $targetLength);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $splitIndex = $index;
            }
        }

        $title1 = implode(' ', array_slice($titleWords, 0, $splitIndex));
        $title2 = implode(' ', array_slice($titleWords, $splitIndex));
    }

    $supportSignals = [
        ['label' => 'Talent orbit live', 'dot' => 'bg-sky-600 dark:bg-sky-100'],
        ['label' => 'Structured AI signal', 'dot' => 'bg-teal-600 dark:bg-teal-100'],
        ['label' => 'Global hiring lanes', 'dot' => 'bg-blue-600 dark:bg-blue-100'],
    ];

    $featureDots = [
        'bg-sky-600 dark:bg-sky-100',
        'bg-teal-600 dark:bg-teal-100',
        'bg-indigo-600 dark:bg-indigo-100',
    ];

    $roleDots = [
        'bg-slate-900 dark:bg-white',
        'bg-sky-600 dark:bg-sky-100',
        'bg-teal-600 dark:bg-teal-100',
    ];
@endphp

<section class="landing-hero-wrap landing-hero-shell relative isolate overflow-hidden px-4 text-slate-950 dark:text-white sm:px-6 lg:px-8">
    <div class="landing-hero-grid absolute inset-0 opacity-60"></div>
    <div class="landing-hero-noise absolute inset-0"></div>

    <div class="pointer-events-none absolute -left-20 top-16 h-52 w-52 rounded-full bg-sky-300/30 blur-3xl dark:bg-sky-400/12" aria-hidden="true"></div>
    <div class="pointer-events-none absolute right-0 top-0 h-64 w-64 rounded-full bg-teal-200/28 blur-3xl dark:bg-teal-300/10" aria-hidden="true"></div>

    <div class="mx-auto grid min-h-[calc(100svh-82px)] max-w-7xl items-center gap-12 py-12 sm:py-16 lg:grid-cols-[0.88fr_1.12fr] lg:gap-14 lg:py-20">
        <div class="max-w-[39rem]">
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-300/80 bg-white/85 px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.28em] text-slate-700/90 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.05] dark:text-slate-100/72 dark:shadow-none">
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-sky-200 bg-sky-50 dark:border-sky-100/20 dark:bg-sky-100/10" aria-hidden="true">
                    <span class="h-1.5 w-1.5 rounded-full bg-sky-600 dark:bg-sky-100"></span>
                </span>
                {{ data_get($heroPayload, 'badge', 'AI-first Recruiting Platform') }}
            </span>

            <p class="mt-7 font-sora text-xs font-semibold uppercase tracking-[0.55em] text-sky-800/70 dark:text-sky-100/64">
                NovaHire
            </p>

            <h1 class="mt-5 max-w-[11ch] font-sora text-[clamp(3.35rem,6vw,6.45rem)] font-semibold leading-[0.88] tracking-[-0.07em] text-slate-950 dark:text-white">
                <span class="landing-hero-title-line block text-balance">{{ $title1 }}</span>
                @if ($title2 !== '')
                    <span class="landing-hero-title-line landing-hero-title-line--accent block bg-gradient-to-r from-slate-950 via-sky-700 to-teal-600 bg-clip-text text-balance text-transparent dark:from-white dark:via-sky-100 dark:to-teal-200">
                        {{ $title2 }}
                    </span>
                @endif
            </h1>

            <p class="mt-6 max-w-[34rem] text-base leading-relaxed text-slate-600 dark:text-slate-200/88 sm:text-lg">
                {{ data_get($heroPayload, 'description') }}
            </p>

            <div class="mt-6 flex flex-wrap gap-2.5">
                @foreach ($supportSignals as $signal)
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-300/80 bg-white/82 px-3 py-1.5 text-[11px] font-medium text-slate-700/90 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-100/76 dark:shadow-none">
                        <span @class([$signal['dot'], 'h-2 w-2 rounded-full']) aria-hidden="true"></span>
                        <span>{{ $signal['label'] }}</span>
                    </span>
                @endforeach
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-950 bg-slate-950 px-6 py-3 text-sm font-semibold text-white shadow-[0_16px_44px_rgba(15,23,42,0.16)] transition duration-200 hover:-translate-y-0.5 hover:bg-slate-900 hover:shadow-[0_20px_52px_rgba(15,23,42,0.2)] dark:border-white/18 dark:bg-gradient-to-r dark:from-white dark:via-slate-100 dark:to-sky-100 dark:text-slate-950 dark:shadow-[0_16px_44px_rgba(56,189,248,0.22)] dark:hover:shadow-[0_20px_52px_rgba(56,189,248,0.26)]">
                        Open Dashboard
                    </a>
                @else
                    <a href="{{ data_get($heroPayload, 'primaryCta.href', route('register')) }}"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-950 bg-slate-950 px-6 py-3 text-sm font-semibold text-white shadow-[0_16px_44px_rgba(15,23,42,0.16)] transition duration-200 hover:-translate-y-0.5 hover:bg-slate-900 hover:shadow-[0_20px_52px_rgba(15,23,42,0.2)] dark:border-white/18 dark:bg-gradient-to-r dark:from-white dark:via-slate-100 dark:to-sky-100 dark:text-slate-950 dark:shadow-[0_16px_44px_rgba(56,189,248,0.22)] dark:hover:shadow-[0_20px_52px_rgba(56,189,248,0.26)]">
                        {{ data_get($heroPayload, 'primaryCta.label', 'Start Free') }}
                    </a>
                    @if (filled(data_get($heroPayload, 'secondaryCta.href')))
                        <a href="{{ data_get($heroPayload, 'secondaryCta.href', route('jobs.index')) }}"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-300/90 bg-white/92 px-6 py-3 text-sm font-semibold text-slate-800 shadow-sm backdrop-blur-xl transition duration-200 hover:-translate-y-0.5 hover:bg-slate-50 dark:border-white/12 dark:bg-white/[0.05] dark:text-white dark:shadow-[0_14px_40px_rgba(2,6,23,0.2)] dark:hover:bg-white/[0.08]">
                            {{ data_get($heroPayload, 'secondaryCta.label', 'Browse Jobs') }}
                        </a>
                    @endif
                @endauth
            </div>

            <div class="mt-10 grid gap-5 border-t border-slate-300/80 pt-6 dark:border-white/10 sm:grid-cols-3">
                @foreach ($featuredStats as $index => $stat)
                    <div @class([
                        'sm:border-l sm:border-slate-300/80 dark:sm:border-white/10 sm:pl-5' => $index > 0,
                    ])>
                        <p class="font-sora text-[2rem] font-semibold tracking-[-0.05em] text-slate-950 dark:text-white sm:text-[2.2rem]">
                            {{ data_get($stat, 'value') }}
                        </p>
                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-200/56">
                            {{ data_get($stat, 'label') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="relative lg:pl-6">
            <div class="landing-hero-visual relative mx-auto min-h-[36rem] w-full max-w-[48rem] overflow-hidden rounded-[2rem] border border-slate-200/80 p-6 shadow-[0_26px_80px_rgba(15,23,42,0.12)] dark:border-white/10 dark:shadow-[0_36px_120px_rgba(2,6,23,0.55)] sm:min-h-[41rem] sm:p-7 lg:min-h-[45rem] lg:rounded-[2.4rem] lg:p-8">
                <div class="landing-hero-grid absolute inset-0 opacity-25"></div>
                <div class="pointer-events-none absolute left-6 top-6 h-28 w-28 rounded-full bg-sky-200/45 blur-3xl dark:bg-sky-300/10" aria-hidden="true"></div>
                <div class="pointer-events-none absolute bottom-12 right-6 h-32 w-32 rounded-full bg-teal-200/45 blur-3xl dark:bg-teal-300/10" aria-hidden="true"></div>

                <div class="relative z-10 flex h-full flex-col px-2 py-1.5 sm:px-3 sm:py-2.5 lg:px-4 lg:py-3">
                    <div class="relative flex items-center justify-between gap-3">
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-300/80 bg-white/85 px-3.5 py-2 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.04] dark:shadow-none">
                        <span class="h-2 w-2 rounded-full bg-sky-600 dark:bg-sky-100" aria-hidden="true"></span>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-700/90 dark:text-white/72">
                            Talent Orbit Live
                        </span>
                    </div>

                    @if ($speedMetric)
                        <div class="rounded-full border border-slate-300/80 bg-white/85 px-3.5 py-2 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.04] dark:shadow-none">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-teal-600 dark:bg-teal-100" aria-hidden="true"></span>
                                <div>
                                    <p class="font-sora text-sm font-semibold text-slate-950 dark:text-white">
                                        {{ data_get($speedMetric, 'value') }}
                                    </p>
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-white/50">
                                        {{ data_get($speedMetric, 'label') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="relative mt-9 grid gap-7 lg:grid-cols-[minmax(0,18.25rem)_minmax(0,1fr)] lg:items-center lg:gap-8">
                    <div class="relative flex min-h-[21rem] items-center justify-center sm:min-h-[24rem] lg:min-h-[28rem]">
                        <div class="landing-hero-orbit landing-hero-orbit--outer" aria-hidden="true">
                            <span class="landing-hero-orbit-node"></span>
                        </div>
                        <div class="landing-hero-orbit landing-hero-orbit--middle" aria-hidden="true">
                            <span class="landing-hero-orbit-node"></span>
                        </div>
                        <div class="landing-hero-core-glow" aria-hidden="true"></div>

                        <div class="relative z-10 h-[18rem] w-[14.5rem] overflow-hidden rounded-[2rem] border border-slate-200/80 bg-slate-950 shadow-[0_30px_90px_rgba(2,6,23,0.48)] dark:border-white/12 sm:h-[22rem] sm:w-[17rem] lg:h-[24rem] lg:w-[18.25rem]">
                            <img src="{{ data_get($heroPayload, 'visualImage') }}" alt="NovaHire recruiting visual"
                                class="h-full w-full object-cover" fetchpriority="high" loading="eager">
                            <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(2,6,23,0.08)_0%,rgba(2,6,23,0.16)_32%,rgba(2,6,23,0.76)_100%)]"></div>

                            <div class="absolute inset-x-4 top-4">
                                <span class="inline-flex items-center gap-2 rounded-full border border-white/14 bg-slate-950/45 px-3 py-1.5 backdrop-blur-md">
                                    <span class="h-2 w-2 rounded-full bg-teal-100" aria-hidden="true"></span>
                                    <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/72">
                                        Candidate Constellation
                                    </span>
                                </span>
                            </div>

                            <div class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                                <p class="font-sora text-lg font-semibold leading-tight text-white">
                                    Signal over noise.
                                </p>
                                <p class="mt-1 text-xs leading-relaxed text-slate-200/74 sm:text-sm">
                                    Every applicant routed into a cleaner decision system before the team ever loses momentum.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="relative z-10 space-y-5">
                        @foreach ($featuredFeatures as $feature)
                            <div class="rounded-[1.35rem] border border-slate-200/80 bg-white/92 px-5 py-4 shadow-[0_18px_50px_rgba(15,23,42,0.12)] backdrop-blur-xl dark:border-white/12 dark:bg-slate-950/48 dark:shadow-[0_18px_50px_rgba(2,6,23,0.34)] sm:px-5 sm:py-4">
                                <div class="flex items-start gap-2.5">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/[0.05]">
                                        <span @class([data_get($featureDots, $loop->index, $featureDots[0]), 'h-2.5 w-2.5 rounded-full']) aria-hidden="true"></span>
                                    </span>
                                    <div>
                                        <span class="font-sora text-sm font-semibold leading-tight text-slate-950 dark:text-white">
                                            {{ data_get($feature, 'title') }}
                                        </span>
                                        @if (filled(data_get($feature, 'desc')))
                                            <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-200/70">
                                                {{ data_get($feature, 'desc') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="relative mt-7 rounded-[1.45rem] border border-slate-200/80 bg-white/90 px-5 py-4 shadow-[0_18px_50px_rgba(15,23,42,0.12)] backdrop-blur-2xl dark:border-white/10 dark:bg-slate-950/58 dark:shadow-none sm:px-5 sm:py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3 dark:border-white/10">
                        <div>
                            <p class="font-sora text-sm font-semibold text-slate-950 dark:text-white">
                                Mission Control
                            </p>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300/68">
                                Role-specific lanes aligned around one source of hiring truth.
                            </p>
                        </div>

                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50/90 px-3 py-1.5 backdrop-blur-md dark:border-white/10 dark:bg-white/[0.04]">
                            <span class="h-2 w-2 rounded-full bg-teal-600 dark:bg-teal-100" aria-hidden="true"></span>
                            <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-700/90 dark:text-white/70">
                                Structured Signal
                            </span>
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        @foreach ($featuredRoles as $index => $role)
                            <div @class([
                                'sm:border-l sm:border-slate-200 dark:sm:border-white/10 sm:pl-4' => $index > 0,
                            ])>
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50/90 dark:border-white/10 dark:bg-white/[0.04]">
                                        <span @class([data_get($roleDots, $index, $roleDots[0]), 'h-2.5 w-2.5 rounded-full']) aria-hidden="true"></span>
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-950 dark:text-white">
                                            {{ data_get($role, 'title') }}
                                        </p>
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-sky-700/70 dark:text-sky-100/48">
                                            Lane {{ $index + 1 }}
                                        </p>
                                    </div>
                                </div>

                                <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-300/72">
                                    {{ data_get($role, 'points.0', 'Structured collaboration') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</section>
