@extends('layouts.public')

@section('content')
    <section class="space-y-10">
        <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900/60 lg:p-10">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(900px_circle_at_0%_0%,rgba(56,189,248,0.12),transparent_42%),radial-gradient(760px_circle_at_100%_0%,rgba(16,185,129,0.10),transparent_38%)]"></div>

            <div class="relative grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-brand-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-brand-300">
                        <span class="inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                        About NovaHire
                    </p>

                    <h1 class="mt-4 text-4xl font-bold leading-tight text-slate-900 dark:text-white lg:text-5xl">
                        AI recruitment operations, designed like a real product platform.
                    </h1>

                    <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                        NovaHire helps hiring teams move from fragmented recruiting tools to one structured operating system.
                        Instead of disconnected spreadsheets, inbox chains, and ad-hoc screening, we provide a unified workflow
                        for public job discovery, CV evaluation, candidate ranking, interview coordination, and final hiring decisions.
                    </p>

                    <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-300">
                        Our goal is straightforward: make hiring faster, more explainable, and more collaborative across recruiters,
                        HR admins, hiring managers, and candidates.
                    </p>

                    <div class="mt-7 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-300">Companies</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($platformMetrics['companies'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-300">Candidates</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($platformMetrics['candidates'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-300">Applications</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($platformMetrics['applications'] ?? 0) }}</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-900">
                    <img
                        src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1400&q=75"
                        alt="Professional team reviewing hiring analytics"
                        class="h-full w-full object-cover"
                        width="1400"
                        height="950"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async">
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700 dark:text-brand-300">What We Solve</p>
                <h2 class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">Recruiting complexity should not be your operating model.</h2>
                <p class="mt-4 text-base leading-8 text-slate-600 dark:text-slate-300">
                    Most teams do not struggle because they lack data. They struggle because hiring context is scattered across
                    too many tools and role handoffs are ambiguous. NovaHire addresses this by giving each role clear ownership,
                    shared visibility, and structured signals at every stage of the pipeline.
                </p>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                            <i data-lucide="brain-circuit" class="h-5 w-5"></i>
                        </div>
                        <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">Structured AI Screening</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                            Extract skills, evaluate fit, and prioritize candidates with consistent criteria.
                        </p>
                    </article>
                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                            <i data-lucide="workflow" class="h-5 w-5"></i>
                        </div>
                        <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">Role-Based Workflow</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                            Recruiters, managers, and admins get interfaces aligned to their decisions.
                        </p>
                    </article>
                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300">
                            <i data-lucide="calendar-check-2" class="h-5 w-5"></i>
                        </div>
                        <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">Interview Orchestration</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                            Coordinate schedules, reminders, and candidate responses without workflow drift.
                        </p>
                    </article>
                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300">
                            <i data-lucide="bar-chart-3" class="h-5 w-5"></i>
                        </div>
                        <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">Recruitment Analytics</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                            Track throughput, conversion, and hiring quality with live operational metrics.
                        </p>
                    </article>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700 dark:text-brand-300">How We Operate</p>
                <h2 class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">Principles behind the platform.</h2>
                <div class="mt-6 space-y-4">
                    <article class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Operational Clarity</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">Every stage has clear ownership and visible status, reducing ambiguity across hiring teams.</p>
                    </article>
                    <article class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Human Accountable Decisions</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">AI supports evaluations with structured insight, while final decisions remain with people.</p>
                    </article>
                    <article class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Built for Scale</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">From growing teams to enterprise hiring operations, workflows stay consistent and auditable.</p>
                    </article>
                </div>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                <img
                    src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=72"
                    alt="Hiring managers collaborating at a workstation"
                    class="h-52 w-full object-cover"
                    width="1200"
                    height="720"
                    loading="lazy"
                    decoding="async">
                <div class="p-5">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Collaborative Decisioning</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">Shared context between recruiters and hiring managers reduces review loops and improves quality.</p>
                </div>
            </article>
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                <img
                    src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=72"
                    alt="Professional recruitment strategy meeting"
                    class="h-52 w-full object-cover"
                    width="1200"
                    height="720"
                    loading="lazy"
                    decoding="async">
                <div class="p-5">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Process Discipline</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">Structured pipelines and role-safe workflows improve consistency and accountability.</p>
                </div>
            </article>
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                <img
                    src="https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=1200&q=72"
                    alt="Data-informed hiring operations dashboard planning"
                    class="h-52 w-full object-cover"
                    width="1200"
                    height="720"
                    loading="lazy"
                    decoding="async">
                <div class="p-5">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Data-Driven Hiring Ops</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">Real-time metrics help teams continuously improve sourcing, screening, and interview throughput.</p>
                </div>
            </article>
        </div>

        <div class="rounded-3xl border border-brand-200 bg-gradient-to-r from-brand-50/80 to-cyan-50/70 p-8 dark:border-brand-700/50 dark:from-brand-500/10 dark:to-cyan-500/10">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Build a hiring process your team can trust.</h2>
            <p class="mt-2 max-w-3xl text-slate-700 dark:text-slate-300">
                NovaHire combines candidate experience, recruiter efficiency, and hiring manager decision quality in one integrated platform.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('public.product') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">Explore Product</a>
                <a href="{{ route('public.pricing') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">View Pricing</a>
                <a href="{{ route('public.contact') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Talk to Team</a>
            </div>
        </div>
    </section>

    @php
        $orgSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'NovaHire',
            'url' => url('/'),
            'sameAs' => [],
            'description' => 'AI-powered recruitment operations platform for recruiters, hiring managers, HR admins, and candidates.',
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($orgSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endsection
