<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Job Listings</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage all open roles and monitor applications.</p>
        </div>
        @can('create', \App\Models\JobListing::class)
            <a href="{{ route('recruiter.jobs.create') }}"
                class="inline-flex h-10 items-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                Post New Job
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search title, location, department"
                class="h-11 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">

            <select wire:model.live="statusFilter"
                class="h-11 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="paused">Paused</option>
                <option value="closed">Closed</option>
            </select>

            <select wire:model.live="typeFilter"
                class="h-11 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">All types</option>
                <option value="full_time">Full Time</option>
                <option value="part_time">Part Time</option>
                <option value="contract">Contract</option>
                <option value="internship">Internship</option>
            </select>

            <select wire:model.live="locationFilter"
                class="h-11 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">All locations</option>
                <option value="onsite">On-site</option>
                <option value="remote">Remote</option>
                <option value="hybrid">Hybrid</option>
            </select>

            <button wire:click="$set('search', ''); $set('statusFilter', ''); $set('typeFilter', ''); $set('locationFilter', '')"
                class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                Reset Filters
            </button>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th wire:click="sort('title')" class="cursor-pointer px-5 py-4 font-semibold text-gray-500">
                            Job
                            @if($sortBy === 'title')
                                <span class="ml-1 text-xs text-brand-500">{{ $sortDir === 'asc' ? 'ASC' : 'DESC' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Type / Location</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Salary</th>
                        <th wire:click="sort('applications_count')" class="cursor-pointer px-5 py-4 font-semibold text-gray-500">
                            Applications
                            @if($sortBy === 'applications_count')
                                <span class="ml-1 text-xs text-brand-500">{{ $sortDir === 'asc' ? 'ASC' : 'DESC' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Status</th>
                        <th wire:click="sort('created_at')" class="cursor-pointer px-5 py-4 font-semibold text-gray-500">
                            Posted
                            @if($sortBy === 'created_at')
                                <span class="ml-1 text-xs text-brand-500">{{ $sortDir === 'asc' ? 'ASC' : 'DESC' }}</span>
                            @endif
                        </th>
                        <th class="px-5 py-4 text-right font-semibold text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($jobs as $job)
                        @php
                            $statusTone = match($job->status) {
                                'active' => 'bg-emerald-100 text-emerald-700',
                                'paused' => 'bg-amber-100 text-amber-700',
                                'closed' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $job->title }}</div>
                                <div class="text-xs text-gray-500">{{ $job->department ?: 'General' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold uppercase text-gray-700">
                                        {{ str_replace('_', ' ', $job->job_type) }}
                                    </span>
                                    <span class="inline-flex rounded-md bg-blue-100 px-2.5 py-1 text-xs font-semibold uppercase text-blue-700">
                                        {{ $job->location_type }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500">{{ $job->location }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                {{ $job->salary_range ?: 'Not specified' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-semibold text-brand-600">{{ $job->applications_count }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold uppercase {{ $statusTone }}">
                                        {{ $job->status }}
                                    </span>
                                    <select wire:change="updateStatus({{ $job->id }}, $event.target.value)"
                                        class="h-8 rounded-lg border border-gray-300 px-2 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <option value="draft" @selected($job->status === 'draft')>Draft</option>
                                        <option value="active" @selected($job->status === 'active')>Active</option>
                                        <option value="paused" @selected($job->status === 'paused')>Paused</option>
                                        <option value="closed" @selected($job->status === 'closed')>Closed</option>
                                    </select>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ $job->created_at?->format('d M Y') }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('recruiter.jobs.show', $job) }}"
                                        class="inline-flex h-9 items-center rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                                        View
                                    </a>
                                    <a href="{{ route('recruiter.jobs.edit', $job) }}"
                                        class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white hover:bg-indigo-700">
                                        Edit
                                    </a>
                                    <button type="button"
                                        @click="$store.clip.copy('{{ route('jobs.show', $job->slug) }}', 'Public link copied')"
                                        class="inline-flex h-9 items-center rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                                        Copy Link
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $job->id }})"
                                        class="inline-flex h-9 items-center rounded-lg border border-red-200 px-3 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-900/60 dark:text-red-400 dark:hover:bg-red-900/20">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">
                                No job listings found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 p-4 dark:border-gray-800">
            {{ $jobs->links() }}
        </div>
    </div>

    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete this job listing?</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    This action cannot be undone. Related applications and analysis records may be affected.
                </p>
                <div class="mt-6 flex items-center justify-end gap-2">
                    <button type="button" wire:click="$set('showDeleteModal', false)"
                        class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Cancel
                    </button>
                    <button type="button" wire:click="delete"
                        class="inline-flex h-10 items-center rounded-lg bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
