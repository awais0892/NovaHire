@extends('layouts.public')

@section('content')
    <section class="space-y-6">
        <div class="public-silk-shell rounded-3xl border border-slate-200 bg-white p-8 dark:border-slate-800 dark:bg-slate-900/60">
            <x-ui.public-silk tone="contact" intensity="0.92" />
            <div class="public-silk-content">
                <p class="public-silk-chip">Contact</p>
                <h1 class="mt-3 text-4xl font-bold text-slate-900 dark:text-white">Reach the NovaHire team</h1>
                <p class="mt-4 max-w-3xl text-lg text-slate-600 dark:text-slate-300">
                    Reach out for demos, enterprise onboarding, support, or partnerships.
                </p>
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900/60">
                <h2 class="text-lg font-semibold">Sales & Demos</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Need custom workflows or team onboarding help?</p>
                <a href="mailto:sales@novahire.example" class="mt-4 inline-block text-sm font-semibold text-brand-600 hover:underline">sales@novahire.example</a>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900/60">
                <h2 class="text-lg font-semibold">Support</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Platform issues, billing help, or account questions.</p>
                <a href="mailto:support@novahire.example" class="mt-4 inline-block text-sm font-semibold text-brand-600 hover:underline">support@novahire.example</a>
            </article>
        </div>
    </section>
@endsection
