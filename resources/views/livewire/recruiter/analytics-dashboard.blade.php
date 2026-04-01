{{-- resources/views/livewire/recruiter/analytics-dashboard.blade.php --}}
<div
    x-data="analyticsCharts()"
    x-init="initCharts()"
    @charts-refresh.window="refreshCharts()"
    class="space-y-8 animate-fade-in pb-10"
>
    {{-- ── Header ── --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Recruitment Analytics</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Deep insights into your hiring performance and AI-powered metrics</p>
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="bg-white/50 dark:bg-gray-800/50 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 rounded-xl px-3 py-1 flex items-center gap-3 shadow-sm">
                <select wire:model.live="dateRange" class="select select-ghost select-sm font-bold focus:ring-0">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">Last 12 months</option>
                </select>

                <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>

                <select wire:model.live="jobFilter" class="select select-ghost select-sm font-bold focus:ring-0 max-w-[200px]">
                    <option value="all">All Channels</option>
                    @foreach($jobs as $job)
                    <option value="{{ $job->id }}">{{ str($job->title)->limit(20) }}</option>
                    @endforeach
                </select>
            </div>

            <button onclick="window.print()" class="btn bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-white rounded-xl h-10 px-4 shadow-sm transition-all active:scale-95 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span class="font-bold text-xs uppercase tracking-widest">Export Report</span>
            </button>
        </div>
    </div>

    {{-- ── KPI Cards ── --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">

        @php
        $kpiCards = [
            ['label' => 'Total Jobs',       'value' => $kpis['totalJobs'],        'sub' => $kpis['activeJobs'] . ' active',          'icon' => '💼', 'color' => 'from-blue-500 to-indigo-600'],
            ['label' => 'Applications',     'value' => $kpis['totalApps'],        'sub' => 'last period',                     'icon' => '📥', 'color' => 'from-purple-500 to-pink-600'],
            ['label' => 'Shortlisted',      'value' => $kpis['totalShortlisted'], 'sub' => 'qualities',                             'icon' => '⭐', 'color' => 'from-yellow-400 to-orange-500'],
            ['label' => 'Hires',            'value' => $kpis['totalHired'],       'sub' => $kpis['conversionRate'] . '% rate', 'icon' => '🎉', 'color' => 'from-green-500 to-emerald-600'],
            ['label' => 'Avg Score',        'value' => number_format($kpis['avgScore'] ?? 0, 1), 'sub' => 'out of 100',             'icon' => '🤖', 'color' => 'from-indigo-500 to-blue-600'],
            ['label' => 'Time to Hire',     'value' => round($kpis['timeToHire'] ?? 0) . 'd',  'sub' => 'avg cycle',          'icon' => '⏱️', 'color' => 'from-orange-500 to-red-600'],
        ];
        @endphp

        @foreach($kpiCards as $card)
        <div class="group relative bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700/50 p-5 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div class="flex flex-col h-full justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">{{ $card['label'] }}</span>
                        <div class="p-2 rounded-lg bg-gradient-to-br {{ $card['color'] }} shadow-lg opacity-80 group-hover:opacity-100 transition-opacity">
                            <span class="text-base leading-none">{{ $card['icon'] }}</span>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white">
                        {{ $card['value'] }}
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-1.5">
                    <div class="w-1 h-1 rounded-full bg-blue-500"></div>
                    <span class="text-[10px] font-bold text-gray-400">{{ $card['sub'] }}</span>
                </div>
            </div>
            {{-- Decorative glow --}}
            <div class="absolute -inset-px bg-gradient-to-br {{ $card['color'] }} rounded-2xl opacity-0 group-hover:opacity-5 transition-opacity pointer-events-none"></div>
        </div>
        @endforeach
    </div>

    {{-- ── Tabs ── --}}
    <div class="flex space-x-1 bg-gray-100/50 dark:bg-gray-900/30 backdrop-blur-xl p-1.5 rounded-2xl w-fit border border-gray-200 dark:border-gray-700/50">
        @foreach([
            ['key' => 'overview',  'label' => '📊 Overview'],
            ['key' => 'funnel',    'label' => '🔽 Funnel'],
            ['key' => 'ai',        'label' => '🤖 AI Metrics'],
            ['key' => 'skills',    'label' => '🎯 Skills'],
            ['key' => 'time',      'label' => '⏱️ Speed'],
        ] as $tab)
        <button wire:click="setTab('{{ $tab['key'] }}')"
                class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all
                       {{ $activeTab === $tab['key'] 
                          ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' 
                          : 'text-gray-500 hover:text-gray-800 dark:hover:text-white hover:bg-white/50 dark:hover:bg-gray-800/50' }}">
            {{ $tab['label'] }}
        </button>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════════════
         TAB CONTENT
    ══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

        @if($activeTab === 'overview')
            {{-- Applications Over Time --}}
            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-200 dark:border-gray-700/50 p-8 rounded-3xl shadow-xl md:col-span-2">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="font-black text-xl text-gray-900 dark:text-white uppercase tracking-tight">Application Momentum</h3>
                        <p class="text-sm text-gray-500">Volume of incoming talent over the selected period</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-600"></div>
                        <span class="text-xs font-bold text-gray-500 uppercase">Apply Volume</span>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="applicationsChart"></canvas>
                </div>
            </div>

            {{-- Applications By Job --}}
            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-200 dark:border-gray-700/50 p-8 rounded-3xl shadow-xl">
                <h3 class="font-black text-xl text-gray-900 dark:text-white uppercase tracking-tight mb-8">Demand by Position</h3>
                <div class="h-72">
                    <canvas id="jobsChart"></canvas>
                </div>
            </div>

            {{-- Recommendation Breakdown --}}
            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-200 dark:border-gray-700/50 p-8 rounded-3xl shadow-xl">
                <h3 class="font-black text-xl text-gray-900 dark:text-white uppercase tracking-tight mb-8">AI Verdict Distribution</h3>
                <div class="h-72 flex items-center justify-center">
                    <canvas id="recommendationChart"></canvas>
                </div>
            </div>
        @endif

        @if($activeTab === 'funnel')
            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-200 dark:border-gray-700/50 p-8 rounded-3xl shadow-xl md:col-span-2">
                <h3 class="font-black text-xl text-gray-900 dark:text-white uppercase tracking-tight mb-10 text-center">Recruitment Velocity Funnel</h3>

                @php
                    $funnelStages = [
                        'applied'     => ['label' => 'Applied',     'icon' => '📥', 'color' => 'bg-blue-600'],
                        'screening'   => ['label' => 'Screening',   'icon' => '🔍', 'color' => 'bg-indigo-600'],
                        'shortlisted' => ['label' => 'Shortlisted', 'icon' => '⭐', 'color' => 'bg-purple-600'],
                        'interview'   => ['label' => 'Interview',   'icon' => '💬', 'color' => 'bg-yellow-500'],
                        'offer'       => ['label' => 'Offer',       'icon' => '🤝', 'color' => 'bg-orange-600'],
                        'hired'       => ['label' => 'Hired',       'icon' => '🎉', 'color' => 'bg-green-600'],
                    ];
                    $maxVal = max(array_values($funnelData) ?: [1]);
                @endphp

                <div class="space-y-6 max-w-4xl mx-auto">
                    @foreach($funnelStages as $stage => $config)
                    @php
                        $count      = $funnelData[$stage] ?? 0;
                        $width      = $maxVal > 0 ? round(($count / $maxVal) * 100) : 0;
                        $prevStage  = array_keys($funnelStages)[max(0, array_search($stage, array_keys($funnelStages)) - 1)];
                        $prevCount  = $funnelData[$prevStage] ?? $count;
                        $dropOff    = $prevCount > 0 && $stage !== 'applied'
                            ? round((1 - $count / $prevCount) * 100) : 0;
                    @endphp
                    <div class="flex items-center gap-6">
                        <div class="w-32 text-right">
                            <span class="text-xs font-black uppercase tracking-widest text-gray-400">{{ $config['label'] }}</span>
                        </div>
                        <div class="flex-1 relative py-1">
                            <div class="bg-gray-100 dark:bg-gray-900/50 rounded-full h-10 overflow-hidden shadow-inner">
                                <div class="relative {{ $config['color'] }} h-full rounded-full flex items-center px-4 transition-all duration-1000 ease-out"
                                     style="width: {{ $width }}%">
                                    <span class="text-white text-sm font-black">{{ $count }}</span>
                                    {{-- Glare effect --}}
                                    <div class="absolute inset-0 bg-white/10 skew-x-[-20deg]"></div>
                                </div>
                            </div>
                        </div>
                        <div class="w-32">
                            @if($dropOff > 0)
                            <div class="flex items-center gap-1.5 text-red-500 font-black italic text-xs">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                {{ $dropOff }}% DROP
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-200 dark:border-gray-700/50 p-8 rounded-3xl shadow-xl md:col-span-2">
                <h3 class="font-black text-xl text-gray-900 dark:text-white uppercase tracking-tight mb-8">Pipeline Composition</h3>
                <div class="h-80 flex justify-center">
                    <canvas id="funnelDoughnut"></canvas>
                </div>
            </div>
        @endif

        @if($activeTab === 'ai')
            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach([
                    ['label' => 'Screenings', 'value' => $kpis['totalAiRuns'], 'unit' => 'Runs', 'color' => 'purple'],
                    ['label' => 'Tokens', 'value' => number_format($kpis['totalTokens']), 'unit' => 'Units', 'color' => 'blue'],
                    ['label' => 'Avg Score', 'value' => number_format($kpis['avgScore'] ?? 0, 1), 'unit' => '/ 100', 'color' => 'emerald'],
                ] as $stat)
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-8 rounded-3xl shadow-sm text-center">
                    <span class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2 block">{{ $stat['label'] }}</span>
                    <div class="text-5xl font-black text-{{ $stat['color'] }}-600 mb-2">{{ $stat['value'] }}</div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $stat['unit'] }} Used</span>
                </div>
                @endforeach
            </div>

            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-200 dark:border-gray-700/50 p-8 rounded-3xl shadow-xl md:col-span-2">
                <h3 class="font-black text-xl text-gray-900 dark:text-white uppercase tracking-tight mb-8">Candidate Match Spectrum</h3>
                <div class="h-80">
                    <canvas id="scoreChart"></canvas>
                </div>
            </div>
        @endif

        @if($activeTab === 'skills')
            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-200 dark:border-gray-700/50 p-8 rounded-3xl shadow-xl">
                <h3 class="font-black text-xl text-gray-900 dark:text-white uppercase tracking-tight mb-8">Asset Inventory (Top Skills)</h3>
                @php $maxSkill = max(array_values($topSkills) ?: [1]); @endphp
                <div class="space-y-4">
                    @forelse($topSkills as $skill => $count)
                    <div class="group">
                        <div class="flex items-center justify-between mb-1.5 px-1">
                            <span class="text-xs font-black uppercase text-gray-700 dark:text-gray-300">{{ $skill }}</span>
                            <span class="text-[10px] font-black italic text-gray-400">{{ $count }} Candidates</span>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-900 shadow-inner h-2.5 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-full rounded-full transition-all duration-1000" style="width: {{ round(($count/$maxSkill)*100) }}%"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-400 text-center py-20 font-bold uppercase tracking-widest text-xs">Scanning Market...</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-200 dark:border-gray-700/50 p-8 rounded-3xl shadow-xl">
                <h3 class="font-black text-xl text-gray-900 dark:text-white uppercase tracking-tight mb-8">Capability Gaps (Missing Skills)</h3>
                @php $maxMissing = max(array_values($topMissingSkills) ?: [1]); @endphp
                <div class="space-y-4">
                    @forelse($topMissingSkills as $skill => $count)
                    <div class="group">
                        <div class="flex items-center justify-between mb-1.5 px-1">
                            <span class="text-xs font-black uppercase text-gray-700 dark:text-gray-300">{{ $skill }}</span>
                            <span class="text-[10px] font-black italic text-gray-400">{{ $count }} Mentions</span>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-900 shadow-inner h-2.5 rounded-full overflow-hidden">
                            <div class="bg-red-500 h-full rounded-full transition-all duration-1000" style="width: {{ round(($count/$maxMissing)*100) }}%"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-400 text-center py-20 font-bold uppercase tracking-widest text-xs">Calibration Perfect</p>
                    @endforelse
                </div>
            </div>
        @endif

        @if($activeTab === 'time')
            <div class="md:col-span-2 bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-200 dark:border-gray-700/50 p-8 rounded-3xl shadow-xl">
                <h3 class="font-black text-xl text-gray-900 dark:text-white uppercase tracking-tight mb-10">Departmental Velocity (Days to Hire)</h3>
                <div class="h-96">
                    <canvas id="timeToHireChart"></canvas>
                </div>
            </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
const analyticsData = {
    applicationsOverTime: @json($applicationsOverTime),
    applicationsByJob:    @json($applicationsByJob),
    funnelData:           @json($funnelData),
    scoreDistribution:    @json($scoreDistribution),
    topSkills:            @json($topSkills),
    topMissingSkills:     @json($topMissingSkills),
    recommendationData:   @json($recommendationData),
    timeToHireData:       @json($timeToHireData),
};

let charts = {};

function destroyChart(id) {
    if (charts[id]) {
        charts[id].destroy();
        delete charts[id];
    }
}

function analyticsCharts() {
    const isDark = document.documentElement.classList.contains('dark');
    const labelColor = isDark ? 'rgba(255, 255, 255, 0.4)' : 'rgba(0, 0, 0, 0.4)';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';

    return {
        initCharts() { this.$nextTick(() => this.renderAll()); },
        refreshCharts() {
            this.$nextTick(() => {
                Object.keys(charts).forEach(id => destroyChart(id));
                this.renderAll();
            });
        },
        renderAll() {
            this.renderApplicationsChart();
            this.renderJobsChart();
            this.renderRecommendationChart();
            this.renderFunnelDoughnut();
            this.renderScoreChart();
            this.renderTimeToHireChart();
        },

        renderApplicationsChart() {
            const el = document.getElementById('applicationsChart');
            if (!el) return;
            destroyChart('applicationsChart');
            charts['applicationsChart'] = new Chart(el, {
                type: 'line',
                data: {
                    labels: analyticsData.applicationsOverTime.labels,
                    datasets: [{
                        label: 'Hires',
                        data:  analyticsData.applicationsOverTime.data,
                        borderColor: '#3b82f6',
                        borderWidth: 4,
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#3b82f6',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { cornerRadius: 12, padding: 12 } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: labelColor, font: { weight: 'bold', size: 10 } } },
                        x: { grid: { display: false }, ticks: { color: labelColor, font: { weight: 'bold', size: 10 } } }
                    }
                }
            });
        },

        renderJobsChart() {
            const el = document.getElementById('jobsChart');
            if (!el) return;
            destroyChart('jobsChart');
            charts['jobsChart'] = new Chart(el, {
                type: 'bar',
                data: {
                    labels: analyticsData.applicationsByJob.map(j => j.title),
                    datasets: [{
                        data:  analyticsData.applicationsByJob.map(j => j.count),
                        backgroundColor: '#6366f1',
                        borderRadius: 12,
                        barThickness: 24,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: labelColor } },
                        y: { grid: { display: false }, ticks: { color: labelColor, font: { weight: 'black', size: 9 } } }
                    }
                }
            });
        },

        renderRecommendationChart() {
            const el = document.getElementById('recommendationChart');
            if (!el) return;
            destroyChart('recommendationChart');
            const rec = analyticsData.recommendationData;
            charts['recommendationChart'] = new Chart(el, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(rec).map(k => k.toUpperCase()),
                    datasets: [{
                        data: Object.values(rec),
                        backgroundColor: ['#10b981','#3b82f6','#f59e0b','#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, font: { weight: 'black', size: 10 } } } }
                }
            });
        },

        renderFunnelDoughnut() {
            const el = document.getElementById('funnelDoughnut');
            if (!el) return;
            destroyChart('funnelDoughnut');
            const funnel = analyticsData.funnelData;
            charts['funnelDoughnut'] = new Chart(el, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(funnel).map(k => k.toUpperCase()),
                    datasets: [{
                        data: Object.values(funnel),
                        backgroundColor: ['#3b82f6','#6366f1','#8b5cf6','#f59e0b','#f97316','#10b981'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '80%',
                    plugins: { legend: { position: 'right', labels: { usePointStyle: true, font: { weight: 'black', size: 9 } } } }
                }
            });
        },

        renderScoreChart() {
            const el = document.getElementById('scoreChart');
            if (!el) return;
            destroyChart('scoreChart');
            const scores = analyticsData.scoreDistribution;
            charts['scoreChart'] = new Chart(el, {
                type: 'bar',
                data: {
                    labels: Object.keys(scores),
                    datasets: [{
                        data:  Object.values(scores),
                        backgroundColor: ['#10b981','#22c55e','#84cc16','#eab308','#f97316','#ef4444'],
                        borderRadius: 30,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: labelColor } },
                        x: { grid: { display: false }, ticks: { color: labelColor, font: { weight: 'black' } } }
                    }
                }
            });
        },

        renderTimeToHireChart() {
            const el = document.getElementById('timeToHireChart');
            if (!el) return;
            destroyChart('timeToHireChart');
            const data = analyticsData.timeToHireData;
            charts['timeToHireChart'] = new Chart(el, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.title),
                    datasets: [{
                        label: 'Days',
                        data:  data.map(d => d.avg_days),
                        backgroundColor: '#3b82f6',
                        borderRadius: 8,
                        barThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: labelColor } },
                        x: { grid: { display: false }, ticks: { color: labelColor, font: { weight: 'black', size: 10 } } }
                    }
                }
            });
        }
    }
}
</script>
<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .select-2xs { font-size: 10px; height: 2rem; min-height: 2rem; }
</style>
@endpush
