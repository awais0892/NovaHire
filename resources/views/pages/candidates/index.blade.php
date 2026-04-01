@extends('layouts.app')

@section('content')
<div class="p-6 max-w-[1600px] mx-auto space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Candidates</h1>
            <p class="mt-1 text-sm text-gray-500">Manage candidate profiles and track their latest application status.</p>
        </div>
        <a href="{{ route('recruiter.candidates.create') }}" class="inline-flex h-10 items-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
            Add Candidate
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Total Candidates</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $candidates->total() }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Screening</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">
                {{ $candidates->getCollection()->filter(fn($c) => strtolower((string) optional($c->applications->first())->status) === 'screening')->count() }}
            </p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Interview</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">
                {{ $candidates->getCollection()->filter(fn($c) => strtolower((string) optional($c->applications->first())->status) === 'interview')->count() }}
            </p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Hired</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">
                {{ $candidates->getCollection()->filter(fn($c) => strtolower((string) optional($c->applications->first())->status) === 'hired')->count() }}
            </p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('recruiter.candidates.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by name, email, phone"
                class="h-11 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">

            <select name="status"
                class="h-11 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">All statuses</option>
                @foreach(['applied', 'screening', 'shortlisted', 'interview', 'offer', 'hired', 'rejected'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>

            <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                Filter
            </button>
            <a href="{{ route('recruiter.candidates.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                Reset
            </a>
        </form>

        <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 dark:border-gray-800 md:flex-row md:items-center md:justify-between">
            <form method="POST" action="{{ route('recruiter.candidates.filters.store') }}" class="flex w-full flex-col gap-2 sm:flex-row md:max-w-xl">
                @csrf
                <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
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
                        <a href="{{ route('recruiter.candidates.index', array_filter($payload, fn($v) => $v !== null && $v !== '')) }}"
                            class="inline-flex h-8 items-center rounded-full bg-gray-100 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-200 dark:hover:bg-white/20">
                            {{ $savedFilter->name }}
                        </a>
                        <form method="POST" action="{{ route('recruiter.candidates.filters.delete', $savedFilter) }}">
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
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-5 py-4 font-semibold text-gray-500">Candidate</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Latest Application</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">AI Score</th>
                        <th class="px-5 py-4 font-semibold text-gray-500">Status</th>
                        <th class="px-5 py-4 text-right font-semibold text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($candidates as $candidate)
                        @php
                            $latestApp = $candidate->applications->first();
                            $status = strtolower((string) ($latestApp->status ?? 'unassigned'));
                            $statusTone = match($status) {
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
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                                        {{ strtoupper(substr($candidate->name ?? 'C', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $candidate->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $candidate->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if($latestApp)
                                    <div class="font-medium text-gray-800 dark:text-gray-100">{{ $latestApp->jobListing->title ?? 'Untitled role' }}</div>
                                    <div class="text-xs text-gray-500">{{ $latestApp->created_at?->diffForHumans() }}</div>
                                @else
                                    <span class="text-gray-400">No applications</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if(!is_null($latestApp?->ai_score))
                                    <span class="font-bold text-brand-600">{{ $latestApp->ai_score }}%</span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold uppercase {{ $statusTone }}">
                                    {{ $latestApp->status ?? 'Unassigned' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('recruiter.candidates.show', $candidate->id) }}" class="inline-flex h-9 items-center rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                                        View
                                    </a>
                                    @if($latestApp)
                                        <a href="{{ route('recruiter.ai.screen', $latestApp->id) }}" class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white hover:bg-indigo-700">
                                            AI Report
                                        </a>
                                    @endif
                                    <form action="{{ route('recruiter.candidates.destroy', $candidate->id) }}" method="POST" onsubmit="return confirm('Archive this candidate?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-red-200 px-3 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-900/60 dark:text-red-400 dark:hover:bg-red-900/20">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">
                                No candidates found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($candidates->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-800">
                {{ $candidates->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
