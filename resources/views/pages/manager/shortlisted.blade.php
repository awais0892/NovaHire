@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Shortlisted Candidates</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Candidates ready for manager review.</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-5 py-3 font-medium text-gray-500">Candidate</th>
                            <th class="px-5 py-3 font-medium text-gray-500">Role</th>
                            <th class="px-5 py-3 font-medium text-gray-500">AI Score</th>
                            <th class="px-5 py-3 font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 font-medium text-gray-500">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($shortlistedCandidates as $application)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-gray-800 dark:text-white">{{ $application->candidate?->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ $application->candidate?->email ?? '' }}</div>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $application->jobListing?->title ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ ($application->ai_score ?? 0) >= 80 ? 'bg-green-100 text-green-700' : 'bg-brand-100 text-brand-700' }}">
                                        {{ $application->ai_score ?? 0 }}%
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300 uppercase text-xs font-bold">{{ $application->status }}</td>
                                <td class="px-5 py-4 text-gray-500">{{ $application->updated_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-gray-500">No shortlisted candidates yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800">
                {{ $shortlistedCandidates->links() }}
            </div>
        </div>
    </div>
@endsection

