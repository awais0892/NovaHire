<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'NovaHire') }}</title>
    @include('partials.vite-assets')
    @livewireStyles
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <style>body{min-height:100vh}</style>
    
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-900">
    <main class="min-h-screen flex items-center justify-center p-6">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
