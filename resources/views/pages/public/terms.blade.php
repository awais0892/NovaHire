@extends('layouts.public')

@section('content')
    <section class="space-y-6">
        <div class="public-silk-shell rounded-3xl border border-slate-200 bg-white p-8 dark:border-slate-800 dark:bg-slate-900/60">
            <x-ui.public-silk tone="legal" intensity="0.82" />
            <div class="public-silk-content">
                <p class="public-silk-chip">Terms of Service</p>
                <h1 class="mt-3 text-4xl font-bold text-slate-900 dark:text-white">Terms of Service</h1>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Effective date: {{ now()->format('d M Y') }}</p>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 text-sm leading-7 text-slate-700 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300">
            <p>By using NovaHire, you agree to these terms and applicable laws.</p>
            <p>Accounts must provide accurate information and protect access credentials.</p>
            <p>Customers are responsible for lawful processing of candidate and hiring data within the platform.</p>
            <p>NovaHire may suspend accounts for misuse, abuse, or unpaid billing obligations.</p>
            <p>For legal questions, contact <a href="mailto:legal@novahire.example" class="text-brand-600 hover:underline">legal@novahire.example</a>.</p>
        </div>
    </section>
@endsection
