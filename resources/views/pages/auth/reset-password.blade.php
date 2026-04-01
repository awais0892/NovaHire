@extends('layouts.fullscreen-layout')

@section('content')
    <div class="min-h-screen w-full bg-slate-950 px-4 py-10 text-slate-100">
        <div class="mx-auto w-full max-w-md">
            <a href="{{ route('login') }}" class="mb-6 inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white">
                <svg class="h-4 w-4 stroke-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Back to sign in
            </a>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-xl backdrop-blur">
                <h1 class="text-2xl font-semibold text-white">Reset Password</h1>
                <p class="mt-2 text-sm text-slate-300">
                    Set a new password for your account.
                </p>

                @if($errors->any())
                    <div class="mt-4 rounded-lg border border-error-500/30 bg-error-500/10 px-3 py-2 text-sm text-error-300">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-slate-300">Email Address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $email) }}"
                            autocomplete="email"
                            class="h-12 w-full rounded-xl border border-white/15 bg-slate-900 px-4 text-white outline-none placeholder:text-slate-500 focus:border-brand-500"
                        />
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-slate-300">New Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            class="h-12 w-full rounded-xl border border-white/15 bg-slate-900 px-4 text-white outline-none placeholder:text-slate-500 focus:border-brand-500"
                        />
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-300">Confirm Password</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="h-12 w-full rounded-xl border border-white/15 bg-slate-900 px-4 text-white outline-none placeholder:text-slate-500 focus:border-brand-500"
                        />
                    </div>

                    <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

