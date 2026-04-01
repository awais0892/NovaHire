@php
    $title = $title ?? 'AI Applicant Tracking System Product Tour';
    $metaDescription = $metaDescription ?? 'NovaHire is an AI Applicant Tracking System with a Smart CV Parser and Recruitment Automation Suite designed for the full hiring lifecycle.';
@endphp
@extends('layouts.public')

@push('head')
    <meta name="keywords" content="AI Applicant Tracking System, Smart CV Parser, Recruitment Automation Suite, Hiring Lifecycle software, interview automation, recruitment workflow platform">
    <style>
        .product-dot-pattern { background-image: radial-gradient(rgba(15, 23, 42, 0.08) 1px, transparent 1px); background-size: 16px 16px; }
        .dark .product-dot-pattern { background-image: radial-gradient(rgba(148, 163, 184, 0.14) 1px, transparent 1px); }
        .product-hero-shell {
            background:
                radial-gradient(circle at 16% 16%, rgba(20, 184, 166, 0.17), transparent 50%),
                radial-gradient(circle at 86% 0%, rgba(14, 116, 144, 0.2), transparent 44%),
                linear-gradient(142deg, #e2e8f0 0%, #f8fafc 45%, #ecfeff 100%);
        }
        .dark .product-hero-shell {
            background:
                radial-gradient(circle at 20% 22%, rgba(16, 185, 129, 0.16), transparent 52%),
                radial-gradient(circle at 82% 3%, rgba(8, 145, 178, 0.2), transparent 42%),
                linear-gradient(140deg, #020617 0%, #0f172a 45%, #082f49 100%);
        }
        .product-pill-active { background: linear-gradient(95deg, rgb(13 148 136), rgb(14 116 144)); color: white; border-color: transparent; }
        .product-section-divider { border-top: 1px solid rgba(148, 163, 184, 0.28); padding-top: 2.75rem; }
        .dark .product-section-divider { border-color: rgba(51, 65, 85, 0.78); }
        .hero-media-frame { isolation: isolate; }
        .hero-media-frame::before {
            content: "";
            position: absolute;
            inset: 1rem;
            border-radius: 0.8rem;
            background: linear-gradient(120deg, rgba(15, 23, 42, 0.78), rgba(14, 116, 144, 0.35), rgba(15, 23, 42, 0.78));
            background-size: 220% 220%;
            animation: productPulse 1.8s ease-in-out infinite;
            z-index: 0;
        }
        .hero-media-frame[data-loaded='1']::before { opacity: 0; transition: opacity 360ms ease; }
        .hero-media-image { position: relative; z-index: 1; }
        .product-faq-panel { max-height: 0; overflow: hidden; opacity: 0; transition: max-height 260ms ease, opacity 220ms ease; }
        .product-faq-item[data-open='1'] .product-faq-panel { opacity: 1; }
        .product-faq-icon { transition: transform 220ms ease; }
        .product-faq-item[data-open='1'] .product-faq-icon { transform: rotate(45deg); }
        @keyframes productPulse {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
    </style>
@endpush

@section('content')
    @php
        $hero = data_get($content ?? [], 'hero', []);
        $featureRows = ($features ?? collect())->take(8);
        $gallery = collect($mediaGallery ?? [])->values();
        $workflowRows = collect($workflow ?? [])->values();
        $featureGroups = collect($featureExplorer ?? [])->values();
        $firstMedia = $gallery->first();
        $firstFeatureGroup = $featureGroups->first();
        $videoPoster = data_get($gallery->firstWhere('type', 'image'), 'src', asset('images/large-vecteezy_ai-generated-a-silhouette-of-a-person-standing-on-top-of-a_40247032_large.jpg'));
        $initialGalleryImage = ($firstMedia['type'] ?? 'image') === 'image' ? data_get($firstMedia, 'src') : $videoPoster;

        $productFaqs = [
            [
                'q' => 'How does AI screening handle bias in candidate evaluation?',
                'a' => 'NovaHire keeps AI scoring transparent with configurable screening criteria, structured reviewer checkpoints, and human override controls so teams can audit and rebalance decisions across the hiring lifecycle.',
            ],
            [
                'q' => 'Can I integrate with my existing HR tech stack?',
                'a' => 'Yes. The recruitment automation suite is designed to fit into existing hiring workflows, with implementation support for data sync and operational handoffs between recruiting and HR systems.',
            ],
            [
                'q' => 'What does the Smart CV Parser extract from resumes?',
                'a' => 'The Smart CV Parser extracts structured candidate signals such as core skills, role experience, and relevant profile details to speed up shortlist quality and recruiter response times.',
            ],
            [
                'q' => 'Is this AI Applicant Tracking System suitable for growing teams?',
                'a' => 'Yes. NovaHire supports startups and enterprise teams with role-based workflows, collaborative review paths, and plan options that scale as application volume increases.',
            ],
        ];

        $softwareSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'NovaHire',
            'applicationCategory' => 'BusinessApplication',
            'applicationSubCategory' => 'AI Applicant Tracking System',
            'operatingSystem' => 'Web',
            'url' => route('public.product'),
            'description' => 'NovaHire is an AI Applicant Tracking System with a Smart CV Parser and Recruitment Automation Suite that supports the full hiring lifecycle from sourcing to decision.',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
                'description' => 'Free trial available with scalable monthly plans.',
            ],
            'featureList' => collect($featureRows)
                ->pluck('title')
                ->filter()
                ->take(6)
                ->merge([
                    'Smart CV Parser',
                    'Recruitment Automation Suite',
                    'Hiring Lifecycle workflow navigator',
                    'AI-assisted screening and shortlist ranking',
                ])
                ->unique()
                ->values()
                ->all(),
        ];

        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($productFaqs)->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['a'],
                ],
            ])->values()->all(),
        ];
    @endphp

    <article class="space-y-12" id="product-page" data-product-page>
        <header class="public-silk-shell product-hero-shell relative overflow-hidden rounded-3xl border border-slate-200/80 shadow-[0_28px_65px_-45px_rgba(15,23,42,0.55)] dark:border-slate-700/70 dark:shadow-[0_30px_80px_-45px_rgba(2,6,23,0.95)]">
            <x-ui.public-silk tone="product" intensity="1.1" />
            <div class="absolute inset-0 product-dot-pattern opacity-30"></div>
            <div class="absolute -left-24 top-8 h-52 w-52 rounded-full bg-emerald-400/15 blur-3xl"></div>
            <div class="absolute -right-20 top-5 h-56 w-56 rounded-full bg-cyan-400/20 blur-3xl"></div>
            <div class="public-silk-content grid gap-8 p-8 lg:grid-cols-[1.15fr_1fr] lg:p-11">
                <div>
                    <p class="public-silk-chip">Product Platform</p>
                    <h1 class="mt-3 text-4xl font-extrabold leading-tight text-slate-900 dark:text-white lg:text-5xl">AI Applicant Tracking System for the Full Hiring Lifecycle</h1>
                    <p class="mt-4 max-w-3xl text-lg text-slate-700 dark:text-slate-200">NovaHire combines a Smart CV Parser and Recruitment Automation Suite so recruiting teams can publish jobs, score applicants, schedule interviews, and close hires in one connected workflow.</p>
                    @if(filled(data_get($hero, 'title')))
                        <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-300">{{ data_get($hero, 'title') }}</p>
                    @endif

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('public.pricing') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-500 hover:shadow-lg">View Pricing</a>
                        <a href="{{ route('register') }}" class="rounded-lg border border-slate-300 bg-white/90 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-white hover:shadow-lg dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:bg-slate-900">Start Free</a>
                        <a href="{{ route('public.contact') }}" class="rounded-lg border border-slate-300 bg-white/90 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-white hover:shadow-lg dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:bg-slate-900">Request Demo</a>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:max-w-xl">
                        <div class="group rounded-xl border border-slate-200 bg-white/85 p-4 shadow-sm backdrop-blur transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-700 dark:bg-slate-900/70">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-300">Active Jobs</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">{{ number_format($platformMetrics['active_jobs'] ?? 0) }}</p>
                        </div>
                        <div class="group rounded-xl border border-slate-200 bg-white/85 p-4 shadow-sm backdrop-blur transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-700 dark:bg-slate-900/70">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-300">Applications</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">{{ number_format($platformMetrics['applications'] ?? 0) }}</p>
                        </div>
                    </div>
                </div>

                <div data-loaded="0" class="hero-media-frame relative overflow-hidden rounded-2xl border border-white/30 bg-slate-950/90 p-4 shadow-2xl dark:border-slate-700/80">
                    <img src="{{ asset('images/large-vecteezy_ai-generated-a-silhouette-of-a-person-standing-on-top-of-a_40247032_large.jpg') }}" alt="AI Applicant Tracking System dashboard mood visual" class="hero-media-image h-72 w-full rounded-xl object-cover opacity-0 transition-opacity duration-700" data-hero-image fetchpriority="high" decoding="async" width="1280" height="800">
                    <div class="absolute inset-0 z-[2] bg-gradient-to-t from-slate-950 via-slate-950/15 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 right-4 z-[3] rounded-xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-300">Live platform snapshot</p>
                        <p class="mt-2 text-sm text-slate-100">Avg AI score: <span class="font-semibold">{{ $platformMetrics['avg_ai_score'] ?? 0 }}%</span></p>
                        <p class="mt-1 text-sm text-slate-100">Scheduled interviews: <span class="font-semibold">{{ number_format($platformMetrics['scheduled_interviews'] ?? 0) }}</span></p>
                    </div>
                </div>
            </div>
        </header>

        <div class="product-section-divider grid gap-6 lg:grid-cols-[1.2fr_0.8fr]" data-gallery-root>
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/60" aria-labelledby="product-media-heading">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Product Media</p>
                        <h2 id="product-media-heading" class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Smart CV Parser and recruitment automation visuals</h2>
                    </div>
                    <a href="https://www.vecteezy.com" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">Source: Vecteezy</a>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900" data-gallery-stage>
                    <img data-gallery-image src="{{ $initialGalleryImage }}" alt="{{ data_get($firstMedia, 'title', 'Recruitment automation visual') }}" class="h-80 w-full object-cover" loading="lazy" decoding="async" width="1280" height="720">
                    <video data-gallery-video controls preload="none" playsinline poster="{{ $videoPoster }}" class="hidden h-80 w-full object-cover"></video>
                    <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                        <p data-gallery-title class="text-sm font-semibold text-slate-900 dark:text-white">{{ data_get($firstMedia, 'title') }}</p>
                        <p data-gallery-caption class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ data_get($firstMedia, 'caption') }}</p>
                        <a data-gallery-source href="{{ data_get($firstMedia, 'source_url', '#') }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300 {{ data_get($firstMedia, 'source_url') ? '' : 'hidden' }}">Open source link</a>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach($gallery as $item)
                        <button type="button" class="group rounded-xl border border-slate-200 bg-white p-3 text-left transition-all duration-300 hover:-translate-y-1 hover:border-brand-400 hover:bg-brand-50/40 hover:shadow-xl dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-brand-500/10 {{ $loop->first ? 'ring-2 ring-brand-300 border-brand-400' : '' }}" data-gallery-item data-type="{{ data_get($item, 'type', 'image') }}" data-src="{{ data_get($item, 'src') }}" data-title="{{ data_get($item, 'title') }}" data-caption="{{ data_get($item, 'caption') }}" data-source="{{ data_get($item, 'source_url') }}" data-poster="{{ $videoPoster }}">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">{{ data_get($item, 'title') }}</p>
                                <span class="rounded-full border border-slate-300 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:border-slate-600 dark:text-slate-300">{{ data_get($item, 'type', 'image') }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ data_get($item, 'caption') }}</p>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/60" data-roi aria-labelledby="roi-heading">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Dynamic Estimator</p>
                <h2 id="roi-heading" class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Recruitment Automation Suite ROI</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Adjust values to estimate recruiter hours and cost saved with AI-assisted screening.</p>

                <div class="mt-5 space-y-4">
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Monthly applications</span>
                        <input data-roi-applications type="range" min="50" max="5000" step="50" value="600" class="mt-2 w-full">
                        <p class="mt-1 text-sm text-slate-700 dark:text-slate-200"><span data-roi-applications-value>600</span> applications</p>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Recruiter hourly cost (USD)</span>
                        <input data-roi-rate type="range" min="20" max="120" step="1" value="45" class="mt-2 w-full">
                        <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">$<span data-roi-rate-value>45</span>/hour</p>
                    </label>
                </div>

                <div class="mt-5 grid gap-3">
                    <div class="group rounded-xl border border-slate-200 bg-slate-50 p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-700 dark:bg-slate-800/70">
                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-300">Estimated hours saved</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300"><span data-roi-hours>0</span> hrs/month</p>
                    </div>
                    <div class="group rounded-xl border border-slate-200 bg-slate-50 p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-700 dark:bg-slate-800/70">
                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-300">Estimated monthly savings</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">$<span data-roi-savings>0</span></p>
                    </div>
                    <div class="rounded-xl border border-brand-200 bg-brand-50/40 p-4 dark:border-brand-700/60 dark:bg-brand-500/10">
                        <p class="text-xs uppercase tracking-wide text-brand-700 dark:text-brand-300">Assumption</p>
                        <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">AI automation reduces manual screening effort by 40% with about 10 minutes average screening per applicant.</p>
                    </div>
                </div>
            </section>
        </div>

        <section class="product-section-divider rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/60" data-feature-explorer aria-labelledby="feature-explorer-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Feature Explorer</p>
                    <h2 id="feature-explorer-heading" class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Explore AI Applicant Tracking System modules by hiring objective</h2>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                @foreach($featureGroups as $index => $group)
                    <button type="button" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-400 hover:text-brand-700 dark:border-slate-700 dark:text-slate-200" data-feature-pill data-key="{{ data_get($group, 'key') }}" @if($index === 0) data-active="1" @endif>
                        {{ data_get($group, 'label') }}
                    </button>
                @endforeach
            </div>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-900">
                <h3 data-feature-title class="text-lg font-semibold text-slate-900 dark:text-white">{{ data_get($firstFeatureGroup, 'label') }}</h3>
                <p data-feature-desc class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ data_get($firstFeatureGroup, 'desc') }}</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2" data-feature-items>
                    @foreach((array) data_get($firstFeatureGroup, 'items', []) as $item)
                        <article class="group rounded-xl border border-slate-200 bg-white p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-700 dark:bg-slate-800/70">
                            <h4 class="text-sm font-semibold text-slate-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">{{ data_get($item, 'title') }}</h4>
                            <p class="mt-2 text-xs leading-6 text-slate-600 dark:text-slate-300">{{ data_get($item, 'desc') }}</p>
                        </article>
                    @endforeach
                </div>
            </div>

            <script type="application/json" data-feature-json>@json($featureGroups)</script>
        </section>

        <section class="product-section-divider rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/60" data-workflow aria-labelledby="workflow-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Workflow Navigator</p>
                    <h2 id="workflow-heading" class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Step through your Hiring Lifecycle</h2>
                </div>
                <div class="inline-flex items-center gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" data-workflow-prev>Prev</button>
                    <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" data-workflow-next>Next</button>
                </div>
            </div>

            <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                <div data-workflow-progress class="h-full w-0 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 transition-all duration-300"></div>
            </div>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-900">
                <p data-workflow-step class="text-sm font-semibold uppercase tracking-wide text-brand-700 dark:text-brand-300"></p>
                <p data-workflow-detail class="mt-2 text-base text-slate-700 dark:text-slate-200"></p>
                <p data-workflow-metric class="mt-3 inline-flex rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200"></p>
            </div>

            <script type="application/json" data-workflow-json>@json($workflowRows)</script>
        </section>

        <section class="product-section-divider" aria-labelledby="capabilities-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Core Capabilities</p>
                    <h2 id="capabilities-heading" class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Recruitment Automation Suite features for every stage</h2>
                </div>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                @foreach($featureRows as $feature)
                    <article class="group rounded-2xl border border-slate-200 bg-white p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900/60">
                        <h3 class="text-base font-semibold text-slate-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">{{ data_get($feature, 'title') }}</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ data_get($feature, 'desc') }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="product-section-divider grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/60" aria-labelledby="roles-heading">
                <h2 id="roles-heading" class="text-2xl font-bold text-slate-900 dark:text-white">Built for every hiring role</h2>
                <div class="mt-5 space-y-4">
                    @foreach(($roleCards ?? collect()) as $role)
                        <article class="group rounded-xl border border-slate-200 p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-700">
                            <h3 class="text-sm font-semibold text-slate-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">{{ data_get($role, 'title') }}</h3>
                            <ul class="mt-2 space-y-1 text-xs text-slate-600 dark:text-slate-300">
                                @foreach((array) data_get($role, 'points', []) as $point)
                                    <li>- {{ $point }}</li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/60" aria-labelledby="plan-fit-heading">
                <h2 id="plan-fit-heading" class="text-2xl font-bold text-slate-900 dark:text-white">Plan fit guidance</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Use this to align team size and hiring volume before selecting a plan.</p>
                <div class="mt-5 space-y-3">
                    @foreach(($plans ?? collect()) as $plan)
                        <article class="group rounded-xl border border-slate-200 p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">{{ data_get($plan, 'name') }}</h3>
                                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ data_get($plan, 'desc') }}</p>
                                </div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ data_get($plan, 'price') }}/mo</p>
                            </div>
                        </article>
                    @endforeach
                </div>
                <a href="{{ route('public.pricing') }}" class="mt-5 inline-flex rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-500 hover:shadow-lg">Compare plans</a>
            </section>
        </div>

        <section class="product-section-divider rounded-3xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/60" aria-labelledby="active-jobs-heading">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="active-jobs-heading" class="text-2xl font-bold text-slate-900 dark:text-white">Active opportunities in your Hiring Lifecycle</h2>
                    <p class="mt-2 text-slate-600 dark:text-slate-300">Public board visibility connected directly to recruiter workflows.</p>
                </div>
                <a href="{{ route('jobs.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">View all jobs</a>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse(($activeJobs ?? collect()) as $job)
                    <article class="group rounded-xl border border-slate-200 p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-slate-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">{{ $job->title }}</h3>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ $job->location }} - {{ ucfirst(str_replace('_', ' ', (string) $job->location_type)) }}</p>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', (string) $job->job_type)) }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-400">Applications: {{ (int) $job->applications_count }}</p>
                    </article>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No active jobs published yet.</p>
                @endforelse
            </div>
        </section>

        <section class="product-section-divider rounded-3xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/60" data-product-faq aria-labelledby="product-faq-heading">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Buyer FAQ</p>
            <h2 id="product-faq-heading" class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Frequently asked questions about this AI Applicant Tracking System</h2>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">Answers to common adoption, integration, and governance questions before rollout.</p>

            <div class="mt-6 space-y-3">
                @foreach($productFaqs as $index => $faq)
                    <article class="product-faq-item rounded-2xl border border-slate-200 bg-slate-50 p-2 dark:border-slate-700 dark:bg-slate-900" data-product-faq-item data-open="{{ $index === 0 ? '1' : '0' }}">
                        <button type="button" class="flex w-full items-center justify-between gap-4 rounded-xl px-4 py-3 text-left transition-colors hover:bg-white/70 dark:hover:bg-slate-800/60" data-product-faq-trigger aria-controls="product-faq-answer-{{ $index }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $faq['q'] }}</h3>
                            <span class="product-faq-icon inline-flex h-8 w-8 flex-none items-center justify-center rounded-full border border-slate-300 text-lg font-semibold text-slate-600 dark:border-slate-600 dark:text-slate-300">+</span>
                        </button>
                        <div id="product-faq-answer-{{ $index }}" class="product-faq-panel" data-product-faq-panel>
                            <div class="px-4 pb-4 pt-1">
                                <p class="text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $faq['a'] }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="product-section-divider rounded-3xl border border-brand-200 bg-gradient-to-r from-brand-50 via-cyan-50 to-emerald-50 p-8 shadow-sm dark:border-brand-700/50 dark:from-brand-500/10 dark:via-cyan-500/10 dark:to-emerald-500/10" aria-labelledby="final-cta-heading">
            <h2 id="final-cta-heading" class="text-2xl font-bold text-slate-900 dark:text-white">Launch smarter hiring workflows</h2>
            <p class="mt-2 text-slate-700 dark:text-slate-300">Start with your current process, then scale with role-based collaboration, AI screening, and interview automation.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-500 hover:shadow-lg">Create Account</a>
                <a href="{{ route('public.pricing') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Compare Plans</a>
                <a href="{{ route('public.contact') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Request Demo</a>
            </div>
        </section>
    </article>

    <script type="application/ld+json">
        {!! json_encode($softwareSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-product-page]');
            if (!root) return;

            const formatNumber = (value) => Number(value || 0).toLocaleString();

            const heroImage = root.querySelector('[data-hero-image]');
            if (heroImage) {
                const frame = heroImage.closest('.hero-media-frame');
                const markLoaded = () => {
                    if (frame) frame.setAttribute('data-loaded', '1');
                    heroImage.classList.remove('opacity-0');
                    heroImage.classList.add('opacity-100');
                };
                if (heroImage.complete) {
                    markLoaded();
                } else {
                    heroImage.addEventListener('load', markLoaded, { once: true });
                }
            }

            const galleryRoot = root.querySelector('[data-gallery-root]');
            if (galleryRoot) {
                const imageEl = galleryRoot.querySelector('[data-gallery-image]');
                const videoEl = galleryRoot.querySelector('[data-gallery-video]');
                const titleEl = galleryRoot.querySelector('[data-gallery-title]');
                const captionEl = galleryRoot.querySelector('[data-gallery-caption]');
                const sourceEl = galleryRoot.querySelector('[data-gallery-source]');
                const items = Array.from(galleryRoot.querySelectorAll('[data-gallery-item]'));

                const resetVideo = () => {
                    if (!videoEl) return;
                    videoEl.pause();
                    if (videoEl.getAttribute('src')) {
                        videoEl.removeAttribute('src');
                        videoEl.dataset.activeSrc = '';
                        videoEl.load();
                    }
                };

                const activateGalleryItem = (button) => {
                    if (!button || !imageEl || !videoEl || !titleEl || !captionEl || !sourceEl) return;
                    const type = button.getAttribute('data-type') || 'image';
                    const src = button.getAttribute('data-src') || '';
                    const title = button.getAttribute('data-title') || '';
                    const caption = button.getAttribute('data-caption') || '';
                    const sourceUrl = button.getAttribute('data-source') || '#';
                    const poster = button.getAttribute('data-poster') || '';

                    items.forEach((item) => item.classList.remove('ring-2', 'ring-brand-300', 'border-brand-400'));
                    button.classList.add('ring-2', 'ring-brand-300', 'border-brand-400');

                    if (type === 'video') {
                        imageEl.classList.add('hidden');
                        videoEl.classList.remove('hidden');
                        videoEl.setAttribute('poster', poster);
                        if (src && videoEl.dataset.activeSrc !== src) {
                            videoEl.setAttribute('src', src);
                            videoEl.dataset.activeSrc = src;
                            videoEl.load();
                        }
                    } else {
                        resetVideo();
                        videoEl.classList.add('hidden');
                        imageEl.classList.remove('hidden');
                        if (src) imageEl.src = src;
                        imageEl.alt = title;
                    }

                    titleEl.textContent = title;
                    captionEl.textContent = caption;
                    if (sourceUrl && sourceUrl !== '#') {
                        sourceEl.href = sourceUrl;
                        sourceEl.classList.remove('hidden');
                    } else {
                        sourceEl.href = '#';
                        sourceEl.classList.add('hidden');
                    }
                };

                items.forEach((item) => {
                    item.addEventListener('click', () => activateGalleryItem(item));
                });
            }

            const roiRoot = root.querySelector('[data-roi]');
            if (roiRoot) {
                const appInput = roiRoot.querySelector('[data-roi-applications]');
                const rateInput = roiRoot.querySelector('[data-roi-rate]');
                const appValue = roiRoot.querySelector('[data-roi-applications-value]');
                const rateValue = roiRoot.querySelector('[data-roi-rate-value]');
                const hoursEl = roiRoot.querySelector('[data-roi-hours]');
                const savingsEl = roiRoot.querySelector('[data-roi-savings]');

                const updateRoi = () => {
                    const applications = Number((appInput && appInput.value) || 0);
                    const hourlyRate = Number((rateInput && rateInput.value) || 0);
                    const minutesPerApplicant = 10;
                    const automationGain = 0.4;
                    const totalHours = (applications * minutesPerApplicant) / 60;
                    const hoursSaved = totalHours * automationGain;
                    const moneySaved = hoursSaved * hourlyRate;

                    if (appValue) appValue.textContent = formatNumber(applications);
                    if (rateValue) rateValue.textContent = formatNumber(hourlyRate);
                    if (hoursEl) hoursEl.textContent = formatNumber(hoursSaved.toFixed(0));
                    if (savingsEl) savingsEl.textContent = formatNumber(moneySaved.toFixed(0));
                };

                if (appInput) appInput.addEventListener('input', updateRoi);
                if (rateInput) rateInput.addEventListener('input', updateRoi);
                updateRoi();
            }

            const featureRoot = root.querySelector('[data-feature-explorer]');
            if (featureRoot) {
                const jsonEl = featureRoot.querySelector('[data-feature-json]');
                const titleEl = featureRoot.querySelector('[data-feature-title]');
                const descEl = featureRoot.querySelector('[data-feature-desc]');
                const itemsEl = featureRoot.querySelector('[data-feature-items]');
                const pills = Array.from(featureRoot.querySelectorAll('[data-feature-pill]'));
                let groups = [];

                try {
                    groups = JSON.parse((jsonEl && jsonEl.textContent) || '[]');
                } catch (e) {
                    groups = [];
                }

                const renderGroup = (key) => {
                    const group = groups.find((item) => item.key === key) || groups[0];
                    if (!group || !titleEl || !descEl || !itemsEl) return;

                    pills.forEach((pill) => {
                        const active = pill.getAttribute('data-key') === group.key;
                        pill.classList.toggle('product-pill-active', active);
                    });

                    titleEl.textContent = group.label || '';
                    descEl.textContent = group.desc || '';

                    itemsEl.innerHTML = '';
                    const groupItems = Array.isArray(group.items) ? group.items : [];

                    if (!groupItems.length) {
                        const empty = document.createElement('p');
                        empty.className = 'text-sm text-slate-500 dark:text-slate-300';
                        empty.textContent = 'No items available in this module yet.';
                        itemsEl.appendChild(empty);
                        return;
                    }

                    groupItems.forEach((item) => {
                        const card = document.createElement('article');
                        card.className = 'group rounded-xl border border-slate-200 bg-white p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-700 dark:bg-slate-800/70';

                        const heading = document.createElement('h4');
                        heading.className = 'text-sm font-semibold text-slate-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300';
                        heading.textContent = (item && item.title) || '';

                        const body = document.createElement('p');
                        body.className = 'mt-2 text-xs leading-6 text-slate-600 dark:text-slate-300';
                        body.textContent = (item && item.desc) || '';

                        card.appendChild(heading);
                        card.appendChild(body);
                        itemsEl.appendChild(card);
                    });
                };

                pills.forEach((pill) => {
                    pill.addEventListener('click', () => renderGroup(pill.getAttribute('data-key')));
                });

                if (groups.length) {
                    renderGroup(groups[0].key);
                }
            }

            const workflowRoot = root.querySelector('[data-workflow]');
            if (workflowRoot) {
                const jsonEl = workflowRoot.querySelector('[data-workflow-json]');
                const stepEl = workflowRoot.querySelector('[data-workflow-step]');
                const detailEl = workflowRoot.querySelector('[data-workflow-detail]');
                const metricEl = workflowRoot.querySelector('[data-workflow-metric]');
                const progressEl = workflowRoot.querySelector('[data-workflow-progress]');
                const prevBtn = workflowRoot.querySelector('[data-workflow-prev]');
                const nextBtn = workflowRoot.querySelector('[data-workflow-next]');
                let rows = [];
                let current = 0;

                try {
                    rows = JSON.parse((jsonEl && jsonEl.textContent) || '[]');
                } catch (e) {
                    rows = [];
                }

                const renderStep = () => {
                    if (!rows.length || !stepEl || !detailEl || !metricEl || !progressEl) return;
                    const row = rows[current] || rows[0];
                    stepEl.textContent = `Step ${current + 1} of ${rows.length}: ${row.step || ''}`;
                    detailEl.textContent = row.detail || '';
                    metricEl.textContent = row.metric || '';
                    const width = ((current + 1) / rows.length) * 100;
                    progressEl.style.width = `${width}%`;
                };

                if (prevBtn) prevBtn.addEventListener('click', () => {
                    if (!rows.length) return;
                    current = (current - 1 + rows.length) % rows.length;
                    renderStep();
                });

                if (nextBtn) nextBtn.addEventListener('click', () => {
                    if (!rows.length) return;
                    current = (current + 1) % rows.length;
                    renderStep();
                });

                renderStep();
            }

            const faqRoot = root.querySelector('[data-product-faq]');
            if (faqRoot) {
                const faqItems = Array.from(faqRoot.querySelectorAll('[data-product-faq-item]'));

                const setFaqPanelHeight = (item, open) => {
                    const panel = item.querySelector('[data-product-faq-panel]');
                    const trigger = item.querySelector('[data-product-faq-trigger]');
                    if (!panel || !trigger) return;

                    item.setAttribute('data-open', open ? '1' : '0');
                    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');

                    if (open) {
                        panel.style.maxHeight = panel.scrollHeight + 'px';
                        setTimeout(() => {
                            if (item.getAttribute('data-open') === '1') {
                                panel.style.maxHeight = panel.scrollHeight + 'px';
                            }
                        }, 280);
                    } else {
                        panel.style.maxHeight = '0px';
                    }
                };

                const closeOtherFaqs = (exceptItem) => {
                    faqItems.forEach((item) => {
                        if (item === exceptItem) return;
                        setFaqPanelHeight(item, false);
                    });
                };

                faqItems.forEach((item, index) => {
                    const trigger = item.querySelector('[data-product-faq-trigger]');
                    if (!trigger) return;

                    setFaqPanelHeight(item, index === 0);

                    trigger.addEventListener('click', () => {
                        const isOpen = item.getAttribute('data-open') === '1';
                        if (!isOpen) closeOtherFaqs(item);
                        setFaqPanelHeight(item, !isOpen);
                    });
                });

                window.addEventListener('resize', () => {
                    faqItems.forEach((item) => {
                        if (item.getAttribute('data-open') !== '1') return;
                        const panel = item.querySelector('[data-product-faq-panel]');
                        if (!panel) return;
                        panel.style.maxHeight = panel.scrollHeight + 'px';
                    });
                });
            }
        });
    </script>
@endsection
