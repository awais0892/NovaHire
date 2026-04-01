@extends('layouts.app')

@section('content')
<div class="p-6 max-w-[1600px] mx-auto space-y-6">
    <section class="app-card p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-brand-500">Candidate Dashboard</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">Welcome, {{ explode(' ', auth()->user()->name)[0] }}</h1>
                <p class="mt-1 text-sm text-gray-500">Track your applications and discover matching jobs.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('candidate.jobs.index') }}" class="inline-flex h-10 items-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">Browse Jobs</a>
                <a href="{{ route('candidate.profile') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Edit Profile</a>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Applications</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $metrics['applications_submitted'] ?? 0 }}</p>
        </div>
        <div class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Interviews</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $metrics['interviews_scheduled'] ?? 0 }}</p>
        </div>
        <div class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Profile Views</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ $metrics['profile_views'] ?? 0 }}</p>
        </div>
        <div class="app-card app-card-body">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Saved Jobs</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $metrics['saved_jobs'] ?? 0 }}</p>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <section class="app-card xl:col-span-8">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Applications</h2>
                    <p class="text-xs text-gray-500">Latest updates from your hiring pipeline</p>
                </div>
                <a href="{{ route('candidate.applications') }}" class="text-sm font-medium text-brand-500 hover:text-brand-600">View All</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-5 py-4 font-semibold text-gray-500">Role</th>
                            <th class="px-5 py-4 font-semibold text-gray-500">Company</th>
                            <th class="px-5 py-4 font-semibold text-gray-500">Applied</th>
                            <th class="px-5 py-4 font-semibold text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($recent_applications ?? [] as $app)
                            @php
                                $statusClass = match (strtolower((string) ($app->status ?? 'applied'))) {
                                    'hired' => 'bg-emerald-100 text-emerald-700',
                                    'offer' => 'bg-brand-100 text-brand-700',
                                    'interview' => 'bg-amber-100 text-amber-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-5 py-4 font-medium text-gray-900 dark:text-white">{{ $app->jobListing->title ?? 'Unknown role' }}</td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $app->jobListing->company->name ?? 'Company' }}</td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $app->created_at->format('d M Y') }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold uppercase {{ $statusClass }}">{{ $app->status ?? 'applied' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">No applications yet. Start by applying to available roles.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="space-y-6 xl:col-span-4">
            <section class="app-card app-card-body">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Suggested Jobs</h3>
                    <a href="{{ route('candidate.jobs.index') }}" class="text-xs font-medium text-brand-500 hover:text-brand-600">See All</a>
                </div>
                <div class="space-y-3">
                    @forelse($suggested_jobs ?? [] as $job)
                        <a href="{{ route('candidate.jobs.show', $job->slug) }}"
                            class="app-subcard glow-subcard block p-3 transition hover:border-brand-300 hover:bg-gray-50 dark:hover:border-brand-700 dark:hover:bg-white/5"
                            data-glow-card
                            data-glow-proximity="92">
                            <p class="line-clamp-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $job->title }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $job->company->name ?? 'Company' }} - {{ $job->location }}</p>
                            <p class="mt-1 text-xs text-brand-500">{{ $job->salary_range }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">No suggestions available yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="app-card app-card-body">
                @php $strength = 70; @endphp
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Strength</h3>
                <div class="mt-4">
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="text-gray-500">Completion</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $strength }}%</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-white/10">
                        <div class="h-2 rounded-full bg-brand-500" style="width: {{ $strength }}%"></div>
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-500">Improve matching by keeping your profile and CV updated.</p>
                <a href="{{ route('candidate.profile') }}" class="mt-4 inline-flex h-10 w-full items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Update Profile</a>
            </section>
        </div>
    </div>
</div>
@endsection
