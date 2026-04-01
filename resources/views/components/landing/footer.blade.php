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
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Hiring Workspace</p>
                    <p class="mt-2 text-lg font-semibold tracking-tight text-slate-900 dark:text-white">
                        Built for modern recruitment operations.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center rounded-xl bg-brand-600 px-4 py-2 text-xs font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:-translate-y-0.5 hover:bg-brand-500">
                            Open Dashboard
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center rounded-xl bg-brand-600 px-4 py-2 text-xs font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:-translate-y-0.5 hover:bg-brand-500">
                            Get Started
                        </a>
                        <a href="{{ route('public.contact') }}"
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white/70 px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:bg-slate-900">
                            Contact Sales
                        </a>
                    @endauth
                </div>
            </div>
        </div>

        <div class="grid gap-8 md:grid-cols-4">
            <div class="md:col-span-2">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white/80 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                        <img src="/images/logo/novahire-icon.png" class="h-7 w-7" alt="{{ $appName }}">
                    </span>
                    <p class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $appName }}</p>
                </div>

                <p class="mt-3 max-w-md text-base text-slate-600 dark:text-slate-300">
                    AI-powered hiring workflows for recruiters, hiring managers, and talent teams to evaluate candidates faster with clearer decisions.
                </p>

                <div class="mt-5 flex items-center gap-3">
                    <a href="#" aria-label="LinkedIn"
                        class="group inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white/80 text-slate-600 shadow-sm transition hover:border-sky-400/60 hover:bg-sky-500/10 hover:text-sky-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:border-sky-400/60 dark:hover:bg-sky-500/15 dark:hover:text-sky-300">
                        <i data-lucide="linkedin" class="h-5 w-5"></i>
                    </a>
                    <a href="#" aria-label="Twitter"
                        class="group inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white/80 text-slate-600 shadow-sm transition hover:border-cyan-400/60 hover:bg-cyan-500/10 hover:text-cyan-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:border-cyan-400/60 dark:hover:bg-cyan-500/15 dark:hover:text-cyan-300">
                        <i data-lucide="twitter" class="h-5 w-5"></i>
                    </a>
                    <a href="#" aria-label="GitHub"
                        class="group inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white/80 text-slate-600 shadow-sm transition hover:border-violet-400/60 hover:bg-violet-500/10 hover:text-violet-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:border-violet-400/60 dark:hover:bg-violet-500/15 dark:hover:text-violet-300">
                        <i data-lucide="github" class="h-5 w-5"></i>
                    </a>
                </div>
            </div>

            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-900 dark:text-white">Product</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <li><a href="{{ route('public.product') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Overview</a></li>
                    <li><a href="{{ route('public.features') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Features</a></li>
                    <li><a href="{{ route('public.pricing') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Pricing</a></li>
                    <li><a href="{{ route('jobs.index') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Job Board</a></li>
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-900 dark:text-white">Company</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <li><a href="{{ route('public.about') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">About</a></li>
                    <li><a href="{{ route('public.contact') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Contact</a></li>
                    <li><a href="{{ route('public.faq') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">FAQ</a></li>
                    <li><a href="{{ route('public.privacy') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Privacy</a></li>
                    <li><a href="{{ route('public.terms') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Terms</a></li>
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Dashboard</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Sign In</a></li>
                        <li><a href="{{ route('register') }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">Create Account</a></li>
                    @endauth
                </ul>
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-2 border-t border-slate-200/80 pt-4 text-xs text-slate-500 dark:border-slate-800/80 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ $appName }}. All rights reserved.</p>
            <p>Consistent recruiting experiences in light and dark mode.</p>
        </div>
    </div>
</footer>
