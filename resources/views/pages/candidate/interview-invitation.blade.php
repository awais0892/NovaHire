@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
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

    <div class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Interview Invitation</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
            {{ $interview->application?->jobListing?->title ?? 'Interview' }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $interview->application?->jobListing?->company?->name ?? 'Company' }}
        </p>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-xs uppercase tracking-wider text-gray-500">Date & Time</p>
                <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                    {{ $interview->starts_at?->timezone($interview->timezone)->format('d M Y H:i') }} ({{ $interview->timezone }})
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-xs uppercase tracking-wider text-gray-500">Format</p>
                <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ ucfirst($interview->mode) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm sm:col-span-2 dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-xs uppercase tracking-wider text-gray-500">Interviewer</p>
                <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $interview->scheduler?->name ?? 'Recruiting team' }}</p>
            </div>
            @if($interview->meeting_link)
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm sm:col-span-2 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Meeting Link</p>
                    <a class="mt-1 inline-block font-semibold text-brand-600 underline" href="{{ $interview->meeting_link }}" target="_blank" rel="noopener">
                        Join Interview
                    </a>
                </div>
            @endif
            @if($interview->location)
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm sm:col-span-2 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Location</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $interview->location }}</p>
                </div>
            @endif
            @if($interview->notes)
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm sm:col-span-2 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Notes</p>
                    <p class="mt-1 text-gray-700 dark:text-gray-200">{{ $interview->notes }}</p>
                </div>
            @endif
        </div>

        <div class="mt-6 border-t border-gray-100 pt-5 dark:border-gray-800">
            @if($interview->status === 'cancelled')
                <p class="text-sm font-semibold text-red-600">This interview has been cancelled.</p>
            @elseif(in_array($interview->candidate_response, ['accepted', 'declined'], true))
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                    You {{ $interview->candidate_response }} this invitation
                    @if($interview->candidate_responded_at)
                        on {{ $interview->candidate_responded_at->format('d M Y H:i') }}.
                    @endif
                </p>
            @else
                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('candidate.interviews.invitation.respond', $interview) }}">
                        @csrf
                        <input type="hidden" name="response" value="accepted">
                        <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-green-600 px-4 text-sm font-semibold text-white hover:bg-green-700">
                            Accept Invitation
                        </button>
                    </form>
                    <form method="POST" action="{{ route('candidate.interviews.invitation.respond', $interview) }}">
                        @csrf
                        <input type="hidden" name="response" value="declined">
                        <button type="submit" class="inline-flex h-10 items-center rounded-lg border border-red-300 px-4 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20">
                            Decline
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
