@extends('layouts.app')

@section('content')
<div class="p-6 max-w-[1600px] mx-auto space-y-6">
    <section class="app-card p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">AI Insights</h1>
                <p class="mt-1 text-sm text-gray-500">Platform-wide AI usage, quality signals, and recent analysis runs.</p>
            </div>
            <div class="flex items-center gap-2">
                @foreach([7, 30, 90] as $d)
                    <a href="{{ route('admin.ai.insights', ['days' => $d]) }}"
                        class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-semibold transition {{ $periodDays === $d ? 'bg-brand-500 text-white' : 'border border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                        {{ $d }}d
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Analyses</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format((int) $summary['total_analyses']) }}</p>
        </article>
        <article class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Tokens Used</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ number_format((int) $summary['tokens_used']) }}</p>
        </article>
        <article class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Avg Match Score</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $summary['avg_match_score'] }}%</p>
        </article>
        <article class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Applications Processed</p>
            <p class="mt-2 text-3xl font-bold text-indigo-600">{{ number_format((int) $summary['applications_processed']) }}</p>
        </article>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="app-card app-card-body xl:col-span-2">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Daily AI Usage</h2>
            <p class="mt-1 text-xs text-gray-500">Tokens and run volume over the selected period.</p>
            <div class="mt-4 h-72">
                <canvas id="adminAiUsageChart"></canvas>
            </div>
        </div>

        <div class="app-card app-card-body">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recommendation Split</h2>
            <p class="mt-1 text-xs text-gray-500">Distribution of model recommendations.</p>
            <div class="mt-4 h-48">
                <canvas id="adminAiRecoChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @forelse($recommendationBreakdown as $item)
                    <div class="app-subcard glow-subcard flex items-center justify-between px-3 py-2 text-sm" data-glow-card data-glow-proximity="84">
                        <span class="font-medium uppercase text-gray-600 dark:text-gray-300">{{ str_replace('_', ' ', $item->recommendation ?: 'unknown') }}</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ (int) $item->count }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No recommendation data.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="app-card">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent AI Runs</h2>
            <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Latest 15</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-5 py-4 font-semibold text-gray-500">Candidate</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Role</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Score</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Recommendation</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Tokens</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($recentAnalyses as $analysis)
                        @php
                            $score = (int) ($analysis->match_score ?? 0);
                            $reco = strtolower((string) ($analysis->recommendation ?? 'maybe'));
                            $recoClass = match($reco) {
                                'strong_yes', 'yes' => 'bg-emerald-100 text-emerald-700',
                                'maybe' => 'bg-amber-100 text-amber-700',
                                default => 'bg-red-100 text-red-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $analysis->candidate->name ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $analysis->jobListing->title ?? '-' }}</td>
                            <td class="px-5 py-4">
                                <span class="font-semibold {{ $score >= 80 ? 'text-emerald-600' : ($score >= 60 ? 'text-amber-600' : 'text-red-600') }}">{{ $score }}%</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold uppercase {{ $recoClass }}">{{ str_replace('_', ' ', $reco) }}</span>
                            </td>
                            <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ number_format((int) ($analysis->tokens_used ?? 0)) }}</td>
                            <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $analysis->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">No AI analysis records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const usageEl = document.getElementById('adminAiUsageChart');
        const recoEl = document.getElementById('adminAiRecoChart');
        if (!usageEl || !recoEl || typeof Chart === 'undefined') return;

        const usageLabels = {!! json_encode($dailyUsage->pluck('date')->values()) !!};
        const usageTokens = {!! json_encode($dailyUsage->pluck('tokens')->map(fn($v) => (int) $v)->values()) !!};
        const usageRuns = {!! json_encode($dailyUsage->pluck('runs')->map(fn($v) => (int) $v)->values()) !!};

        new Chart(usageEl, {
            data: {
                labels: usageLabels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Tokens',
                        data: usageTokens,
                        backgroundColor: 'rgba(70, 95, 255, 0.25)',
                        borderColor: '#465fff',
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'yTokens'
                    },
                    {
                        type: 'line',
                        label: 'Runs',
                        data: usageRuns,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.15)',
                        tension: 0.35,
                        pointRadius: 3,
                        borderWidth: 2,
                        yAxisID: 'yRuns'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    yTokens: {
                        beginAtZero: true,
                        position: 'left',
                        grid: { color: 'rgba(148, 163, 184, 0.2)' }
                    },
                    yRuns: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { display: false }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        const recoLabels = {!! json_encode($recommendationBreakdown->pluck('recommendation')->map(fn($v) => str_replace('_', ' ', $v ?: 'unknown'))->values()) !!};
        const recoData = {!! json_encode($recommendationBreakdown->pluck('count')->map(fn($v) => (int) $v)->values()) !!};

        new Chart(recoEl, {
            type: 'doughnut',
            data: {
                labels: recoLabels,
                datasets: [{
                    data: recoData,
                    backgroundColor: ['#10b981', '#465fff', '#f59e0b', '#ef4444', '#6b7280'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }
                }
            }
        });
    });
</script>
@endpush
@endsection
