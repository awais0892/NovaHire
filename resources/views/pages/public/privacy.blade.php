@extends('layouts.public')

@section('content')
    <section class="space-y-6">
        <div class="public-silk-shell rounded-3xl border border-slate-200 bg-white p-8 dark:border-slate-800 dark:bg-slate-900/60">
            <x-ui.public-silk tone="legal" intensity="0.82" />
            <div class="public-silk-content">
                <p class="public-silk-chip">Privacy Policy</p>
                <h1 class="mt-3 text-4xl font-bold text-slate-900 dark:text-white">Privacy Policy</h1>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Effective date: {{ now()->format('d M Y') }}</p>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 text-sm leading-7 text-slate-700 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300">
            <p>NovaHire processes account, hiring workflow, and candidate profile data to deliver recruitment features.</p>
            <p>We use collected data to authenticate users, support hiring workflows, send notifications, and improve product quality.</p>
            <p>Customer data is retained according to account lifecycle, billing records, and legal obligations.</p>
            <p>If you have privacy requests, contact <a href="mailto:privacy@novahire.example" class="text-brand-600 hover:underline">privacy@novahire.example</a>.</p>
        </div>
    </section>
@endsection
