@extends('layouts.fullscreen-layout')

@section('content')
    @php
        $testimonials = [
            [
                'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=200&q=80',
                'name' => 'Sarah Chen',
                'handle' => '@sarahdigital',
                'text' => 'Amazing platform. The hiring flow is clean, fast, and easy for the whole team.',
            ],
            [
                'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=200&q=80',
                'name' => 'Marcus Johnson',
                'handle' => '@marcustech',
                'text' => 'The experience feels premium and practical. We got faster decisions immediately.',
            ],
            [
                'avatar' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=200&q=80',
                'name' => 'David Martinez',
                'handle' => '@davidcreates',
                'text' => 'Best-looking ATS sign-in flow I have used in a while. Simple and effective.',
            ],
        ];
        $heroImage = 'https://images.unsplash.com/photo-1642615835477-d303d7dc9ee9?auto=format&fit=crop&w=1600&q=80';
    @endphp

    <div class="min-h-screen w-full overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(70,95,255,0.15),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(34,197,94,0.12),transparent_28%)] bg-slate-950 text-slate-100">
        <div class="flex min-h-screen flex-col md:flex-row">
            <section class="relative flex flex-1 items-center justify-center bg-white px-6 py-10 text-slate-900 dark:bg-slate-950 dark:text-white md:px-10 lg:px-16">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(139,92,246,0.12),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(70,95,255,0.1),transparent_34%)]"></div>

                <div class="relative z-10 w-full max-w-md">
                    <a href="{{ route('home') }}" class="auth-animate auth-delay-100 mb-8 inline-flex items-center gap-2 text-sm text-slate-500 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Back to home
                    </a>

                    <div class="space-y-6">
                        <div class="space-y-3">
                            <h1 class="auth-animate auth-delay-200 text-4xl font-semibold leading-tight tracking-tight text-slate-900 dark:text-white md:text-5xl">
                                <span class="font-light">Welcome</span>
                            </h1>
                            <p class="auth-animate auth-delay-300 text-base text-slate-500 dark:text-slate-300">
                                Access your account and continue your hiring journey with NovaHire.
                            </p>
                        </div>

                        @if (session('status'))
                            <div class="rounded-2xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-300">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                            @csrf

                            <div class="auth-animate auth-delay-400 space-y-2">
                                <label for="email" class="text-sm font-medium text-slate-500 dark:text-slate-300">Email Address</label>
                                <div class="auth-glass-input">
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value="{{ old('email') }}"
                                        placeholder="Enter your email address"
                                        class="w-full rounded-2xl bg-transparent px-4 py-4 text-sm text-slate-900 outline-none placeholder:text-slate-400 dark:text-white"
                                    />
                                </div>
                                @error('email')
                                    <div class="text-sm text-error-500">{{ $message }}</div>
                                @enderror
                            </div>

                            <div x-data="{ showPassword: false }" class="auth-animate auth-delay-500 space-y-2">
                                <label for="password" class="text-sm font-medium text-slate-500 dark:text-slate-300">Password</label>
                                <div class="auth-glass-input relative">
                                    <input
                                        id="password"
                                        name="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Enter your password"
                                        class="w-full rounded-2xl bg-transparent px-4 py-4 pr-12 text-sm text-slate-900 outline-none placeholder:text-slate-400 dark:text-white"
                                    />
                                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition hover:text-slate-900 dark:hover:text-white">
                                        <svg x-show="!showPassword" class="h-5 w-5 fill-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" fill="currentColor" />
                                        </svg>
                                        <svg x-show="showPassword" class="h-5 w-5 fill-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" fill="currentColor" />
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="text-sm text-error-500">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="auth-animate auth-delay-600 flex items-center justify-between gap-4 text-sm">
                                <label class="flex cursor-pointer items-center gap-3">
                                    <input type="checkbox" name="remember" class="auth-checkbox" />
                                    <span class="text-slate-700 dark:text-slate-200">Keep me signed in</span>
                                </label>
                                <a href="{{ route('password.request') }}" class="text-violet-500 transition hover:text-violet-400 hover:underline">
                                    Reset password
                                </a>
                            </div>

                            <button type="submit" class="auth-animate auth-delay-700 w-full rounded-2xl bg-brand-500 py-4 font-medium text-white transition hover:bg-brand-600">
                                Sign In
                            </button>
                        </form>

                        <div class="auth-animate auth-delay-800 relative flex items-center justify-center">
                            <span class="w-full border-t border-slate-200 dark:border-white/10"></span>
                            <span class="absolute bg-white px-4 text-sm text-slate-400 dark:bg-slate-950 dark:text-slate-500">Or continue with</span>
                        </div>

                        <a href="{{ route('auth.google.redirect') }}" class="auth-animate auth-delay-900 flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 py-4 text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 48 48">
                                <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-2.641-.21-5.236-.389-3.917z"/>
                                <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4c-7.682 0-14.344 4.337-17.694 10.691z"/>
                                <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                                <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-.792 2.237-2.231 4.166-4.087 5.571l6.19 5.238C42.022 35.026 44 30.038 44 24c0-2.641-.21-5.236-.389-3.917z"/>
                            </svg>
                            Continue with Google
                        </a>

                        <p class="auth-animate auth-delay-1000 text-center text-sm text-slate-500 dark:text-slate-400">
                            New to our platform?
                            <a href="{{ route('register') }}" class="text-violet-500 transition hover:text-violet-400 hover:underline">
                                Create Account
                            </a>
                        </p>
                    </div>
                </div>
            </section>

            <section class="relative hidden flex-1 p-4 md:block">
                <div class="auth-slide-right absolute inset-4 overflow-hidden rounded-[2rem] border border-white/10 bg-slate-900 shadow-2xl">
                    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $heroImage }}');"></div>
                    <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(2,6,23,0.15),rgba(2,6,23,0.55),rgba(2,6,23,0.92))]"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(139,92,246,0.24),transparent_28%),radial-gradient(circle_at_bottom_right,rgba(34,197,94,0.18),transparent_22%)]"></div>

                    <div class="relative z-10 flex h-full flex-col justify-between p-8 lg:p-10">
                        <div class="max-w-lg space-y-5">
                            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white/90 backdrop-blur-xl">
                                <img src="/images/logo/novahire-mark-light.svg" alt="NovaHire" class="h-6 w-6 rounded-lg object-cover">
                                NovaHire
                            </a>
                            <div class="space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/70">AI Hiring Platform</p>
                                <h2 class="max-w-xl text-4xl font-semibold leading-tight tracking-tight text-white lg:text-5xl">
                                    Screen, rank, and hire with a cleaner workflow.
                                </h2>
                                <p class="max-w-lg text-base text-white/70">
                                    Move from candidate review to interview decisions with faster collaboration and better signal quality.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-end justify-center gap-4 xl:flex-nowrap">
                            @foreach($testimonials as $index => $testimonial)
                                <article class="auth-testimonial-card {{ $index === 0 ? 'auth-delay-1000' : ($index === 1 ? 'auth-delay-1200 hidden xl:flex' : 'auth-delay-1400 hidden 2xl:flex') }}">
                                    <img src="{{ $testimonial['avatar'] }}" class="h-10 w-10 rounded-2xl object-cover" alt="{{ $testimonial['name'] }}">
                                    <div class="text-sm leading-snug text-white/85">
                                        <p class="font-medium text-white">{{ $testimonial['name'] }}</p>
                                        <p class="text-white/55">{{ $testimonial['handle'] }}</p>
                                        <p class="mt-1">{{ $testimonial['text'] }}</p>
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
