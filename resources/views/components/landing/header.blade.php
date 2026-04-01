@props(['appName' => config('app.name', 'NovaHire')])

<header class="sticky top-0 z-50">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-white/80 to-transparent dark:from-slate-950/80"></div>
    <div class="relative border-b border-slate-200/60 bg-white/60 backdrop-blur-2xl dark:border-slate-800/70 dark:bg-slate-950/55">
        <div class="nh-container flex items-center justify-between py-3">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <span class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700">
                <img src="/images/logo/novahire-icon.png" class="h-7 w-7" alt="{{ $appName }}">
            </span>
            <span class="text-base font-semibold tracking-tight text-slate-900 dark:text-white">{{ $appName }}</span>
        </a>

        <nav class="hidden items-center rounded-full border border-slate-200 bg-white/70 p-1 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-200 md:flex">
            <a href="{{ route('public.product') }}" class="rounded-full px-4 py-2 transition hover:bg-slate-100/90 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white">Product</a>
            <a href="{{ route('public.features') }}" class="rounded-full px-4 py-2 transition hover:bg-slate-100/90 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white">Features</a>
            <a href="{{ route('public.pricing') }}" class="rounded-full px-4 py-2 transition hover:bg-slate-100/90 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white">Pricing</a>
            <a href="{{ route('public.faq') }}" class="rounded-full px-4 py-2 transition hover:bg-slate-100/90 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white">FAQ</a>
            <a href="{{ route('jobs.index') }}" class="rounded-full px-4 py-2 transition hover:bg-slate-100/90 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white">Jobs</a>
        </nav>

        <div class="flex items-center gap-2">
            <button type="button" id="theme-toggle"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white/70 text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:bg-slate-900">
                <i id="theme-toggle-icon" data-lucide="moon" class="h-4 w-4"></i>
            </button>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
            @else
                <a href="{{ route('login') }}"
                    class="hidden items-center rounded-xl border border-slate-200 bg-white/70 px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:bg-slate-900 sm:inline-flex">Sign
                    In</a>
                <a href="{{ route('register') }}"
                    class="inline-flex items-center rounded-xl bg-brand-600 px-4 py-2 text-xs font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:-translate-y-0.5 hover:bg-brand-500">Get
                    Started</a>
            @endauth
        </div>
        </div>
    </div>
</header>
