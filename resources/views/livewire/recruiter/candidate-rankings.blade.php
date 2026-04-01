<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <h1 class="truncate text-2xl font-bold text-gray-900 dark:text-white md:text-3xl">{{ $job->title }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">AI candidate ranking and shortlist actions</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('recruiter.jobs.kanban', $job->id) }}" class="btn btn-outline btn-sm">Kanban View</a>
            <a href="{{ route('recruiter.jobs.index') }}" class="btn btn-outline btn-sm">All Jobs</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="card p-5"><p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total</p><p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p></div>
        <div class="card p-5"><p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Screened</p><p class="mt-2 text-3xl font-bold text-brand-600">{{ $stats['screened'] }}</p></div>
        <div class="card p-5"><p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Shortlisted</p><p class="mt-2 text-3xl font-bold text-success-600">{{ $stats['shortlisted'] }}</p></div>
        <div class="card p-5"><p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Avg Score</p><p class="mt-2 text-3xl font-bold text-warning-600">{{ number_format($stats['avg_score'] ?? 0, 1) }}</p></div>
        <div class="card p-5"><p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Top Score</p><p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ (int)($stats['top_score'] ?? 0) }}%</p></div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search candidate name/email" class="input" />
            <select wire:model.live="statusFilter" class="select">
                <option value="">All statuses</option>
                @foreach(['applied','screening','shortlisted','interview','offer','hired','rejected'] as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select wire:model.live="scoreFilter" class="select">
                <option value="">All scores</option>
                <option value="high">High (80+)</option>
                <option value="medium">Medium (50-79)</option>
                <option value="low">Low (&lt; 50)</option>
            </select>
            <select wire:model.live="sortBy" class="select">
                <option value="ai_score">Sort: AI score</option>
                <option value="created_at">Sort: Latest</option>
                <option value="status">Sort: Status</option>
            </select>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($applications as $application)
            @php
                $score = $application->ai_score;
                $statusClass = match ($application->status) {
                    'hired' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
                    'offer' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300',
                    'interview' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
                    'rejected' => 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300',
                    default => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
                };
                $hasAnalysis = $application->aiAnalysis && (
                    filled(trim((string) ($application->aiAnalysis->reasoning ?? '')))
                    || ($application->aiAnalysis->match_score ?? 0) > 0
                );
            @endphp
            <article class="card p-5 md:p-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                                {{ strtoupper(substr($application->candidate->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate text-lg font-semibold text-gray-900 dark:text-white">{{ $application->candidate->name }}</h3>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $application->candidate->email }}</p>
                            </div>
                        </div>
                        @if($hasAnalysis && !empty($application->aiAnalysis->matched_skills ?? []))
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach(collect($application->aiAnalysis->matched_skills)->take(5) as $skill)
                                    <span class="badge badge-primary">{{ $skill }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase {{ $statusClass }}">{{ $application->status }}</span>
                        @if(!is_null($score))
                            <span class="badge badge-primary">AI {{ $score }}%</span>
                        @else
                            <span class="badge badge-outline">AI Pending</span>
                        @endif

                        <button wire:click="runAnalysis({{ $application->id }})" class="btn btn-outline btn-sm">Run AI</button>
                        <a href="{{ route('recruiter.analysis', $application->id) }}" class="btn btn-primary btn-sm">Open Analysis</a>

                        @if($application->status !== 'shortlisted')
                            <button wire:click="shortlist({{ $application->id }})" class="btn btn-outline btn-sm">Shortlist</button>
                        @endif

                        <button wire:click="confirmReject({{ $application->id }})" class="btn btn-error btn-sm">Reject</button>
                    </div>
                </div>
            </article>
        @empty
            <div class="card p-10 text-center">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">No candidates found</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Applications for this job will appear here once submitted.</p>
            </div>
        @endforelse
    </div>

    @if($applications->hasPages())
        <div>{{ $applications->links() }}</div>
    @endif

    @if($showRejectModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Reject candidate</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional internal reason for rejection.</p>
                <textarea wire:model="rejectReason" rows="4" class="textarea textarea-bordered mt-4" placeholder="Reason..."></textarea>
                <div class="modal-action">
                    <button wire:click="$set('showRejectModal', false)" class="btn btn-outline">Cancel</button>
                    <button wire:click="reject" class="btn btn-error">Confirm Reject</button>
                </div>
            </div>
        </div>
    @endif
</div>
