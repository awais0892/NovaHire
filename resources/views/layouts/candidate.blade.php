<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ config('app.name', 'NovaHire') }} - Jobs</title>
    @include('partials.vite-assets')
    @livewireStyles
</head>

<body class="min-h-screen bg-gray-50 dark:bg-gray-900">

    {{-- Navbar --}}
    <nav class="sticky top-0 z-50 bg-white shadow-sm dark:bg-gray-800">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">

                {{-- Logo --}}
                <a href="{{ route('jobs.index') }}" class="text-xl font-black text-blue-600">
                    {{ config('app.name', 'NovaHire') }}
                </a>

                {{-- Nav Links --}}
                <div class="hidden items-center gap-6 md:flex">
                    <a href="{{ route('jobs.index') }}" class="font-medium text-gray-600 transition hover:text-blue-600
                              {{ request()->routeIs('jobs.index') ? 'text-blue-600' : '' }}">
                        Browse Jobs
                    </a>
                    @auth
                        <a href="{{ route('candidate.applications') }}" class="font-medium text-gray-600 transition hover:text-blue-600
                                          {{ request()->routeIs('candidate.applications') ? 'text-blue-600' : '' }}">
                            My Applications
                        </a>
                    @endauth
                </div>

                {{-- Auth --}}
                <div class="flex items-center gap-3">
                    @auth
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-sm font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="hidden md:block">
                                <p class="text-sm font-semibold text-gray-700 dark:text-white">
                                    {{ auth()->user()->name }}
                                </p>
                            </div>
                            <div class="dropdown dropdown-end">
                                <button tabindex="0" class="btn btn-ghost btn-xs">Menu</button>
                                <ul tabindex="0" class="dropdown-content menu mt-2 w-48 rounded-box bg-white p-2 shadow dark:bg-gray-800">
                                    <li>
                                        <a href="{{ route('candidate.profile') }}">
                                            My Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('candidate.applications') }}">
                                            My Applications
                                        </a>
                                    </li>
                                    <li class="mt-1 border-t pt-1">
                                        <a href="{{ route('logout') }}" onclick="event.preventDefault();
                                                            document.getElementById('logout-form').submit();">
                                            Logout
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Login</a>
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm">
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Main --}}
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="mt-16 border-t bg-white py-8 dark:bg-gray-800">
        <div class="mx-auto max-w-7xl px-4 text-center text-sm text-gray-400">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'NovaHire') }}. Powered by AI hiring workflows.</p>
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')
</body>

</html>
