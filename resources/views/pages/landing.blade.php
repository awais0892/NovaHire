@php
    $hero = data_get($content ?? [], 'hero', []);
    $stats = data_get($content ?? [], 'stats', []);
    $features = data_get($content ?? [], 'features', []);
    $roleCards = data_get($content ?? [], 'roles', []);
    $plans = collect($stripePlans ?? [])->isNotEmpty()
        ? $stripePlans
        : data_get($content ?? [], 'plans', []);
    $logoFiles = data_get($content ?? [], 'logos', []);
    $featuredJobs = $featuredJobs ?? collect();
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'NovaHire') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    @include('partials.vite-assets')
    @livewireStyles
    <style>
        html {
            font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
        }

        @keyframes marquee {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-50%);
            }
        }
    </style>
    <script>
        (function () {
            const saved = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = saved ? saved === 'dark' : prefersDark;
            if (isDark) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>

<body
    class="bg-slate-50 text-[17px] text-slate-900 antialiased transition-colors dark:bg-slate-950 dark:text-slate-100">
    <div class="min-h-screen">
        <x-landing.header :app-name="config('app.name', 'NovaHire')" />

        <main class="relative overflow-x-clip">
            <x-landing.hero :hero="$hero" :stats="$stats" :roles="$roleCards" :features="$features" />
            <x-landing.jobs-market :jobs="$featuredJobs" />
            <x-landing.logo-strip :logos="$logoFiles" />
            <x-landing.features :features="$features" />
            <x-landing.roles :roles="$roleCards" />
            <x-landing.showcase />
            <x-landing.reviews />
            <x-landing.pricing :plans="$plans" />
        </main>

        <x-landing.footer />
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('theme-toggle');
            const toggleIcon = document.getElementById('theme-toggle-icon');
            const syncIcon = () => {
                if (!toggleIcon) return;
                const isDark = document.documentElement.classList.contains('dark');
                toggleIcon.setAttribute('data-lucide', isDark ? 'sun' : 'moon');
                if (window.createIcons && window.lucideIcons) window.createIcons({ icons: window.lucideIcons });
            };
            if (toggle) {
                toggle.addEventListener('click', () => {
                    document.documentElement.classList.toggle('dark');
                    const isDark = document.documentElement.classList.contains('dark');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                    syncIcon();
                });
            }
            if (window.createIcons && window.lucideIcons) window.createIcons({ icons: window.lucideIcons });
            syncIcon();
        });
    </script>
    @livewireScripts
</body>

</html>
