@props(['features' => []])
@php
    $publicFeatures = collect($features)->filter(function ($feature) {
        $title = strtolower((string) data_get($feature, 'title', ''));
        return !str_contains($title, 'billing');
    })->values();
@endphp

<section id="features" class="nh-section">
    <div class="nh-container">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p data-animate="reveal" class="nh-reveal nh-eyebrow">Core Platform</p>
                <h2 data-animate="reveal" data-delay="1" class="nh-reveal nh-h2">Built for AI recruiting operations</h2>
                <p data-animate="reveal" data-delay="2" class="nh-reveal nh-lead">Everything your teams need for CV analysis, screening, and decisioning.</p>
            </div>
            <div
                data-animate="reveal" data-delay="3"
                class="hidden rounded-full border border-slate-200 bg-white/70 px-5 py-2.5 text-xs font-semibold text-slate-600 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300 sm:block">
                Real-time hiring workflow intelligence
            </div>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($publicFeatures as $feature)
                <article
                    data-animate="reveal"
                    class="nh-reveal nh-card p-6">
                    <div class="mb-5 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 ring-1 ring-brand-100 dark:bg-brand-500/15 dark:text-brand-300 dark:ring-brand-500/20">
                        <i data-lucide="{{ data_get($feature, 'icon', 'sparkles') }}" class="h-5 w-5"></i>
                    </div>
                    <h3 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ data_get($feature, 'title') }}</h3>
                    <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-100">
                        {{ data_get($feature, 'desc') }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>