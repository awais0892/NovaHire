@extends('layouts.app')

@section('content')
    <div class="grid grid-cols-12 gap-4 md:gap-6">
        <!-- Top Metrics -->
        <div class="col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 md:gap-6">
            <!-- Total Companies -->
            <div class="app-card app-card-body">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/10 mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <h4 class="mb-1 text-title-sm font-bold text-gray-800 dark:text-white/90">
                            {{ $metrics['total_companies'] ?? 0 }}
                        </h4>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Companies</span>
                    </div>
                </div>
            </div>

            <!-- Active Subscriptions -->
            <div class="app-card app-card-body">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-50 text-green-500 dark:bg-green-500/10 mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <h4 class="mb-1 text-title-sm font-bold text-gray-800 dark:text-white/90">
                            {{ $metrics['active_subscriptions'] ?? 0 }}
                        </h4>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Active Subscriptions</span>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="app-card app-card-body">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-500 dark:bg-blue-500/10 mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <h4 class="mb-1 text-title-sm font-bold text-gray-800 dark:text-white/90">
                            {{ $metrics['total_users'] ?? 0 }}
                        </h4>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Users</span>
                    </div>
                </div>
            </div>

            <!-- AI Credits Used -->
            <div class="app-card app-card-body">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-50 text-purple-500 dark:bg-purple-500/10 mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <h4 class="mb-1 text-title-sm font-bold text-gray-800 dark:text-white/90">
                            {{ $metrics['ai_tokens_used'] ?? '0' }}
                        </h4>
                        <span class="text-sm text-gray-500 dark:text-gray-400">AI Tokens Used (M)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-span-12 xl:col-span-8 space-y-6">
            <!-- Company Growth Chart -->
            <div class="app-card app-card-body">
                <h3 class="font-semibold text-gray-800 dark:text-white/90 mb-4">Company Registrations (Last 30 Days)</h3>
                <div class="h-64">
                    <canvas id="companyGrowthChart"></canvas>
                </div>
            </div>

            <!-- Recent Registrations Table -->
            <div class="app-card">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-800 dark:text-white/90 text-lg">Recent Company Registrations</h3>
                    <a href="/admin/companies" class="text-sm text-brand-500 hover:text-brand-600">View All</a>
                </div>
                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <th class="pb-3 text-sm font-medium text-gray-500">Company</th>
                                    <th class="pb-3 text-sm font-medium text-gray-500">Users</th>
                                    <th class="pb-3 text-sm font-medium text-gray-500">Status</th>
                                    <th class="pb-3 text-sm font-medium text-gray-500">Joined</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                @forelse($recent_companies ?? [] as $company)
                                    <tr
                                        class="border-b border-gray-50 dark:border-gray-800/50 last:border-0 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                        <td class="py-3">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="h-8 w-8 rounded bg-brand-100 text-brand-600 flex items-center justify-center font-bold dark:bg-brand-900/30 dark:text-brand-400">
                                                    {{ substr($company->name ?? 'C', 0, 1) }}
                                                </div>
                                                <p class="font-medium text-gray-800 dark:text-white/90">
                                                    {{ $company->name ?? 'Unknown' }}</p>
                                            </div>
                                        </td>
                                        <td class="py-3 text-gray-700 dark:text-gray-300">{{ $company->users_count ?? 0 }}</td>
                                        <td class="py-3">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $company->status === 'active' ? 'green' : 'gray' }}-100 text-{{ $company->status === 'active' ? 'green' : 'gray' }}-800">
                                                {{ ucfirst($company->status) }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-gray-500 text-xs">{{ $company->created_at->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-gray-500 italic">No companies registered
                                            yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-span-12 xl:col-span-4 space-y-6">
            <!-- System Status -->
            <div class="app-card app-card-body">
                <h3 class="font-semibold text-gray-800 dark:text-white/90 mb-4">System Health</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span class="text-gray-600 dark:text-gray-400">Database</span>
                        </div>
                        <span class="text-green-500 font-medium">Synced</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">AI Engine</span>
                        </div>
                        <span class="text-green-500 font-medium">Operational</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Storage S3</span>
                        </div>
                        <span class="text-green-500 font-medium">94% Safe</span>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('account.settings') }}"
                        class="block w-full text-center text-sm font-medium text-brand-600 hover:text-brand-500">Configure
                        System</a>
                </div>
            </div>

            <!-- Quick Stats Card -->
            <div class="app-card glow-strong border-brand-500/40 bg-brand-600 p-6 text-white shadow-xl shadow-brand-500/20" data-glow-strength="1.1" data-glow-proximity="132">
                <h3 class="text-lg font-semibold mb-2">Enterprise Plan</h3>
                <p class="text-brand-100 text-sm mb-4">You have 4 new pending company requests waiting for approval.</p>
                <a href="/admin/companies"
                    class="inline-flex items-center gap-2 text-sm font-bold bg-white text-brand-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition">
                    Review Now
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3">
                        </path>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', async function () {
                const chartEl = document.getElementById('companyGrowthChart');
                if (!chartEl) return;

                const ChartCtor = typeof window.ensureChartJs === 'function'
                    ? await window.ensureChartJs()
                    : window.Chart;
                if (typeof ChartCtor !== 'function') return;

                new ChartCtor(chartEl, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($growth_labels) !!},
                        datasets: [{
                            label: 'New Companies',
                            data: {!! json_encode($growth_data) !!},
                            backgroundColor: '#4f46e5',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            });
        </script>
    @endpush
@endsection
