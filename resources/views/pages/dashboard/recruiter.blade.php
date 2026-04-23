@extends('layouts.app')

@section('content')
<div class="p-6 max-w-[1600px] mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Recruiter Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Track jobs, pipeline health, and AI matching performance.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('recruiter.jobs.create') }}" class="inline-flex h-10 items-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                Post Job
            </a>
            <a href="{{ route('recruiter.applications') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                View Pipeline
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Open Jobs</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $metrics['open_jobs'] ?? 0 }}</p>
        </div>
        <div class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Total Candidates</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ $metrics['total_candidates'] ?? 0 }}</p>
        </div>
        <div class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">High Match Scores</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $metrics['ai_matches'] ?? 0 }}</p>
        </div>
        <div class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Interviews</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $metrics['interviews'] ?? 0 }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-9">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="app-card app-card-body">
                    <h3 class="font-semibold text-gray-800 dark:text-white">Application Trends (Last 7 Days)</h3>
                    <div class="mt-4 h-64">
                        <canvas id="appsOverTimeChart"></canvas>
                    </div>
                </div>

                <div class="app-card app-card-body">
                    <h3 class="font-semibold text-gray-800 dark:text-white">AI Score Distribution</h3>
                    <div class="mt-4 h-64">
                        <canvas id="scoreDistChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <h3 class="font-semibold text-gray-800 dark:text-white">Recent Applications</h3>
                    <a href="{{ route('recruiter.applications') }}" class="text-sm font-medium text-brand-500 hover:text-brand-600">View All</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-5 py-4 font-semibold text-gray-500">Candidate</th>
                                <th class="px-5 py-4 font-semibold text-gray-500">Role</th>
                                <th class="px-5 py-4 font-semibold text-gray-500">AI Score</th>
                                <th class="px-5 py-4 font-semibold text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($recent_applications ?? [] as $app)
                                @php
                                    $score = (int) ($app->ai_score ?? 0);
                                    $scoreClass = $score >= 80
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : ($score >= 60 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700');

                                    $statusTone = match(strtolower((string) $app->status)) {
                                        'hired' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        'interview' => 'bg-amber-100 text-amber-700',
                                        'shortlisted' => 'bg-blue-100 text-blue-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                                                {{ substr($app->candidate->name ?? 'C', 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $app->candidate->name ?? 'Unknown' }}</p>
                                                <p class="text-xs text-gray-500">{{ $app->created_at?->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $app->jobListing->title ?? 'Unknown Role' }}</td>
                                    <td class="px-5 py-4">
                                        @if($app->ai_score)
                                            <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $scoreClass }}">{{ $score }}%</span>
                                        @else
                                            <span class="text-gray-400">Processing...</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold uppercase {{ $statusTone }}">
                                            {{ ucfirst((string) $app->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500">No recent applications yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-3">
            <div class="app-card app-card-body">
                <h3 class="font-semibold text-gray-800 dark:text-white">Quick Actions</h3>
                <div class="mt-4 grid grid-cols-1 gap-2">
                    <a href="{{ route('recruiter.jobs.create') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                        Post New Job
                    </a>
                    <a href="{{ route('recruiter.jobs.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Manage Jobs
                    </a>
                    <a href="{{ route('recruiter.ai.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        AI Screening
                    </a>
                </div>
            </div>

            <div class="app-card app-card-body">
                <h3 class="font-semibold text-gray-800 dark:text-white">System Status</h3>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">AI Engine</span>
                        <span class="inline-flex items-center gap-1 font-medium text-emerald-600">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Online
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Parsing Service</span>
                        <span class="font-medium text-emerald-600">Healthy</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', async function () {
                const appsCtx = document.getElementById('appsOverTimeChart');
                const scoreCtx = document.getElementById('scoreDistChart');
                if (!appsCtx || !scoreCtx) return;

                const ChartCtor = typeof window.ensureChartJs === 'function'
                    ? await window.ensureChartJs()
                    : window.Chart;
                if (typeof ChartCtor !== 'function') return;

                new ChartCtor(appsCtx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($chart_data['labels']) !!},
                        datasets: [{
                            label: 'Applications',
                            data: {!! json_encode($chart_data['data']) !!},
                            borderColor: '#465fff',
                            backgroundColor: 'rgba(70,95,255,0.12)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 3,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.2)' } },
                            x: { grid: { display: false } },
                        }
                    }
                });

                new ChartCtor(scoreCtx, {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode(array_keys($score_dist)) !!},
                        datasets: [{
                            data: {!! json_encode(array_values($score_dist)) !!},
                            backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#6b7280'],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } }
                        }
                    }
                });
            });
        </script>
    @endpush
</div>
@endsection
