@extends('layouts.app')

@section('content')
    <div class="grid grid-cols-12 gap-4 md:gap-6">
        <!-- Top Metrics -->
        <div class="col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 md:gap-6">
            <!-- Open Reqs -->
            <div class="app-card app-card-body">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/10 mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <h4 class="mb-1 text-title-sm font-bold text-gray-800 dark:text-white/90">
                            {{ $metrics['open_reqs'] ?? 0 }}
                        </h4>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Your Open Requisitions</span>
                    </div>
                </div>
            </div>

            <!-- Candidates to Review -->
            <div class="app-card app-card-body">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-500 dark:bg-orange-500/10 mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                        </path>
                    </svg>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <h4 class="mb-1 text-title-sm font-bold text-gray-800 dark:text-white/90">
                            {{ $metrics['candidates_to_review'] ?? 0 }}
                        </h4>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Awaiting Your Review</span>
                    </div>
                </div>
            </div>

            <!-- Upcoming Interviews -->
            <div class="app-card app-card-body">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-500 dark:bg-blue-500/10 mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <h4 class="mb-1 text-title-sm font-bold text-gray-800 dark:text-white/90">
                            {{ $metrics['upcoming_interviews'] ?? 0 }}
                        </h4>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Upcoming Interviews</span>
                    </div>
                </div>
            </div>

            <!-- Recent Hires -->
            <div class="app-card app-card-body">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-50 text-green-500 dark:bg-green-500/10 mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                        </path>
                    </svg>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <h4 class="mb-1 text-title-sm font-bold text-gray-800 dark:text-white/90">
                            {{ $metrics['recent_hires'] ?? 0 }}
                        </h4>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Hires this Quarter</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-span-12 xl:col-span-8 space-y-6">
            <!-- Shortlisted Candidates -->
            <div class="app-card">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-800 dark:text-white/90 text-lg">Shortlisted by AI & HR</h3>
                    <a href="{{ route('manager.shortlisted') }}" class="text-sm text-brand-500 hover:text-brand-600">View All</a>
                </div>
                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <th class="pb-3 text-sm font-medium text-gray-500">Candidate</th>
                                    <th class="pb-3 text-sm font-medium text-gray-500">Role</th>
                                    <th class="pb-3 text-sm font-medium text-gray-500">Match Score</th>
                                    <th class="pb-3 text-sm font-medium text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                @forelse($shortlisted_candidates ?? [] as $app)
                                    <tr
                                        class="border-b border-gray-50 dark:border-gray-800/50 last:border-0 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                        <td class="py-3">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="h-8 w-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold dark:bg-brand-900/30 dark:text-brand-400">
                                                    {{ substr($app->candidate->name ?? 'C', 0, 1) }}
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-800 dark:text-white/90">
                                                        {{ $app->candidate->name ?? 'Unknown' }}</p>
                                                    <p class="text-[10px] text-gray-500 uppercase tracking-wider">
                                                        {{ $app->status }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 text-gray-700 dark:text-gray-300">
                                            {{ $app->jobListing->title ?? 'Unknown Role' }}
                                        </td>
                                        <td class="py-3">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden max-w-[80px] dark:bg-gray-800">
                                                    <div class="h-full bg-{{ $app->ai_score >= 80 ? 'green' : 'brand' }}-500"
                                                        style="width: {{ $app->ai_score ?? 0 }}%"></div>
                                                </div>
                                                <span class="font-medium font-mono text-xs">{{ $app->ai_score ?? 0 }}%</span>
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <a href="{{ route('manager.shortlisted') }}"
                                                class="text-brand-600 hover:text-brand-500 font-medium">Review</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-gray-500 italic">No candidates awaiting
                                            review.</td>
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
            <!-- Requisition Status -->
            <div class="app-card">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-800 dark:text-white/90">Your Requisitions</h3>
                </div>
                <div class="p-5 space-y-5">
                    @forelse($requisitions ?? [] as $req)
                        <div class="app-subcard glow-subcard p-3" data-glow-card data-glow-proximity="90">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $req->title }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">{{ $req->applications_count }} Apps</span>
                                    <button @click.prevent="$store.clip.copy('{{ route('jobs.show', $req->slug ?? Str::slug($req->title)) }}')"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                            aria-label="Copy job link">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8m-6 4h4M8 8h8m-6-4h4M5 7h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 dark:bg-gray-800">
                                <div class="bg-brand-500 h-1.5 rounded-full"
                                    style="width: {{ min(100, ($req->applications_count / 10) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-sm text-gray-500">No active reqs.</div>
                    @endforelse
                </div>
            </div>

            <!-- Interview Schedule -->
            <div class="app-card app-card-body">
                <h3 class="font-semibold text-gray-800 dark:text-white/90 mb-4">Today's Interviews</h3>
                <div class="space-y-4">
                    @forelse($interviews_today ?? [] as $interview)
                        <div
                            class="app-subcard glow-subcard p-3 border-brand-100 bg-brand-50/50 dark:border-brand-500/20 dark:bg-brand-500/10"
                            data-glow-card
                            data-glow-proximity="90">
                            <h4 class="text-sm font-bold text-gray-800 dark:text-white/90">{{ $interview->candidate->name }}
                            </h4>
                            <p class="text-xs text-brand-600 dark:text-brand-400 font-medium">
                                {{ $interview->jobListing->title }}</p>
                            <div class="mt-3 flex items-center justify-between">
                                <span
                                    class="text-[10px] text-gray-500 uppercase font-bold">{{ $interview->updated_at->format('H:i') }}
                                    Today</span>
                                <a href="#"
                                    class="text-[10px] font-bold bg-white px-2 py-1 rounded border border-brand-200 text-brand-600 hover:bg-brand-50">Join
                                    Hub</a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 text-sm">
                            <svg class="w-10 h-10 mx-auto text-gray-200 mb-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            No interviews today.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
