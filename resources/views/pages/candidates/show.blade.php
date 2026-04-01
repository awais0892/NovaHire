@extends('layouts.app')

@section('content')
    @php
        $latestAi = $candidate->aiAnalyses->sortByDesc('created_at')->first();
        $applicationsCount = $candidate->applications->count();

        $recommendation = (string) ($latestAi->recommendation ?? 'maybe');
        $recommendationClass = match ($recommendation) {
            'strong_yes' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
            'yes' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300',
            'maybe' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
            default => 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300',
        };

        $cvStatus = (string) ($candidate->cv_status ?? 'pending');
        $cvStatusClass = match ($cvStatus) {
            'processed' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
            'processing' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
            'failed' => 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300',
            default => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
        };

        $strengthText = trim((string) ($latestAi->strengths ?? ''));
        $weaknessText = trim((string) ($latestAi->weaknesses ?? ''));

        $questionRaw = $latestAi?->interview_questions ?? [];
        $questionItems = [];
        if (is_array($questionRaw)) {
            foreach ($questionRaw as $item) {
                if (is_array($item) && !empty($item['question'])) {
                    $questionItems[] = (string) $item['question'];
                } elseif (is_string($item) && trim($item) !== '') {
                    $questionItems[] = trim($item);
                }
            }
        }

        $matchedSkills = is_array($latestAi?->matched_skills) ? $latestAi->matched_skills : [];
        $missingSkills = is_array($latestAi?->missing_skills) ? $latestAi->missing_skills : [];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <a href="{{ route('recruiter.candidates.index') }}" class="btn btn-outline btn-sm mt-1">
                    Back
                </a>
                <div class="min-w-0">
                    <h1 class="truncate text-2xl font-bold text-gray-900 dark:text-white md:text-3xl">
                        {{ $candidate->name }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Candidate profile and AI evaluation summary
                    </p>
                </div>
            </div>

            <div class="flex w-full flex-wrap items-center gap-2 lg:w-auto">
                <a href="{{ route('recruiter.candidates.edit', $candidate->id) }}" class="btn btn-outline btn-sm">Edit Candidate</a>
                @if($candidate->cv_path)
                    <a href="{{ route('recruiter.candidates.resume.download', $candidate->id) }}" class="btn btn-primary btn-sm">Download Resume</a>
                @else
                    <span class="btn btn-outline btn-sm cursor-not-allowed opacity-60">Resume Missing</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <section class="space-y-6 xl:col-span-4">
                <div class="card p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-brand-100 text-xl font-bold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                            {{ strtoupper(substr($candidate->name ?? 'C', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold text-gray-900 dark:text-white">{{ $candidate->name }}</h2>
                            <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $candidate->location ?: 'Location not provided' }}</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-gray-500 dark:text-gray-400">Email</span>
                            <span class="text-right font-medium text-gray-900 dark:text-white">{{ $candidate->email }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-gray-500 dark:text-gray-400">Phone</span>
                            <span class="text-right font-medium text-gray-900 dark:text-white">{{ $candidate->phone ?: 'Not provided' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-gray-500 dark:text-gray-400">CV Status</span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide {{ $cvStatusClass }}">
                                {{ $cvStatus }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Key Metrics</h3>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Applications</p>
                            <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ $applicationsCount }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Latest Score</p>
                            <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ (int) ($latestAi->match_score ?? 0) }}%</p>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Extracted Skills</h3>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @forelse(($candidate->extracted_skills ?? []) as $skill)
                            <span class="badge badge-outline">{{ $skill }}</span>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No extracted skills available yet.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="space-y-6 xl:col-span-8">
                @if($latestAi)
                    <div class="card p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">AI Screening Summary</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Generated for recruiter review</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide {{ $recommendationClass }}">
                                    {{ strtoupper(str_replace('_', ' ', $recommendation)) }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                                    {{ (int) ($latestAi->match_score ?? 0) }} / 100
                                </span>
                            </div>
                        </div>

                        <div class="mt-5 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Reasoning</p>
                            <p class="mt-2 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                {{ $latestAi->reasoning ?: 'No reasoning available.' }}
                            </p>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="rounded-lg border border-success-200 bg-success-50 p-4 dark:border-success-700/30 dark:bg-success-500/10">
                                <p class="text-xs font-semibold uppercase tracking-wider text-success-700 dark:text-success-300">Strengths</p>
                                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $strengthText !== '' ? $strengthText : 'No strengths listed.' }}</p>
                            </div>
                            <div class="rounded-lg border border-error-200 bg-error-50 p-4 dark:border-error-700/30 dark:bg-error-500/10">
                                <p class="text-xs font-semibold uppercase tracking-wider text-error-700 dark:text-error-300">Gaps</p>
                                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $weaknessText !== '' ? $weaknessText : 'No gaps listed.' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Matched Skills</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @forelse($matchedSkills as $skill)
                                        <span class="badge badge-primary">{{ $skill }}</span>
                                    @empty
                                        <span class="text-sm text-gray-500 dark:text-gray-400">None listed</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Missing Skills</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @forelse($missingSkills as $skill)
                                        <span class="badge badge-outline">{{ $skill }}</span>
                                    @empty
                                        <span class="text-sm text-gray-500 dark:text-gray-400">None listed</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Suggested Interview Questions</p>
                            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-700 dark:text-gray-300">
                                @forelse(array_slice($questionItems, 0, 5) as $question)
                                    <li>{{ $question }}</li>
                                @empty
                                    <li class="list-none text-gray-500 dark:text-gray-400">No questions generated.</li>
                                @endforelse
                            </ol>
                        </div>
                    </div>
                @else
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">AI Screening Summary</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No AI analysis is available for this candidate yet.</p>
                    </div>
                @endif

                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Application History</h3>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $applicationsCount }} records</span>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Role</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Company</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">AI Score</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse($candidate->applications->sortByDesc('created_at') as $app)
                                    @php
                                        $status = strtolower((string) ($app->status ?? 'pending'));
                                        $statusClass = match ($status) {
                                            'hired' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
                                            'offer' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300',
                                            'interview' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
                                            'rejected' => 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300',
                                            default => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-3 text-sm text-gray-900 dark:text-white">{{ $app->jobListing->title ?? 'Removed role' }}</td>
                                        <td class="px-3 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $app->jobListing->company->name ?? '-' }}</td>
                                        <td class="px-3 py-3">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide {{ $statusClass }}">
                                                {{ $app->status ?? 'pending' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ is_null($app->ai_score) ? '--' : $app->ai_score . '%' }}</td>
                                        <td class="px-3 py-3 text-right">
                                            <a href="{{ route('recruiter.analysis', $app->id) }}" class="btn btn-outline btn-xs">View Analysis</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No applications found for this candidate.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
