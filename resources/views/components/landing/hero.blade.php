@props(['hero' => [], 'stats' => [], 'roles' => [], 'features' => []])

@php
    $fallbackVisual = 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1400&q=80';

    $heroPayload = [
        'badge' => (string) data_get($hero, 'badge', 'AI-first Recruiting Platform'),
        'title' => (string) data_get($hero, 'title', 'Hire faster with structured AI screening and role-based workflows'),
        'description' => (string) data_get($hero, 'subtitle', 'From job posting to candidate ranking, OVA Recruiter App helps teams evaluate CVs, reduce manual screening, and move qualified talent through the pipeline with confidence.'),
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

    $heroPayloadJson = json_encode($heroPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '{}';
@endphp

<div
    data-landing-hero-root
    data-landing-hero-props="{{ $heroPayloadJson }}"
    class="block"
></div>

<noscript>
    <section class="relative overflow-hidden bg-[#030303] px-4 py-18 text-white sm:px-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-88px)] max-w-6xl flex-col justify-center gap-8">
            <div class="max-w-3xl">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70 backdrop-blur-xl">
                    <span class="inline-flex h-2 w-2 rounded-full bg-sky-300"></span>
                    {{ data_get($hero, 'badge', 'AI-first Recruiting Platform') }}
                </span>

                <h1 class="mt-6 text-4xl font-semibold tracking-[-0.05em] text-white sm:text-5xl lg:text-7xl">
                    {{ data_get($hero, 'title') }}
                </h1>

                <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/65 sm:text-lg">
                    {{ data_get($hero, 'subtitle') }}
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-950">
                            Open Dashboard
                        </a>
                    @else
                        <a href="{{ data_get($hero, 'primary_cta_url', route('register')) }}"
                            class="inline-flex items-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-950">
                            {{ data_get($hero, 'primary_cta_text', 'Start Free') }}
                        </a>
                        <a href="{{ data_get($hero, 'secondary_cta_url', route('jobs.index')) }}"
                            class="inline-flex items-center rounded-full border border-white/15 bg-white/6 px-6 py-3 text-sm font-semibold text-white/85 backdrop-blur-xl">
                            {{ data_get($hero, 'secondary_cta_text', 'Browse Jobs') }}
                        </a>
                    @endauth
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                @foreach (collect($stats)->take(3) as $stat)
                    <div class="rounded-3xl border border-white/10 bg-white/5 px-5 py-4 backdrop-blur-xl">
                        <p class="text-2xl font-semibold text-white">{{ data_get($stat, 'value') }}</p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.18em] text-white/45">{{ data_get($stat, 'label') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</noscript>
