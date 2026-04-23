@php
    $nextSteps = [
        'Open the email we sent to your candidate account.',
        'Click the secure verification link to activate access.',
        'Return to NovaHire and continue from your candidate workspace.',
    ];

    $heroImage = 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1600&q=80';
@endphp

@extends('layouts.fullscreen-layout')

@section('content')
    <div class="min-h-screen w-full overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(70,95,255,0.15),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(34,197,94,0.12),transparent_28%)] bg-slate-950 text-slate-100">
        <div class="flex min-h-screen flex-col md:flex-row">
            <section class="relative flex flex-1 items-center justify-center overflow-y-auto bg-white px-6 py-10 text-slate-900 dark:bg-slate-950 dark:text-white md:px-10 lg:px-16">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(139,92,246,0.12),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(70,95,255,0.1),transparent_34%)]"></div>

                <div class="relative z-10 w-full max-w-[30rem]">
                    <a href="{{ route('login') }}" class="auth-animate auth-delay-100 mb-8 inline-flex items-center gap-2 text-sm text-slate-500 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Back to sign in
                    </a>

                    <div class="space-y-6">
                        <div class="space-y-3">
                            <p class="auth-animate auth-delay-100 text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                                Verify Candidate Email
                            </p>
                            <h1 class="auth-animate auth-delay-200 text-4xl font-semibold leading-tight tracking-tight text-slate-900 dark:text-white md:text-5xl">
                                Check your inbox
                            </h1>
                            <p class="auth-animate auth-delay-300 text-base text-slate-500 dark:text-slate-300">
                                We sent a secure verification link to activate your candidate workspace.
                            </p>
                        </div>

                        <div class="auth-animate auth-delay-400 rounded-2xl bg-slate-100 p-4 dark:bg-white/5">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Verification destination</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">
                                <span class="font-medium text-slate-900 dark:text-white">{{ $maskedEmail }}</span>
                            </p>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-300">
                                Check your inbox and spam folder. The verification link expires after 60 minutes.
                            </p>
                        </div>

                        @if (session('status'))
                            <div class="rounded-2xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-300">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div role="alert" aria-live="polite" class="rounded-2xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-300">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register.verify.resend') }}" x-data="{ resending: false }" @submit="resending = true" class="space-y-4">
                            @csrf
                            <input type="hidden" name="email" value="{{ $email }}">

                            <button type="submit" :disabled="resending" x-bind:aria-busy="resending.toString()" class="auth-animate auth-delay-500 inline-flex h-12 w-full items-center justify-center rounded-2xl bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-70">
                                <span x-text="resending ? 'Sending another verification email...' : 'Resend verification email'"></span>
                            </button>
                        </form>

                        <div class="auth-animate auth-delay-600 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4 text-sm text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                            <p>
                                Wrong email?
                                <a href="{{ route('register') }}" class="font-medium text-slate-900 underline decoration-slate-300 underline-offset-4 transition hover:text-brand-500 dark:text-white dark:decoration-white/20">Create another candidate account</a>
                                or
                                <a href="{{ route('login') }}" class="font-medium text-slate-900 underline decoration-slate-300 underline-offset-4 transition hover:text-brand-500 dark:text-white dark:decoration-white/20">return to sign in</a>.
                            </p>
                        </div>
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
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/70">Activation Steps</p>
                                <h2 class="max-w-xl text-4xl font-semibold leading-tight tracking-tight text-white lg:text-5xl">
                                    Finish setup in one quick step.
                                </h2>
                                <p class="max-w-lg text-base text-white/70">
                                    Email verification protects the candidate workspace before your first sign-in.
                                </p>
                            </div>
                        </div>

                        <div class="grid max-w-xl gap-4">
                            @foreach($nextSteps as $index => $step)
                                <article class="auth-testimonial-card w-full">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-sm font-semibold text-white">
                                        0{{ $index + 1 }}
                                    </div>
                                    <div class="text-sm leading-snug text-white/85">
                                        <p class="font-medium text-white">Step {{ $index + 1 }}</p>
                                        <p class="mt-1 text-white/65">{{ $step }}</p>
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
