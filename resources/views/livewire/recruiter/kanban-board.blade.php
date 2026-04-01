@php
    $flowStages = [
        ['key' => 'applied', 'label' => 'Applied'],
        ['key' => 'screening', 'label' => 'Screening'],
        ['key' => 'shortlisted', 'label' => 'Shortlisted'],
        ['key' => 'interview', 'label' => 'Interview'],
        ['key' => 'offer', 'label' => 'Offer'],
        ['key' => 'hired', 'label' => 'Hired'],
    ];
@endphp

<div x-data="kanbanBoard()" x-init="initDrag()" data-kanban-board class="space-y-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ATS Pipeline</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $job->title }}</p>
        </div>

        <div class="grid w-full gap-2 sm:grid-cols-2 xl:w-auto xl:grid-cols-[minmax(16rem,20rem)_auto_auto]">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search candidates..."
                class="input h-10 w-full" />
            <a href="{{ route('recruiter.jobs.candidates', $job->id) }}" class="btn btn-outline btn-sm h-10">Ranked View</a>
            <a href="{{ route('recruiter.jobs.index') }}" class="btn btn-outline btn-sm h-10">All Jobs</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <div class="card p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $boardStats['total'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Screening</p>
            <p class="mt-1 text-2xl font-bold text-blue-600">{{ $boardStats['screening'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Interview</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ $boardStats['interview'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Hired</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $boardStats['hired'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Rejected</p>
            <p class="mt-1 text-2xl font-bold text-rose-600">{{ (int) $counts->get('rejected', 0) }}</p>
        </div>
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900/40">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-600 dark:text-gray-300">Pipeline Timeline</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">Live stage counts for this role</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            @foreach($flowStages as $stage)
                @php
                    $count = (int) $counts->get($stage['key'], 0);
                    $isActiveStage = $count > 0;
                @endphp
                <div class="relative rounded-xl border p-3 {{ $isActiveStage ? 'border-brand-200 bg-brand-50/40 dark:border-brand-500/50 dark:bg-brand-500/10' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-white/5' }}">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">{{ $stage['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold {{ $isActiveStage ? 'text-brand-700 dark:text-brand-300' : 'text-gray-700 dark:text-gray-200' }}">{{ $count }}</p>
                    @if(!$loop->last)
                        <span class="pointer-events-none absolute -right-2 top-1/2 hidden h-[2px] w-4 -translate-y-1/2 bg-gray-300 dark:bg-gray-600 xl:block"></span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60">
        @foreach($columns as $status => $config)
            @php $columnApps = $applications->get($status, collect()); @endphp

            <section class="kanban-column overflow-hidden rounded-2xl border border-gray-200 bg-white/95 dark:border-gray-800 dark:bg-gray-900/70">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $config['label'] }}</span>
                        <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">
                            {{ $counts->get($status, 0) }}
                        </span>
                    </div>
                </div>

                <div
                    id="column-{{ $status }}"
                    data-status="{{ $status }}"
                    class="kanban-drop-zone min-h-[220px] space-y-3 p-3"
                    wire:key="column-{{ $status }}">
                    @forelse($columnApps as $application)
                        @php
                            $analysis = $application->aiAnalysis;
                            $score = $application->ai_score;
                            $scoreBadge = is_null($score)
                                ? 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-300'
                                : ($score >= 80
                                    ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300'
                                    : ($score >= 60
                                        ? 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300'
                                        : 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300'));
                        @endphp

                        <article
                            id="card-{{ $application->id }}"
                            data-id="{{ $application->id }}"
                            class="kanban-card rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs transition hover:shadow-theme-sm dark:border-gray-700 dark:bg-gray-900"
                            wire:key="card-{{ $application->id }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $application->candidate->name }}</p>
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $application->candidate->email }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $scoreBadge }}">
                                    {{ is_null($score) ? 'Pending' : $score . '%' }}
                                </span>
                            </div>

                            @if($analysis && !empty($analysis->matched_skills ?? []))
                                <div class="mt-3 flex flex-wrap gap-1">
                                    @foreach(collect($analysis->matched_skills)->take(3) as $skill)
                                        <span class="badge badge-primary badge-sm">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ $application->created_at->diffForHumans() }}</div>

                            <div class="mt-3 grid grid-cols-2 gap-2 border-t border-gray-100 pt-3 dark:border-gray-800">
                                <button wire:click="runAnalysis({{ $application->id }})" class="btn btn-outline btn-sm">Run AI</button>
                                <a href="{{ route('recruiter.analysis', $application->id) }}" class="btn btn-primary btn-sm text-center">Analysis</a>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap gap-1">
                                    @if($status !== 'applied')
                                        <button wire:click="quickMove({{ $application->id }}, 'back')" class="btn btn-ghost btn-sm">Back</button>
                                    @endif
                                    @if($status !== 'hired' && $status !== 'rejected')
                                        <button wire:click="quickMove({{ $application->id }}, 'forward')" class="btn btn-ghost btn-sm">Next</button>
                                    @endif
                                </div>
                                <button wire:click="openCard({{ $application->id }})" class="btn btn-outline btn-sm">Details</button>
                            </div>
                        </article>
                    @empty
                        <div class="empty-placeholder rounded-lg border border-dashed border-gray-300 px-3 py-8 text-center text-xs text-gray-400 dark:border-gray-700">
                            No candidates in this stage
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    @if($showNoteModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="text-lg font-semibold">Recruiter Note</h3>
                <textarea wire:model="noteText" rows="5" class="textarea textarea-bordered mt-4" placeholder="Add your notes..."></textarea>
                <div class="modal-action">
                    <button wire:click="$set('showNoteModal', false)" class="btn btn-outline">Cancel</button>
                    <button wire:click="saveNote" class="btn btn-primary">Save Note</button>
                </div>
            </div>
        </div>
    @endif

    @if($showCardModal && $focusedApp)
        <div class="modal modal-open">
            <div class="modal-box max-w-2xl">
                @php
                    $a = $focusedApp;
                    $ai = $a->aiAnalysis;
                    $currentStageIndex = collect($flowStages)->search(fn(array $stage) => $stage['key'] === $a->status);
                @endphp
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $a->candidate->name }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $a->candidate->email }}</p>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-white/5">
                        <p class="text-xs text-gray-400">Status</p>
                        <p class="mt-1 text-sm font-semibold capitalize text-gray-900 dark:text-white">{{ $a->status }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-white/5">
                        <p class="text-xs text-gray-400">Applied</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $a->created_at->format('d M Y') }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-white/5">
                        <p class="text-xs text-gray-400">AI Score</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ is_null($a->ai_score) ? 'Pending' : $a->ai_score . '%' }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-300">Candidate Timeline</p>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach($flowStages as $index => $stage)
                            @php
                                $isReached = is_int($currentStageIndex) && $index <= $currentStageIndex;
                            @endphp
                            <div class="rounded-lg border px-3 py-2 text-xs font-semibold {{ $isReached ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/50 dark:bg-emerald-500/10 dark:text-emerald-300' : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-white/5 dark:text-gray-300' }}">
                                {{ $stage['label'] }}
                            </div>
                        @endforeach
                    </div>
                    @if($a->status_changed_at)
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Last status update: {{ $a->status_changed_at->format('d M Y, H:i') }}
                        </p>
                    @endif
                </div>

                @if($ai)
                    <div class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">AI Reasoning</p>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $ai->reasoning }}</p>
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($columns as $s => $c)
                        <button wire:click="handleCardMoved({{ $a->id }}, '{{ $s }}')" class="btn btn-outline btn-sm {{ $a->status === $s ? '!border-brand-500 !text-brand-600' : '' }}">{{ $c['label'] }}</button>
                    @endforeach
                </div>

                <div class="modal-action">
                    <button wire:click="$set('showCardModal', false)" class="btn btn-outline">Close</button>
                    <a href="{{ route('recruiter.analysis', $a->id) }}" class="btn btn-primary">Open Analysis</a>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function kanbanBoard() {
    return {
        _onKanbanUpdated: null,
        destroy() {
            if (this._onKanbanUpdated) {
                window.removeEventListener('kanban-updated', this._onKanbanUpdated);
            }
        },
        initDrag() {
            this.$nextTick(() => this.setupSortable());
            this._onKanbanUpdated = () => {
                this.$nextTick(() => this.setupSortable());
            };
            window.addEventListener('kanban-updated', this._onKanbanUpdated);
        },
        setupSortable() {
            if (typeof Sortable === 'undefined') {
                console.warn('SortableJS is not loaded. Kanban drag/drop is disabled.');
                return;
            }

            const wire = this.$wire;
            const columns = document.querySelectorAll('.kanban-drop-zone');
            if (!columns.length) return;

            columns.forEach(column => {
                if (column._sortable) {
                    column._sortable.destroy();
                }

                column._sortable = Sortable.create(column, {
                    group: 'kanban',
                    animation: 200,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    handle: '.kanban-card',
                    filter: '.empty-placeholder',
                    onEnd: (event) => {
                        const cardId = event.item.dataset.id;
                        const newStatus = event.to.dataset.status;
                        const oldStatus = event.from.dataset.status;
                        if (newStatus === oldStatus) return;

                        if (wire && typeof wire.$call === 'function') {
                            wire.$call('handleCardMoved', parseInt(cardId, 10), newStatus);
                        }
                    }
                });
            });
        }
    }
}
</script>

<style>
.sortable-ghost {
    opacity: 0.45;
    background: #dbeafe !important;
    border: 2px dashed #3b82f6 !important;
    border-radius: 12px;
}
.sortable-drag {
    opacity: 0.95;
    transform: rotate(1deg);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.24) !important;
}
.kanban-drop-zone.sortable-over {
    background: rgba(239, 246, 255, 0.8) !important;
    border-radius: 12px;
}
</style>
@endpush
