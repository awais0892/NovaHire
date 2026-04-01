@extends('layouts.app')

@section('content')
<div class="p-6 max-w-[1600px] mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">AI Screening Pipeline</h1>
            <p class="mt-1 text-sm text-gray-500">Review AI-generated matching scores and analysis for candidate applications.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Total Applications</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $applications->count() }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">In Screening</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ $applications->where('status', 'screening')->count() }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">High Matches (80%+)</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $applications->where('ai_score', '>=', 80)->count() }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Shortlisted</p>
            <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $applications->where('status', 'shortlisted')->count() }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-5 py-4 font-semibold text-gray-500">Candidate</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Applied For</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">AI Score</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Status</th>
                        <th class="px-5 py-4 text-right font-semibold text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($applications as $app)
                        @php
                            $score = (int) ($app->ai_score ?? 0);
                            $scoreClass = $score >= 80
                                ? 'text-emerald-600'
                                : ($score >= 60 ? 'text-brand-600' : 'text-red-600');

                            $statusTone = match(strtolower((string) $app->status)) {
                                'shortlisted' => 'bg-emerald-100 text-emerald-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                'interview' => 'bg-amber-100 text-amber-700',
                                default => 'bg-blue-100 text-blue-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                                        {{ strtoupper(substr($app->candidate->name ?? 'C', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $app->candidate->name ?? 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500">{{ $app->candidate->email ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $app->jobListing->title ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $app->created_at?->diffForHumans() }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-bold {{ $scoreClass }}">{{ $score }}%</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold uppercase {{ $statusTone }}">
                                    {{ ucfirst((string) $app->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('recruiter.ai.screen', $app->id) }}"
                                    class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white hover:bg-indigo-700">
                                    View Analysis
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">
                                No applications found. AI screening results will appear here when candidates apply.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
