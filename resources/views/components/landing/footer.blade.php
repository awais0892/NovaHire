@php
    $appName = config('app.name', 'NovaHire');
@endphp

<footer class="relative overflow-hidden border-t border-slate-200/70 bg-slate-50/70 py-16 text-slate-700 backdrop-blur-xl dark:border-slate-800/70 dark:bg-slate-950/55 dark:text-slate-300">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -top-24 -left-16 h-72 w-72 rounded-full bg-brand-400/12 blur-3xl dark:bg-brand-500/16"></div>
        <div class="absolute -bottom-28 -right-14 h-72 w-72 rounded-full bg-emerald-400/12 blur-3xl dark:bg-emerald-500/14"></div>
    </div>

    <div class="relative nh-container">
        <div class="mb-10 overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white/80 p-5 shadow-sm backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/60 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">AI Hiring, Human Precision</p>
                    <p class="mt-2 text-lg font-semibold tracking-tight text-slate-900 dark:text-white">
                        Build your hiring engine with NovaHire.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-xl bg-brand-600 px-4 py-2 text-xs font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:-translate-y-0.5 hover:bg-brand-500">
                        Start Hiring
                    </a>
                    <a href="{{ route('public.contact') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white/70 px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:bg-slate-900">
                        Book Demo
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-8 md:grid-cols-4">
            <div class="md:col-span-2">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white/80 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                        <img src="/images/logo/novahire-mark.svg" class="h-7 w-7 dark:hidden" alt="{{ $appName }}">
                        <img src="/images/logo/novahire-mark-light.svg" class="hidden h-7 w-7 dark:block" alt="{{ $appName }}">
                    </span>
                    <p class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $appName }}</p>
                </div>

                <p class="mt-3 max-w-md text-base text-slate-600 dark:text-slate-300">
                    Source, screen, and onboard the right candidates with AI workflows and human oversight.
                </p>
            </div>

            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-900 dark:text-white">Explore</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <li><a href="{{ route('home') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Home</a></li>
                    <li><a href="{{ route('public.product') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Product</a></li>
                    <li><a href="{{ route('public.features') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Features</a></li>
                    <li><a href="{{ route('public.pricing') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Pricing</a></li>
                    <li><a href="{{ route('public.about') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">About</a></li>
                    <li><a href="{{ route('public.contact') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Contact</a></li>
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-900 dark:text-white">Resources</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <li><a href="{{ route('public.faq') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">FAQ</a></li>
                    <li><a href="{{ route('public.sitemap') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Sitemap</a></li>
                    <li><a href="{{ route('public.privacy') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Privacy Policy</a></li>
                    <li><a href="{{ route('public.terms') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Terms of Service</a></li>
                    <li><a href="mailto:support@novahire.com" class="transition hover:text-brand-600 dark:hover:text-brand-300">support@novahire.com</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-2 border-t border-slate-200/80 pt-4 text-xs text-slate-500 dark:border-slate-800/80 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ $appName }}. All rights reserved.</p>
            <p>Consistent recruiting experiences in light and dark mode.</p>
        </div>
    </div>
</footer>
