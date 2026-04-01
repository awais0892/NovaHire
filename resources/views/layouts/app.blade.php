<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} | NovaHire</title>

    <!-- Scripts -->
    @include('partials.vite-assets')
    @livewireStyles

    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = savedTheme || systemTheme;
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

</head>

<body class="min-h-screen overflow-x-hidden bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100" x-data="{ loaded: true }">

    {{-- preloader --}}
    <x-common.preloader />
    {{-- preloader end --}}

    <div class="min-h-screen overflow-x-hidden bg-gray-50 dark:bg-gray-950 xl:flex">
        @include('layouts.sidebar')

        <div class="min-w-0 flex-1 transition-all duration-300 ease-in-out" :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
                'ml-0': $store.sidebar.isMobileOpen
            }">
            <!-- app header start -->
            @include('layouts.app-header')
            <!-- app header end -->
            <div class="mx-auto w-full max-w-(--breakpoint-2xl) min-w-0 p-4 text-gray-900 dark:text-gray-100 md:p-6">
                @if(isset($slot) && ($slot instanceof \Illuminate\Contracts\Support\Htmlable || is_string($slot)))
                    {{ $slot }}
                @endif
                @yield('content')
            </div>
            @include('layouts.app-footer')
        </div>

    </div>

    <!-- Global Toast -->
    <div x-cloak x-data class="fixed bottom-6 right-6 z-50">
        <div x-show="$store.toast.visible" x-transition.opacity.duration.150ms
            class="rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-lg"
            :class="{
                'bg-emerald-600': $store.toast.type === 'success' || !$store.toast.type,
                'bg-red-600': $store.toast.type === 'error',
                'bg-amber-500': $store.toast.type === 'warning'
            }">
            <span x-text="$store.toast.message"></span>
        </div>
        @stack('toasts')
    </div>

    @stack('scripts')
    @livewireScripts
</body>

</html>
