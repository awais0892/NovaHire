@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative flex flex-col items-center justify-center min-h-screen p-6 overflow-hidden bg-[#0A0F1C] font-sans">
        {{-- Animated Background Elements --}}
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/20 rounded-full blur-[120px] animate-pulse">
        </div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-600/20 rounded-full blur-[120px] animate-pulse"
            style="animation-delay: 2s;"></div>

        <div class="relative z-10 flex flex-col items-center max-w-2xl text-center">
            {{-- Glassmorphic 404 Text --}}
            <div class="relative group">
                <h1
                    class="text-[12rem] font-black leading-none tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-white/20 to-transparent select-none">
                    404
                </h1>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div
                        class="px-8 py-4 bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl transform rotate-[-2deg] group-hover:rotate-0 transition-transform duration-500">
                        <p class="text-2xl font-bold text-white tracking-widest uppercase">Lost in Orbit</p>
                    </div>
                </div>
            </div>

            <h2 class="mt-12 text-3xl font-bold text-white sm:text-4xl">
                Whoops! This page doesn't exist.
            </h2>

            <p class="mt-6 text-lg text-gray-400 max-w-md mx-auto leading-relaxed">
                The page you are looking for might have been moved, deleted, or never existed in this dimension.
            </p>

            {{-- Action Buttons --}}
            <div class="mt-12 flex flex-col sm:flex-row gap-4">
                <a href="{{ route('home') }}"
                    class="px-8 py-4 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl shadow-lg shadow-blue-600/20 transition-all transform hover:scale-105 active:scale-95 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Return Home
                </a>
                <button onclick="window.history.back()"
                    class="px-8 py-4 bg-white/5 hover:bg-white/10 text-white font-bold rounded-xl border border-white/10 backdrop-blur-md transition-all transform hover:scale-105 active:scale-95 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Go Back
                </button>
            </div>

            {{-- Technical Support --}}
            <div class="mt-16 pt-8 border-t border-white/5 w-full">
                <p class="text-sm text-gray-500 uppercase tracking-widest">
                    Support Code: <span class="text-blue-500/80 font-mono">ERR_PAGE_NOT_FOUND</span>
                </p>
            </div>
        </div>

        {{-- Floating UI Decorations --}}
        <div class="absolute top-1/4 right-10 w-24 h-24 bg-gradient-to-br from-blue-500/20 to-transparent rounded-full border border-white/5 backdrop-blur-3xl animate-bounce"
            style="animation-duration: 4s;"></div>
        <div class="absolute bottom-1/4 left-10 w-16 h-16 bg-gradient-to-br from-purple-500/20 to-transparent rounded-full border border-white/5 backdrop-blur-3xl animate-bounce"
            style="animation-duration: 3s;"></div>
    </div>

    <style>
        @keyframes pulse {

            0%,
            100% {
                opacity: 0.5;
                transform: scale(1);
            }

            50% {
                opacity: 0.8;
                transform: scale(1.1);
            }
        }
    </style>
@endsection