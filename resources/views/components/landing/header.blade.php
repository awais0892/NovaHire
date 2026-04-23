@props(['appName' => config('app.name', 'NovaHire')])

@php
    $navItems = [
        ['label' => 'Product', 'href' => route('public.product'), 'active' => request()->routeIs('public.product')],
        ['label' => 'Features', 'href' => route('public.features'), 'active' => request()->routeIs('public.features')],
        ['label' => 'Pricing', 'href' => route('public.pricing'), 'active' => request()->routeIs('public.pricing')],
        ['label' => 'About', 'href' => route('public.about'), 'active' => request()->routeIs('public.about')],
        ['label' => 'Contact', 'href' => route('public.contact'), 'active' => request()->routeIs('public.contact')],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200/60 bg-white/60 backdrop-blur-2xl dark:border-slate-800/70 dark:bg-slate-950/55">
    <div class="nh-container flex items-center justify-between gap-3 py-3">
        <div class="flex min-w-0 items-center gap-2">
            <button
                type="button"
                id="nh-mob-toggle"
                aria-label="Open mobile menu"
                aria-controls="nh-mob-drawer"
                aria-expanded="false"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white/80 text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400/50 dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-900 md:hidden"
            >
                <svg id="nh-mob-open-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M2.5 5H17.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M2.5 10H13.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M2.5 15H17.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <svg id="nh-mob-close-icon" class="hidden" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M5.25 5.25L14.75 14.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M14.75 5.25L5.25 14.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>

            <a href="{{ route('home') }}" class="ml-1 flex items-center gap-3 md:ml-0">
                <span class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700">
                    <img src="/images/logo/novahire-mark.svg" class="h-7 w-7 dark:hidden" alt="{{ $appName }}">
                    <img src="/images/logo/novahire-mark-light.svg" class="hidden h-7 w-7 dark:block" alt="{{ $appName }}">
                </span>
                <span class="truncate text-base font-semibold tracking-tight text-slate-900 dark:text-white">{{ $appName }}</span>
            </a>
        </div>

        <nav class="hidden items-center rounded-full border border-slate-200/80 bg-white/75 p-1 text-sm font-semibold shadow-sm backdrop-blur dark:border-slate-700/80 dark:bg-slate-900/70 md:flex">
            @foreach($navItems as $item)
                @php
                    $base = 'rounded-full px-4 py-2 transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400/50';
                    $active = 'bg-slate-200/95 text-slate-900 ring-1 ring-slate-300/80 shadow-sm dark:bg-slate-800 dark:text-white dark:ring-slate-600/80';
                    $inactive = 'text-slate-600 hover:bg-slate-100/95 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-white';
                @endphp
                <a href="{{ $item['href'] }}" @if($item['active']) aria-current="page" @endif class="{{ $base }} {{ $item['active'] ? $active : $inactive }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2 sm:gap-3">
            <button
                type="button"
                id="theme-toggle"
                aria-label="Toggle theme"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white/70 text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:bg-slate-900"
            >
                <x-common.theme-toggle-icon />
            </button>

            <a href="{{ route('public.contact') }}" class="hidden items-center rounded-xl border border-slate-200 bg-white/70 px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:bg-slate-900 sm:inline-flex">
                Book Demo
            </a>
            <a href="{{ route('register') }}" class="inline-flex items-center rounded-xl bg-brand-600 px-4 py-2 text-xs font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:-translate-y-0.5 hover:bg-brand-500">
                Start Hiring
            </a>
        </div>
    </div>
</header>

<div
    id="nh-mob-overlay"
    aria-hidden="true"
    style="display:none;"
    class="fixed inset-0 z-[9998] bg-slate-950/50 backdrop-blur-[2px] transition-opacity duration-300 opacity-0"
></div>

<aside
    id="nh-mob-drawer"
    role="dialog"
    aria-modal="true"
    aria-label="Mobile navigation"
    aria-hidden="true"
    tabindex="-1"
    style="display:none;"
    class="fixed inset-y-0 left-0 z-[9999] flex w-[80vw] max-w-[320px] -translate-x-full flex-col bg-white shadow-2xl shadow-slate-900/30 transition-transform duration-300 ease-out dark:bg-slate-950"
>
    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4 dark:border-slate-800">
        <a href="{{ route('home') }}" id="nh-mob-logo-link" class="flex items-center gap-2.5">
            <span class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700">
                <img src="/images/logo/novahire-mark.svg" class="h-6 w-6 dark:hidden" alt="{{ $appName }}">
                <img src="/images/logo/novahire-mark-light.svg" class="hidden h-6 w-6 dark:block" alt="{{ $appName }}">
            </span>
            <span class="text-sm font-bold tracking-tight text-slate-900 dark:text-white">{{ $appName }}</span>
        </a>
        <button
            type="button"
            id="nh-mob-drawer-close"
            aria-label="Close mobile menu"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400/50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
        >
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M5.25 5.25L14.75 14.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M14.75 5.25L5.25 14.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-5" aria-label="Mobile navigation links">
        <ul class="space-y-1">
            @foreach($navItems as $item)
                @php
                    $mBase = 'nh-mob-link group flex items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400/50';
                    $mActive = 'bg-brand-50 text-brand-700 ring-1 ring-brand-200/70 dark:bg-brand-900/30 dark:text-brand-300 dark:ring-brand-700/50';
                    $mInactive = 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800/80 dark:hover:text-white';
                @endphp
                <li>
                    <a href="{{ $item['href'] }}" @if($item['active']) aria-current="page" @endif class="{{ $mBase }} {{ $item['active'] ? $mActive : $mInactive }}">
                        <span>{{ $item['label'] }}</span>
                        <svg class="h-4 w-4 opacity-40 transition group-hover:translate-x-0.5 group-hover:opacity-70" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M8 5L13 10L8 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="border-t border-slate-200 px-4 py-5 dark:border-slate-800">
        <div class="space-y-2 rounded-2xl border border-slate-200/90 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900/70">
            <a href="{{ route('register') }}" class="nh-mob-link inline-flex w-full items-center justify-center rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm shadow-brand-500/25 transition hover:bg-brand-500">
                Start Hiring
            </a>
            <a href="{{ route('public.contact') }}" class="nh-mob-link inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                Book Demo
            </a>
        </div>
    </div>
</aside>
