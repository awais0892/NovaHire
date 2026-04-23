<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $canonicalUrl = rtrim(url()->current(), '/');
        if ($canonicalUrl === '') {
            $canonicalUrl = url('/');
        }
        $metaTitle = ($title ?? 'NovaHire') . ' | NovaHire';
        $metaDesc = $metaDescription ?? 'NovaHire AI recruitment platform for modern hiring teams.';
        $metaImg = $metaImage ?? asset('images/logo/novahire-wordmark.svg');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDesc }}">
    <meta name="robots" content="index,follow">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDesc }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $metaImg }}">
    <meta property="og:image:alt" content="NovaHire platform preview">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDesc }}">
    <meta name="twitter:image" content="{{ $metaImg }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('partials.vite-assets', ['jsEntry' => 'resources/js/public.js'])
    @livewireStyles
    @stack('head')
    <style>
        html { font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; }
    </style>
    <script>
        (function () {
            const saved = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = saved ? saved === 'dark' : prefersDark;
            if (isDark) document.documentElement.classList.add('dark');
        })();
    </script>
</head>
<body class="bg-slate-50 text-[17px] text-slate-900 antialiased transition-colors dark:bg-slate-950 dark:text-slate-100">
    <div class="min-h-screen">
        <x-landing.header app-name="NovaHire" />
        <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @if(!empty($breadcrumbs) && is_array($breadcrumbs))
                <nav aria-label="Breadcrumb" class="mb-6">
                    <ol class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-300">
                        @foreach($breadcrumbs as $index => $crumb)
                            @php $isLast = $index === count($breadcrumbs) - 1; @endphp
                            <li class="inline-flex items-center gap-2">
                                @if(!$isLast)
                                    <a href="{{ $crumb['url'] ?? '#' }}" class="hover:text-brand-600 dark:hover:text-brand-300">{{ $crumb['name'] ?? '' }}</a>
                                    <span>/</span>
                                @else
                                    <span class="font-semibold text-slate-700 dark:text-slate-100">{{ $crumb['name'] ?? '' }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
        </main>

        <x-landing.footer />
    </div>

    <x-common.scroll-to-top />

    @if(!empty($breadcrumbs) && is_array($breadcrumbs))
        @php
            $breadcrumbSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($breadcrumbs)->values()->map(function ($crumb, $index) {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $crumb['name'] ?? '',
                        'item' => $crumb['url'] ?? url()->current(),
                    ];
                })->all(),
            ];
        @endphp
        <script type="application/ld+json">
            {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('theme-toggle');
            if (toggle) {
                toggle.addEventListener('click', () => {
                    document.documentElement.classList.toggle('dark');
                    const isDark = document.documentElement.classList.contains('dark');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                });
            }
        });
    </script>

    @livewireScripts
</body>
</html>
