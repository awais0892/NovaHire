@php
    $candidateHighlights = [
        [
            'title' => 'Profile stays ready',
            'text' => 'Keep your CV, links, and details ready for the next role.',
            'icon' => 'profile',
        ],
        [
            'title' => 'Follow each decision',
            'text' => 'Track interviews, updates, and hiring progress in one place.',
            'icon' => 'timeline',
        ],
    ];

    $heroImage = asset('images/optimized/vecteezy-businessman-1600.webp');
@endphp

@extends('layouts.fullscreen-layout')

@section('content')
<div class="h-[100svh] w-full overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(70,95,255,0.15),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(34,197,94,0.12),transparent_28%)] bg-slate-950 text-slate-100">
    <div class="grid h-full xl:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
        <section class="relative flex items-center justify-center bg-white px-6 py-6 text-slate-900 dark:bg-slate-950 dark:text-white md:px-10 lg:px-12 lg:py-8 xl:px-16">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(139,92,246,0.12),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(70,95,255,0.1),transparent_34%)]"></div>

            <div class="relative z-10 w-full max-w-[33rem]">
                <div class="mb-6 flex items-center justify-between gap-4 lg:mb-8">
                    <a href="{{ route('home') }}" class="auth-animate auth-delay-100 inline-flex items-center gap-2 text-sm text-slate-500 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Back to home
                    </a>
                    <p class="auth-animate auth-delay-100 text-sm text-slate-500 dark:text-slate-400">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-medium text-violet-500 transition hover:text-violet-400 hover:underline">Sign in</a>
                    </p>
                </div>

                <div class="space-y-4 lg:space-y-5">
                    <div class="space-y-3">
                        <div class="auth-animate auth-delay-200 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                Candidate account only
                            </span>
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                                Email verification required
                            </span>
                        </div>
                        <p class="auth-animate auth-delay-200 text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                            Candidate Onboarding
                        </p>
                        <h1 class="auth-animate auth-delay-300 text-4xl font-semibold leading-[1.04] tracking-tight text-slate-900 dark:text-white md:text-[3rem]">
                            Create your candidate account
                        </h1>
                        <p class="auth-animate auth-delay-400 max-w-xl text-[15px] leading-6 text-slate-500 dark:text-slate-300">
                            Secure onboarding for one clean workspace covering your profile, applications, and interview progress.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div role="alert" aria-live="polite" class="rounded-2xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-300">
                            Please review the highlighted fields and try again.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.post') }}" x-data="{ submitting: false, showPasswords: false }" @submit="submitting = true" class="space-y-4">
                        @csrf

                        <div class="auth-animate auth-delay-500 space-y-2">
                            <label for="name" class="text-sm font-medium text-slate-500 dark:text-slate-300">Full Name</label>
                            <div @class(['auth-glass-input', 'is-error' => $errors->has('name')])>
                                <input
                                    id="name"
                                    name="name"
                                    value="{{ old('name') }}"
                                    type="text"
                                    autocomplete="name"
                                    placeholder="Full name"
                                    required
                                    autofocus
                                    aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                                    aria-describedby="{{ $errors->has('name') ? 'name-error' : '' }}"
                                    class="w-full rounded-2xl bg-transparent px-4 py-3.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 dark:text-white" />
                            </div>
                            @error('name')
                                <div id="name-error" class="text-sm text-error-500">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="auth-animate auth-delay-600 space-y-2">
                            <label for="email" class="text-sm font-medium text-slate-500 dark:text-slate-300">Email Address</label>
                            <div @class(['auth-glass-input', 'is-error' => $errors->has('email')])>
                                <input
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    type="email"
                                    autocomplete="email"
                                    inputmode="email"
                                    placeholder="Email address"
                                    required
                                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                    aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                                    class="w-full rounded-2xl bg-transparent px-4 py-3.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 dark:text-white" />
                            </div>
                            @error('email')
                                <div id="email-error" class="text-sm text-error-500">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="auth-animate auth-delay-700 space-y-2">
                                <label for="password" class="text-sm font-medium text-slate-500 dark:text-slate-300">Password</label>
                                <div @class(['auth-glass-input relative', 'is-error' => $errors->has('password')])>
                                    <input
                                        id="password"
                                        name="password"
                                        :type="showPasswords ? 'text' : 'password'"
                                        autocomplete="new-password"
                                        minlength="8"
                                        placeholder="Create password"
                                        required
                                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                        aria-describedby="{{ $errors->has('password') ? 'password-error password-help' : 'password-help' }}"
                                        class="w-full rounded-2xl bg-transparent px-4 py-3.5 pr-12 text-sm text-slate-900 outline-none placeholder:text-slate-400 dark:text-white" />
                                    <button type="button" @click="showPasswords = !showPasswords" x-bind:aria-label="showPasswords ? 'Hide passwords' : 'Show passwords'" x-bind:aria-pressed="showPasswords.toString()" x-bind:title="showPasswords ? 'Hide passwords' : 'Show passwords'" class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition hover:text-slate-900 dark:hover:text-white">
                                        <svg x-show="!showPasswords" class="h-5 w-5 fill-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" fill="currentColor" />
                                        </svg>
                                        <svg x-show="showPasswords" class="h-5 w-5 fill-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" fill="currentColor" />
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <div id="password-error" class="text-sm text-error-500">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="auth-animate auth-delay-800 space-y-2">
                                <label for="password_confirmation" class="text-sm font-medium text-slate-500 dark:text-slate-300">Confirm Password</label>
                                <div @class(['auth-glass-input relative', 'is-error' => $errors->has('password_confirmation') || $errors->has('password')])>
                                    <input
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        :type="showPasswords ? 'text' : 'password'"
                                        autocomplete="new-password"
                                        minlength="8"
                                        placeholder="Confirm password"
                                        required
                                        aria-invalid="{{ $errors->has('password_confirmation') || $errors->has('password') ? 'true' : 'false' }}"
                                        aria-describedby="{{ trim(($errors->has('password_confirmation') ? 'password-confirmation-error ' : '') . ($errors->has('password') ? 'password-error ' : '') . 'password-help') }}"
                                        class="w-full rounded-2xl bg-transparent px-4 py-3.5 pr-12 text-sm text-slate-900 outline-none placeholder:text-slate-400 dark:text-white" />
                                    <button type="button" @click="showPasswords = !showPasswords" x-bind:aria-label="showPasswords ? 'Hide passwords' : 'Show passwords'" x-bind:aria-pressed="showPasswords.toString()" x-bind:title="showPasswords ? 'Hide passwords' : 'Show passwords'" class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition hover:text-slate-900 dark:hover:text-white">
                                        <svg x-show="!showPasswords" class="h-5 w-5 fill-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" fill="currentColor" />
                                        </svg>
                                        <svg x-show="showPasswords" class="h-5 w-5 fill-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" fill="currentColor" />
                                        </svg>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <div id="password-confirmation-error" class="text-sm text-error-500">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="auth-animate auth-delay-900 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
                            <p id="password-help">Use at least 8 characters.</p>
                            <div class="inline-flex items-center gap-2 font-medium">
                                <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                Verify your email before first sign in
                            </div>
                        </div>

                        <div class="auth-animate auth-delay-1000 grid gap-3 sm:grid-cols-2">
                            <button type="submit" :disabled="submitting" x-bind:aria-busy="submitting.toString()" class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-500 px-5 py-3.5 font-medium text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-70">
                                <span x-text="submitting ? 'Creating account...' : 'Create Candidate Account'"></span>
                            </button>

                            <a
                                href="{{ route('auth.google.redirect') }}"
                                class="flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 48 48">
                                    <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-2.641-.21-5.236-.389-3.917z"/>
                                    <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4c-7.682 0-14.344 4.337-17.694 10.691z"/>
                                    <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                                    <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-.792 2.237-2.231 4.166-4.087 5.571l6.19 5.238C42.022 35.026 44 30.038 44 24c0-2.641-.21-5.236-.389-3.917z"/>
                                </svg>
                                Continue with Google
                            </a>
                        </div>

                        <p class="auth-animate auth-delay-1000 text-xs leading-6 text-slate-500 dark:text-slate-400">
                            By creating an account or continuing with Google, you agree to the
                            <a href="{{ route('public.terms') }}" class="font-medium text-slate-900 underline decoration-slate-300 underline-offset-4 transition hover:text-brand-500 dark:text-white dark:decoration-white/20">Terms of Service</a>
                            and
                            <a href="{{ route('public.privacy') }}" class="font-medium text-slate-900 underline decoration-slate-300 underline-offset-4 transition hover:text-brand-500 dark:text-white dark:decoration-white/20">Privacy Policy</a>.
                        </p>
                    </form>
                </div>
            </div>
        </section>

        <section class="relative hidden p-4 xl:block">
            <div class="auth-slide-right absolute inset-4 overflow-hidden rounded-[2rem] border border-white/10 bg-slate-900 shadow-2xl">
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $heroImage }}');"></div>
                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(2,6,23,0.12),rgba(2,6,23,0.42),rgba(2,6,23,0.9))]"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(70,95,255,0.18),transparent_28%),radial-gradient(circle_at_bottom_right,rgba(15,23,42,0.38),transparent_22%)]"></div>

                <div class="relative z-10 flex h-full flex-col justify-between p-8 xl:p-10">
                    <div class="max-w-lg space-y-5">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white/90 backdrop-blur-xl">
                            <img src="/images/logo/novahire-mark-light.svg" alt="NovaHire" class="h-6 w-6 rounded-lg object-cover">
                            NovaHire
                        </a>
                        <div class="space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/70">Candidate Workspace</p>
                            <h2 class="max-w-xl text-4xl font-semibold leading-tight tracking-tight text-white lg:text-5xl">
                                Keep the next step clear.
                            </h2>
                            <p class="max-w-lg text-base text-white/70">
                                A focused workspace for your profile, applications, and interview momentum.
                            </p>
                        </div>
                    </div>

                    <div class="grid max-w-xl gap-4 md:grid-cols-2">
                        @foreach($candidateHighlights as $highlight)
                            <article class="flex items-start gap-3 rounded-[1.5rem] border border-white/10 bg-white/10 p-4 text-sm backdrop-blur-xl">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white">
                                    @if ($highlight['icon'] === 'timeline')
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4.16699 5.83325H15.8337M4.16699 9.99992H11.667M4.16699 14.1666H9.16699" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                        </svg>
                                    @elseif ($highlight['icon'] === 'profile')
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M10 10.4167C12.0711 10.4167 13.75 8.73773 13.75 6.66667C13.75 4.5956 12.0711 2.91667 10 2.91667C7.92893 2.91667 6.25 4.5956 6.25 6.66667C6.25 8.73773 7.92893 10.4167 10 10.4167Z" stroke="currentColor" stroke-width="1.5" />
                                            <path d="M3.75 16.6667C4.73131 14.0962 7.17578 12.2917 10 12.2917C12.8242 12.2917 15.2687 14.0962 16.25 16.6667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4.16699 14.1667V10.8334M8.33366 14.1667V6.66675M12.5003 14.1667V9.16675M16.667 14.1667V5.00008" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="text-sm leading-snug text-white/85">
                                    <p class="font-medium text-white">{{ $highlight['title'] }}</p>
                                    <p class="mt-1 text-white/65">{{ $highlight['text'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
