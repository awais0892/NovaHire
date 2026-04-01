@extends('layouts.app')

@section('content')
<div class="p-6 max-w-[1600px] mx-auto space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Applications</h1>
            <p class="mt-1 text-sm text-gray-500">Track candidate progression and move candidates through stages.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($canExportApplications ?? false)
                <a href="{{ route('recruiter.applications.export.csv', request()->query()) }}"
                    class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    Export CSV
                </a>
                <a href="{{ route('recruiter.applications.export.pdf', request()->query()) }}"
                    class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    Export PDF
                </a>
            @endif
            <a href="{{ route('recruiter.jobs.index') }}" class="inline-flex h-10 items-center rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-black dark:bg-white/10 dark:hover:bg-white/20">
                View Jobs
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Total Applications</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $metrics['total'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">In Screening</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ $metrics['screening'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Interview Stage</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $metrics['interview'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Hired</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $metrics['hired'] }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('recruiter.applications') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search candidate or role"
                class="h-11 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">

            <select name="status"
                class="h-11 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">All statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>

            <select name="job_id"
                class="h-11 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">All jobs</option>
                @foreach($jobs as $job)
                    <option value="{{ $job->id }}" @selected(((int) ($filters['job_id'] ?? 0)) === $job->id)>{{ $job->title }}</option>
                @endforeach
            </select>

            <input type="number" min="0" max="100" name="min_score" value="{{ $filters['min_score'] ?? '' }}" placeholder="Min AI score"
                class="h-11 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex h-11 items-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                    Filter
                </button>
                <a href="{{ route('recruiter.applications') }}" class="inline-flex h-11 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Reset
                </a>
            </div>
        </form>

        <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 dark:border-gray-800 md:flex-row md:items-center md:justify-between">
            <form method="POST" action="{{ route('recruiter.applications.filters.store') }}" class="flex w-full flex-col gap-2 sm:flex-row md:max-w-xl">
                @csrf
                <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                <input type="hidden" name="job_id" value="{{ $filters['job_id'] ?? '' }}">
                <input type="hidden" name="min_score" value="{{ $filters['min_score'] ?? '' }}">
                <input type="text" name="name" required placeholder="Save current filters as..."
                    class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Save Filter
                </button>
            </form>

            @if(($savedFilters ?? collect())->count())
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($savedFilters as $savedFilter)
                        @php $payload = (array) ($savedFilter->filters ?? []); @endphp
                        <a href="{{ route('recruiter.applications', array_filter($payload, fn($v) => $v !== null && $v !== '')) }}"
                            class="inline-flex h-8 items-center rounded-full bg-gray-100 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-200 dark:hover:bg-white/20">
                            {{ $savedFilter->name }}
                        </a>
                        <form method="POST" action="{{ route('recruiter.applications.filters.delete', $savedFilter) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex h-8 items-center rounded-full border border-red-200 px-2 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-900/60 dark:text-red-400 dark:hover:bg-red-900/20">
                                x
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-100 p-4 dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                @if($canManageApplications ?? false)
                    <form id="bulk-application-form" method="POST" action="{{ route('recruiter.applications.bulk-status') }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="h-10 rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            @foreach($statuses as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <button id="bulk-application-submit" type="submit" class="inline-flex h-10 items-center rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white/10 dark:hover:bg-white/20" disabled>
                            Update Selected
                        </button>
                    </form>
                    <p class="text-xs text-gray-500">Select rows and update status in one action.</p>
                @else
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-900/40 dark:bg-amber-500/10 dark:text-amber-300">
                        Read-only mode: status updates are available to HR admins.
                    </div>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3 dark:border-gray-800 dark:bg-white/[0.02] sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <input id="applications-table-search" type="text" placeholder="Quick search on this page..."
                    class="h-9 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 sm:w-72 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <button id="applications-table-clear" type="button"
                    class="inline-flex h-9 items-center rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Clear
                </button>
            </div>
            <p id="applications-table-count" class="text-xs text-gray-500">Showing {{ $applications->count() }} rows on this page.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-5 py-4 font-semibold text-gray-500">
                            <input id="check-all-applications" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500" @disabled(!($canManageApplications ?? false))>
                        </th>
                        <th class="px-5 py-4 font-semibold text-gray-500">
                            <button type="button" class="application-sort-btn inline-flex items-center gap-1" data-sort-key="candidate">Candidate</button>
                        </th>
                        <th class="px-5 py-4 font-semibold text-gray-500">
                            <button type="button" class="application-sort-btn inline-flex items-center gap-1" data-sort-key="job">Job</button>
                        </th>
                        <th class="px-5 py-4 font-semibold text-gray-500">
                            <button type="button" class="application-sort-btn inline-flex items-center gap-1" data-sort-key="score">AI Score</button>
                        </th>
                        <th class="px-5 py-4 font-semibold text-gray-500">
                            <button type="button" class="application-sort-btn inline-flex items-center gap-1" data-sort-key="status">Status</button>
                        </th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Interview</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">
                            <button type="button" class="application-sort-btn inline-flex items-center gap-1" data-sort-key="applied">Applied</button>
                        </th>
                        <th class="px-5 py-4 text-right font-semibold text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($applications as $application)
                        @php
                            $statusTone = match($application->status) {
                                'hired' => 'bg-emerald-100 text-emerald-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                'interview' => 'bg-amber-100 text-amber-700',
                                'shortlisted' => 'bg-blue-100 text-blue-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <tr
                            class="application-table-row"
                            data-candidate="{{ \Illuminate\Support\Str::lower((string) ($application->candidate->name ?? '')) }}"
                            data-job="{{ \Illuminate\Support\Str::lower((string) ($application->jobListing->title ?? '')) }}"
                            data-score="{{ (int) ($application->ai_score ?? -1) }}"
                            data-status="{{ \Illuminate\Support\Str::lower((string) ($application->status ?? '')) }}"
                            data-applied="{{ (int) ($application->created_at?->timestamp ?? 0) }}"
                            data-search="{{ \Illuminate\Support\Str::lower(trim(($application->candidate->name ?? '') . ' ' . ($application->candidate->email ?? '') . ' ' . ($application->jobListing->title ?? '') . ' ' . ($application->status ?? ''))) }}"
                        >
                            <td class="px-5 py-4">
                                <input
                                    form="bulk-application-form"
                                    type="checkbox"
                                    name="application_ids[]"
                                    value="{{ $application->id }}"
                                    class="bulk-application-checkbox h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                    @disabled(!($canManageApplications ?? false))
                                >
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $application->candidate->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500">{{ $application->candidate->email ?? 'No email' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $application->jobListing->title ?? 'Untitled role' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @if(!is_null($application->ai_score))
                                    <span class="font-bold text-brand-600">{{ $application->ai_score }}%</span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold uppercase {{ $statusTone }}">
                                    {{ $application->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                @if($interviewsEnabled ?? false)
                                    @if($application->upcomingInterview)
                                        <div class="space-y-2 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/30 dark:bg-amber-500/10">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                                Scheduled
                                            </p>
                                            <p class="text-xs text-gray-700 dark:text-gray-200">
                                                {{ $application->upcomingInterview->starts_at?->timezone($application->upcomingInterview->timezone)->format('d M Y H:i') }}
                                                ({{ $application->upcomingInterview->timezone }})
                                            </p>
                                            <p class="text-[11px] text-gray-600 dark:text-gray-300">
                                                Candidate response:
                                                <span class="font-semibold">
                                                    {{ $application->upcomingInterview->candidate_response ? ucfirst($application->upcomingInterview->candidate_response) : 'Pending' }}
                                                </span>
                                            </p>
                                            @if($canManageApplications ?? false)
                                                <div class="flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        class="open-interview-modal inline-flex h-8 items-center rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 hover:bg-white dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                                        data-application-id="{{ $application->id }}"
                                                        data-candidate-name="{{ $application->candidate->name ?? 'Candidate' }}"
                                                        data-job-title="{{ $application->jobListing->title ?? 'Role' }}"
                                                        data-starts-at="{{ optional($application->upcomingInterview->starts_at?->timezone($application->upcomingInterview->timezone))->format('Y-m-d\TH:i') }}"
                                                        data-ends-at="{{ optional($application->upcomingInterview->ends_at?->timezone($application->upcomingInterview->timezone))->format('Y-m-d\TH:i') }}"
                                                        data-timezone="{{ $application->upcomingInterview->timezone }}"
                                                        data-mode="{{ $application->upcomingInterview->mode }}"
                                                        data-slot-id="{{ $application->upcomingInterview->interview_slot_id }}"
                                                        data-meeting-link="{{ $application->upcomingInterview->meeting_link }}"
                                                        data-location="{{ $application->upcomingInterview->location }}"
                                                        data-notes="{{ $application->upcomingInterview->notes }}"
                                                    >
                                                        Reschedule
                                                    </button>
                                                    <form method="POST" action="{{ route('recruiter.applications.interviews.cancel', [$application, $application->upcomingInterview]) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="reason" value="Cancelled by recruiter from pipeline">
                                                        <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-300 px-3 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-900/50 dark:text-red-300 dark:hover:bg-red-900/20">
                                                            Cancel
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">No interview scheduled</p>
                                            @if($canManageApplications ?? false)
                                                <button
                                                    type="button"
                                                    class="open-interview-modal mt-2 inline-flex h-8 items-center rounded-lg bg-brand-600 px-3 text-xs font-semibold text-white hover:bg-brand-700"
                                                    data-application-id="{{ $application->id }}"
                                                    data-candidate-name="{{ $application->candidate->name ?? 'Candidate' }}"
                                                    data-job-title="{{ $application->jobListing->title ?? 'Role' }}"
                                                    data-starts-at=""
                                                    data-ends-at=""
                                                    data-timezone="{{ config('app.timezone') }}"
                                                    data-mode="video"
                                                    data-slot-id=""
                                                    data-meeting-link=""
                                                    data-location=""
                                                    data-notes=""
                                                >
                                                    Schedule Interview
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-900/30 dark:bg-amber-500/10 dark:text-amber-300">
                                        Pending migration
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-gray-600 dark:text-gray-300">
                                {{ $application->created_at?->format('d M Y') }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col items-end gap-2">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            class="open-application-detail inline-flex h-8 items-center rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                                            data-application-id="{{ $application->id }}"
                                        >
                                            Details
                                        </button>
                                        <a href="{{ route('recruiter.candidates.show', $application->candidate_id) }}" class="inline-flex h-8 items-center rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                                            Candidate
                                        </a>
                                        @if($canManageApplications ?? false)
                                            <a href="{{ route('recruiter.ai.screen', $application->id) }}" class="inline-flex h-8 items-center rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white hover:bg-indigo-700">
                                                AI Report
                                            </a>
                                        @endif
                                    </div>
                                    @if($canManageApplications ?? false)
                                        <form method="POST" action="{{ route('recruiter.applications.status', $application->id) }}" class="application-status-form flex items-center gap-2" data-current-status="{{ $application->status }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="application-status-select h-8 rounded-lg border border-gray-300 px-2 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                                @foreach($statuses as $status)
                                                    <option value="{{ $status }}" @selected($status === $application->status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="application-status-submit inline-flex h-8 items-center rounded-lg bg-gray-900 px-3 text-xs font-semibold text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white/10 dark:hover:bg-white/20" disabled>
                                                Save
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-500">
                                No applications found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($applications->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-800">
                {{ $applications->links() }}
            </div>
        @endif
    </div>

    <section class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Recent Manual Changes</h2>
            <span class="text-xs text-gray-500">{{ ($auditEvents ?? collect())->count() }} entries</span>
        </div>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-3 py-2 font-semibold text-gray-500">When</th>
                        <th class="px-3 py-2 font-semibold text-gray-500">User</th>
                        <th class="px-3 py-2 font-semibold text-gray-500">Action</th>
                        <th class="px-3 py-2 font-semibold text-gray-500">From</th>
                        <th class="px-3 py-2 font-semibold text-gray-500">To</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse(($auditEvents ?? collect()) as $event)
                        @php
                            $metadata = is_array($event->metadata) ? $event->metadata : [];
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $event->created_at?->format('d M H:i') }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $event->user?->name ?? 'System' }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ str_replace('_', ' ', $event->action) }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $metadata['from'] ?? ($metadata['from_note_excerpt'] ?? '-') }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-100">{{ $metadata['to'] ?? ($metadata['to_note_excerpt'] ?? '-') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-gray-500">No manual updates logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div id="application-detail-drawer" class="fixed inset-0 z-[999] hidden bg-gray-900/40">
        <div class="absolute right-0 top-0 h-full w-full max-w-2xl overflow-y-auto border-l border-gray-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-950">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white/95 px-5 py-4 backdrop-blur dark:border-gray-800 dark:bg-gray-950/95">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Application Detail</p>
                    <h3 id="detail-title" class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Loading...</h3>
                </div>
                <button id="close-application-detail" type="button"
                    class="inline-flex h-9 items-center rounded-lg border border-gray-300 px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Close
                </button>
            </div>

            <div id="detail-loading" class="px-5 py-5 text-sm text-gray-500">Loading application details...</div>
            <div id="detail-error" class="hidden px-5 py-5 text-sm text-red-600"></div>

            <div id="detail-content" class="hidden space-y-6 px-5 py-5">
                <section class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800 md:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Candidate</p>
                        <p id="detail-candidate" class="mt-1 font-semibold text-gray-900 dark:text-white"></p>
                        <p id="detail-candidate-email" class="text-gray-600 dark:text-gray-300"></p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Role</p>
                        <p id="detail-job" class="mt-1 font-semibold text-gray-900 dark:text-white"></p>
                        <p id="detail-job-company" class="text-gray-600 dark:text-gray-300"></p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Status</p>
                        <p id="detail-status" class="mt-1 font-semibold text-gray-900 dark:text-white"></p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">AI Score</p>
                        <p id="detail-score" class="mt-1 font-semibold text-gray-900 dark:text-white"></p>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Application & AI Note</h4>
                    <p id="detail-cover-letter" class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300"></p>
                    <div class="mt-3 rounded-lg bg-gray-50 p-3 dark:bg-white/[0.03]">
                        <p class="text-xs uppercase tracking-wide text-gray-500">AI Summary</p>
                        <p id="detail-ai-note" class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300"></p>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Interview Slot</h4>
                    <p id="detail-interview" class="mt-2 text-sm text-gray-700 dark:text-gray-300">No interview information available.</p>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Email History</h4>
                    <div id="detail-emails" class="mt-2 space-y-2 text-sm text-gray-700 dark:text-gray-300"></div>
                </section>

                <section id="detail-note-section" class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">HR Note Override</h4>
                    <p class="mt-1 text-xs text-gray-500">Update recruiter note and optionally send a fresh decision email.</p>
                    <form id="detail-note-form" class="mt-3 space-y-3">
                        <input type="hidden" id="detail-application-id" name="application_id">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Decision Email Type</label>
                                <select id="detail-decision" name="decision" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    <option value="shortlisted">Shortlisted</option>
                                    <option value="interview">Interview</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input id="detail-send-email" type="checkbox" name="send_email" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-600">
                                    Send email after saving note
                                </label>
                            </div>
                        </div>
                        <input id="detail-note-subject" name="subject" type="text" placeholder="Optional subject"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <textarea id="detail-note-content" name="note_content" rows="5" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            placeholder="Write recruiter note..."></textarea>
                        <div class="flex items-center justify-between gap-2">
                            <p id="detail-note-feedback" class="text-xs text-gray-500"></p>
                            <button id="detail-note-submit" type="submit"
                                class="inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">
                                Save Note
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>

    <div id="interview-modal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-gray-900/50 px-4">
        <div class="w-full max-w-2xl rounded-2xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Interview Scheduler</p>
                    <h3 id="interview-modal-title" class="mt-1 text-lg font-bold text-gray-900 dark:text-white"></h3>
                </div>
                <button type="button" id="close-interview-modal" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Close
                </button>
            </div>

            <form
                id="interview-modal-form"
                method="POST"
                class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2"
                data-base-url="{{ url('/recruiter/applications') }}"
                data-slots-url-template="{{ url('/recruiter/applications/__APP_ID__/interview-slots') }}"
            >
                @csrf
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Preferred Available Slot</label>
                    <div class="mt-1 flex flex-col gap-2 sm:flex-row">
                        <select id="modal-slot-id" name="slot_id" class="h-10 flex-1 rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">Manual scheduling (custom date/time)</option>
                        </select>
                        <button type="button" id="refresh-slot-list" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-xs font-semibold uppercase tracking-wide text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                            Refresh Slots
                        </button>
                    </div>
                    <p id="modal-slot-hint" class="mt-1 text-xs text-gray-500">Choose an available slot or schedule manually.</p>
                </div>

                <input id="modal-starts-at" type="datetime-local" name="starts_at" required class="h-10 rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <input id="modal-ends-at" type="datetime-local" name="ends_at" class="h-10 rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">

                <input id="modal-timezone" type="text" name="timezone" value="{{ config('app.timezone') }}" class="h-10 rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <select id="modal-mode" name="mode" class="h-10 rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="video">Video</option>
                    <option value="onsite">Onsite</option>
                    <option value="phone">Phone</option>
                </select>

                <input id="modal-meeting-link" type="url" name="meeting_link" placeholder="Meeting URL (required for video)" class="h-10 rounded-lg border border-gray-300 px-3 text-sm sm:col-span-2 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <input id="modal-location" type="text" name="location" placeholder="Location (required for onsite)" class="h-10 rounded-lg border border-gray-300 px-3 text-sm sm:col-span-2 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <textarea id="modal-notes" name="notes" rows="3" placeholder="Interview notes" class="rounded-lg border border-gray-300 px-3 py-2 text-sm sm:col-span-2 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>

                <div class="sm:col-span-2 flex justify-end gap-2">
                    <button type="button" id="cancel-interview-modal" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">
                        Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('check-all-applications');
    const checkboxes = Array.from(document.querySelectorAll('.bulk-application-checkbox'));
    const submit = document.getElementById('bulk-application-submit');
    const tableSearchInput = document.getElementById('applications-table-search');
    const tableClearBtn = document.getElementById('applications-table-clear');
    const tableCount = document.getElementById('applications-table-count');
    const tableRows = Array.from(document.querySelectorAll('.application-table-row'));
    const sortButtons = Array.from(document.querySelectorAll('.application-sort-btn'));
    const tableBody = tableRows.length > 0 ? tableRows[0].parentElement : null;
    const sortState = { key: null, dir: 1 };
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const updateState = () => {
        if (!submit) return;
        const hasChecked = checkboxes.some(cb => cb.checked);
        submit.disabled = !hasChecked;
    };

    const updateTableCount = () => {
        if (!tableCount) return;
        const visibleCount = tableRows.filter((row) => row.style.display !== 'none').length;
        tableCount.textContent = `Showing ${visibleCount} rows on this page.`;
    };

    const sortRows = (rows, key, dir) => {
        const numericKeys = new Set(['score', 'applied']);
        return [...rows].sort((a, b) => {
            const av = a.dataset[key] ?? '';
            const bv = b.dataset[key] ?? '';

            if (numericKeys.has(key)) {
                const an = Number(av);
                const bn = Number(bv);
                if (Number.isNaN(an) || Number.isNaN(bn)) {
                    return 0;
                }
                return (an - bn) * dir;
            }

            return av.localeCompare(bv) * dir;
        });
    };

    const refreshTableRows = () => {
        if (!tableBody) return;

        const term = (tableSearchInput?.value || '').trim().toLowerCase();
        let visibleRows = tableRows.filter((row) => {
            const matches = term === '' || (row.dataset.search || '').includes(term);
            row.style.display = matches ? '' : 'none';
            return matches;
        });

        if (sortState.key) {
            visibleRows = sortRows(visibleRows, sortState.key, sortState.dir);
            visibleRows.forEach((row) => tableBody.appendChild(row));
        }

        updateTableCount();
    };

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
            updateState();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (!cb.checked && selectAll) {
                selectAll.checked = false;
            } else if (selectAll && checkboxes.every(x => x.checked)) {
                selectAll.checked = true;
            }
            updateState();
        });
    });

    if (tableSearchInput) {
        tableSearchInput.addEventListener('input', refreshTableRows);
    }

    if (tableClearBtn && tableSearchInput) {
        tableClearBtn.addEventListener('click', () => {
            tableSearchInput.value = '';
            refreshTableRows();
        });
    }

    sortButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.sortKey;
            if (!key) return;

            if (sortState.key === key) {
                sortState.dir = sortState.dir === 1 ? -1 : 1;
            } else {
                sortState.key = key;
                sortState.dir = 1;
            }

            sortButtons.forEach((btn) => btn.classList.remove('text-brand-600'));
            button.classList.add('text-brand-600');
            refreshTableRows();
        });
    });

    updateState();
    refreshTableRows();

    document.querySelectorAll('.application-status-form').forEach((form) => {
        const select = form.querySelector('.application-status-select');
        const saveBtn = form.querySelector('.application-status-submit');
        const current = form.dataset.currentStatus;

        const toggleSaveState = () => {
            saveBtn.disabled = select.value === current;
        };

        if (select && saveBtn) {
            select.addEventListener('change', toggleSaveState);
        }
        toggleSaveState();
    });

    const detailDrawer = document.getElementById('application-detail-drawer');
    const detailCloseBtn = document.getElementById('close-application-detail');
    const detailLoading = document.getElementById('detail-loading');
    const detailError = document.getElementById('detail-error');
    const detailContent = document.getElementById('detail-content');
    const detailTitle = document.getElementById('detail-title');
    const detailCandidate = document.getElementById('detail-candidate');
    const detailCandidateEmail = document.getElementById('detail-candidate-email');
    const detailJob = document.getElementById('detail-job');
    const detailJobCompany = document.getElementById('detail-job-company');
    const detailStatus = document.getElementById('detail-status');
    const detailScore = document.getElementById('detail-score');
    const detailCoverLetter = document.getElementById('detail-cover-letter');
    const detailAiNote = document.getElementById('detail-ai-note');
    const detailInterview = document.getElementById('detail-interview');
    const detailEmails = document.getElementById('detail-emails');
    const detailNoteSection = document.getElementById('detail-note-section');
    const detailNoteForm = document.getElementById('detail-note-form');
    const detailDecision = document.getElementById('detail-decision');
    const detailNoteSubject = document.getElementById('detail-note-subject');
    const detailNoteContent = document.getElementById('detail-note-content');
    const detailSendEmail = document.getElementById('detail-send-email');
    const detailFeedback = document.getElementById('detail-note-feedback');
    const detailSubmit = document.getElementById('detail-note-submit');
    const detailApplicationId = document.getElementById('detail-application-id');
    const detailUrlTemplate = @json(url('/recruiter/applications/__APP_ID__/details'));
    const overrideUrlTemplate = @json(url('/recruiter/applications/__APP_ID__/notes/override'));

    const formatDate = (isoValue) => {
        if (!isoValue) return '-';
        const date = new Date(isoValue);
        if (Number.isNaN(date.getTime())) return '-';
        return date.toLocaleString();
    };

    const showDetailLoadingState = () => {
        if (detailLoading) detailLoading.classList.remove('hidden');
        if (detailError) detailError.classList.add('hidden');
        if (detailContent) detailContent.classList.add('hidden');
    };

    const showDetailErrorState = (message) => {
        if (!detailError) return;
        detailLoading?.classList.add('hidden');
        detailContent?.classList.add('hidden');
        detailError.classList.remove('hidden');
        detailError.textContent = message || 'Unable to load application details.';
    };

    const renderDetailPayload = (payload) => {
        if (!payload) return;

        const application = payload.application || {};
        const candidate = payload.candidate || {};
        const job = payload.job || {};
        const ai = payload.ai || {};
        const emails = Array.isArray(payload.emails) ? payload.emails : [];
        const interview = payload.interview || null;
        const canManage = Boolean(payload.can_manage);

        detailTitle.textContent = `${candidate.name || 'Candidate'} - ${job.title || 'Role'}`;
        detailCandidate.textContent = `${candidate.name || '-'} (${candidate.cv_status || 'pending'})`;
        detailCandidateEmail.textContent = candidate.email || '-';
        detailJob.textContent = `${job.title || '-'} (${job.job_type || '-'})`;
        detailJobCompany.textContent = `${job.company || '-'} - ${job.location || '-'}`;
        detailStatus.textContent = application.status || '-';
        detailScore.textContent = application.ai_score === null || application.ai_score === undefined ? 'N/A' : `${application.ai_score}%`;
        detailCoverLetter.textContent = (application.cover_letter || '').trim() !== '' ? application.cover_letter : 'No cover letter provided.';
        detailAiNote.textContent = (ai.latest_note || ai.reasoning || '').trim() !== '' ? (ai.latest_note || ai.reasoning) : 'No AI note available.';

        if (interview) {
            detailInterview.textContent = `${formatDate(interview.starts_at)} - ${formatDate(interview.ends_at)} | ${interview.mode || '-'} | ${interview.timezone || '-'}`;
        } else {
            detailInterview.textContent = 'No interview information available.';
        }

        if (emails.length === 0) {
            detailEmails.innerHTML = '<p class="text-sm text-gray-500">No email logs found.</p>';
        } else {
            detailEmails.innerHTML = emails.map((email) => {
                const sentLabel = email.sent_at ? formatDate(email.sent_at) : formatDate(email.created_at);
                const error = email.error_message ? `<div class="text-[11px] text-red-600">${email.error_message}</div>` : '';

                return `
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">${email.status || '-'}</p>
                            <p class="text-xs text-gray-500">${sentLabel}</p>
                        </div>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">${email.subject || '(No subject)'}</p>
                        <p class="text-xs text-gray-500">${email.recipient_email || '-'}</p>
                        ${error}
                    </div>
                `;
            }).join('');
        }

        if (detailDecision) {
            const decision = ['rejected', 'shortlisted', 'interview'].includes(application.status) ? application.status : 'shortlisted';
            detailDecision.value = decision;
        }

        if (detailNoteSubject) {
            detailNoteSubject.value = '';
        }

        if (detailNoteContent) {
            detailNoteContent.value = application.recruiter_notes || '';
        }

        if (detailSendEmail) {
            detailSendEmail.checked = false;
        }

        if (detailFeedback) {
            detailFeedback.textContent = '';
        }

        if (detailApplicationId) {
            detailApplicationId.value = String(application.id || '');
        }

        if (detailNoteSection) {
            detailNoteSection.classList.toggle('hidden', !canManage);
        }

        detailLoading?.classList.add('hidden');
        detailError?.classList.add('hidden');
        detailContent?.classList.remove('hidden');
    };

    const openDetailDrawer = async (applicationId) => {
        if (!detailDrawer || !applicationId) return;
        detailDrawer.classList.remove('hidden');
        showDetailLoadingState();

        try {
            const url = detailUrlTemplate.replace('__APP_ID__', String(applicationId));
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to load details.');
            }

            const payload = await response.json();
            renderDetailPayload(payload);
        } catch (error) {
            showDetailErrorState(error.message);
        }
    };

    const closeDetailDrawer = () => {
        if (!detailDrawer) return;
        detailDrawer.classList.add('hidden');
    };

    document.querySelectorAll('.open-application-detail').forEach((btn) => {
        btn.addEventListener('click', () => {
            const applicationId = btn.dataset.applicationId;
            openDetailDrawer(applicationId);
        });
    });

    if (detailCloseBtn) {
        detailCloseBtn.addEventListener('click', closeDetailDrawer);
    }

    if (detailDrawer) {
        detailDrawer.addEventListener('click', (event) => {
            if (event.target === detailDrawer) {
                closeDetailDrawer();
            }
        });
    }

    if (detailNoteForm) {
        detailNoteForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const applicationId = detailApplicationId?.value || '';
            if (!applicationId) return;

            if (detailSubmit) {
                detailSubmit.disabled = true;
                detailSubmit.textContent = 'Saving...';
            }
            if (detailFeedback) {
                detailFeedback.textContent = '';
            }

            const formData = new FormData(detailNoteForm);
            formData.append('_token', csrfToken);

            try {
                const response = await fetch(overrideUrlTemplate.replace('__APP_ID__', String(applicationId)), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(payload.message || 'Unable to save note override.');
                }

                if (detailFeedback) {
                    detailFeedback.textContent = payload.message || 'Saved successfully.';
                    detailFeedback.classList.remove('text-red-600');
                    detailFeedback.classList.add('text-emerald-600');
                }

                await openDetailDrawer(applicationId);
            } catch (error) {
                if (detailFeedback) {
                    detailFeedback.textContent = error.message || 'Unable to save note override.';
                    detailFeedback.classList.remove('text-emerald-600');
                    detailFeedback.classList.add('text-red-600');
                }
            } finally {
                if (detailSubmit) {
                    detailSubmit.disabled = false;
                    detailSubmit.textContent = 'Save Note';
                }
            }
        });
    }

    const modal = document.getElementById('interview-modal');
    const modalTitle = document.getElementById('interview-modal-title');
    const modalForm = document.getElementById('interview-modal-form');
    const closeBtn = document.getElementById('close-interview-modal');
    const cancelBtn = document.getElementById('cancel-interview-modal');
    const slotSelect = document.getElementById('modal-slot-id');
    const refreshSlotsBtn = document.getElementById('refresh-slot-list');
    const slotHint = document.getElementById('modal-slot-hint');
    const startsAt = document.getElementById('modal-starts-at');
    const endsAt = document.getElementById('modal-ends-at');
    const timezone = document.getElementById('modal-timezone');
    const mode = document.getElementById('modal-mode');
    const meetingLink = document.getElementById('modal-meeting-link');
    const locationField = document.getElementById('modal-location');
    const notes = document.getElementById('modal-notes');
    const slotUrlTemplate = modalForm?.dataset.slotsUrlTemplate || '';
    let currentApplicationId = null;

    const schedulingFields = [startsAt, endsAt, timezone];

    const setDateFieldState = (disabled) => {
        schedulingFields.forEach((field) => {
            if (!field) return;
            field.disabled = disabled;
            field.classList.toggle('opacity-60', disabled);
            field.classList.toggle('cursor-not-allowed', disabled);
        });
    };

    const buildSlotsUrl = (applicationId) => {
        return slotUrlTemplate.replace('__APP_ID__', String(applicationId));
    };

    const renderSlots = (slots, preferredSlotId = '') => {
        if (!slotSelect) return;

        slotSelect.innerHTML = '';
        const manualOption = document.createElement('option');
        manualOption.value = '';
        manualOption.textContent = 'Manual scheduling (custom date/time)';
        slotSelect.appendChild(manualOption);

        if (!Array.isArray(slots) || slots.length === 0) {
            slotHint.textContent = 'No available slots in this window. Use manual scheduling or create slots in Interview Slots.';
            slotSelect.value = '';
            setDateFieldState(false);
            return;
        }

        slots.forEach((slot) => {
            const option = document.createElement('option');
            option.value = String(slot.id);
            option.textContent = `${slot.date_label} - ${slot.time_label} (${slot.mode})`;
            option.dataset.localStart = (slot.local_start || '').slice(0, 16);
            option.dataset.localEnd = (slot.local_end || '').slice(0, 16);
            option.dataset.timezone = slot.timezone || '';
            option.dataset.mode = slot.mode || '';
            option.dataset.meetingLink = slot.meeting_link || '';
            option.dataset.location = slot.location || '';
            slotSelect.appendChild(option);
        });

        slotHint.textContent = `${slots.length} available slots loaded.`;

        if (preferredSlotId) {
            slotSelect.value = String(preferredSlotId);
        } else {
            slotSelect.value = '';
        }
    };

    const applySelectedSlot = () => {
        if (!slotSelect) return;
        const selectedOption = slotSelect.selectedOptions[0];
        const hasSlot = Boolean(slotSelect.value);

        if (hasSlot && selectedOption) {
            startsAt.value = selectedOption.dataset.localStart || startsAt.value;
            endsAt.value = selectedOption.dataset.localEnd || endsAt.value;
            timezone.value = selectedOption.dataset.timezone || timezone.value;
            mode.value = selectedOption.dataset.mode || mode.value;
            if (selectedOption.dataset.meetingLink) {
                meetingLink.value = selectedOption.dataset.meetingLink;
            }
            if (selectedOption.dataset.location) {
                locationField.value = selectedOption.dataset.location;
            }
        }

        setDateFieldState(hasSlot);
    };

    const fetchAvailableSlots = async (applicationId, preferredSlotId = '') => {
        if (!applicationId || !slotSelect) return;

        slotHint.textContent = 'Loading available slots...';
        slotSelect.innerHTML = '<option value="">Loading slots...</option>';

        try {
            const response = await fetch(buildSlotsUrl(applicationId), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to load available slots.');
            }

            const payload = await response.json();
            renderSlots(payload.slots || [], preferredSlotId);
            applySelectedSlot();
        } catch (error) {
            slotSelect.innerHTML = '<option value="">Manual scheduling (custom date/time)</option>';
            slotHint.textContent = 'Unable to load slots right now. You can still schedule manually.';
            setDateFieldState(false);
        }
    };

    const closeModal = () => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        currentApplicationId = null;
        if (slotSelect) {
            slotSelect.innerHTML = '<option value="">Manual scheduling (custom date/time)</option>';
        }
        setDateFieldState(false);
    };

    const openModal = (button) => {
        if (!modal || !modalForm) return;
        const appId = button.dataset.applicationId;
        const candidate = button.dataset.candidateName || 'Candidate';
        const role = button.dataset.jobTitle || 'Role';
        const base = modalForm.dataset.baseUrl;
        const slotId = button.dataset.slotId || '';
        currentApplicationId = appId;

        modalTitle.textContent = `${candidate} - ${role}`;
        modalForm.action = `${base}/${appId}/interviews`;

        startsAt.value = button.dataset.startsAt || '';
        endsAt.value = button.dataset.endsAt || '';
        timezone.value = button.dataset.timezone || @json(config('app.timezone'));
        mode.value = button.dataset.mode || 'video';
        meetingLink.value = button.dataset.meetingLink || '';
        locationField.value = button.dataset.location || '';
        notes.value = button.dataset.notes || '';
        slotHint.textContent = 'Choose an available slot or schedule manually.';
        fetchAvailableSlots(appId, slotId);

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    document.querySelectorAll('.open-interview-modal').forEach((btn) => {
        btn.addEventListener('click', function () {
            openModal(this);
        });
    });

    if (slotSelect) {
        slotSelect.addEventListener('change', applySelectedSlot);
    }

    if (refreshSlotsBtn) {
        refreshSlotsBtn.addEventListener('click', () => {
            if (!currentApplicationId) return;
            fetchAvailableSlots(currentApplicationId);
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });
});
</script>
@endpush
