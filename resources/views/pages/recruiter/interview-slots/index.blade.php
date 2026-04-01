@extends('layouts.app')

@php
    $dayLabels = [
        'mon' => 'Monday',
        'tue' => 'Tuesday',
        'wed' => 'Wednesday',
        'thu' => 'Thursday',
        'fri' => 'Friday',
        'sat' => 'Saturday',
        'sun' => 'Sunday',
    ];

    $allSlots = $slots->flatten(1);
    $totalSlotsCount = $allSlots->count();
    $bookedSlotsCount = $allSlots->whereNotNull('booked_application_id')->count();
    $openSlotsCount = max(0, $totalSlotsCount - $bookedSlotsCount);
@endphp

@section('content')
<div class="mx-auto w-full max-w-[1450px] min-w-0 space-y-6">
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

    <section class="app-card">
        <div class="app-card-body space-y-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">NovaHire</p>
                    <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white md:text-3xl">Interview Slot Management</h1>
                    <p class="mt-2 text-sm text-gray-500">
                        Configure UK office hours, holiday exceptions, and candidate-ready interview availability.
                    </p>
                </div>

                <form method="GET" action="{{ route('recruiter.interview-slots.index') }}"
                    class="grid w-full min-w-0 grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-3 dark:border-gray-800 dark:bg-white/[0.02] sm:grid-cols-3 xl:w-auto xl:min-w-[530px]">
                    <div class="min-w-0">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">From Date</label>
                        <input type="date" name="from" value="{{ $from->toDateString() }}"
                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div class="min-w-0">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Window (days)</label>
                        <input type="number" min="7" max="42" name="days" value="{{ $days }}"
                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                            class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">
                            Refresh Window
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Range</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $from->format('d M') }} - {{ $to->format('d M') }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Days</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $slots->count() }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Total Slots</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $totalSlotsCount }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Open</p>
                    <p class="mt-1 text-sm font-semibold text-emerald-600">{{ $openSlotsCount }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Booked</p>
                    <p class="mt-1 text-sm font-semibold text-amber-600">{{ $bookedSlotsCount }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid min-w-0 grid-cols-1 gap-6 xl:grid-cols-12">
        <section class="app-card xl:col-span-8">
            <div class="app-card-body space-y-6">
                <div class="space-y-4">
                    <div class="flex flex-col gap-1">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Slot Configuration</h2>
                        <p class="text-xs text-gray-500">Set timezone, slot sizing, and active hours for each weekday.</p>
                    </div>

                    <form method="POST" action="{{ route('recruiter.interview-slots.settings.update') }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="min-w-0">
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Timezone</label>
                                <input type="text" name="timezone" value="{{ old('timezone', $settings->timezone) }}"
                                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            </div>
                            <div class="min-w-0">
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Default Mode</label>
                                <select name="default_mode"
                                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    @foreach(['video' => 'Video', 'phone' => 'Phone', 'onsite' => 'Onsite'] as $mode => $label)
                                        <option value="{{ $mode }}" @selected(old('default_mode', $settings->default_mode) === $mode)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="min-w-0">
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Duration (min)</label>
                                <input type="number" name="slot_duration_minutes" min="15" max="180"
                                    value="{{ old('slot_duration_minutes', $settings->slot_duration_minutes) }}"
                                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            </div>
                            <div class="min-w-0">
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Buffer (min)</label>
                                <input type="number" name="buffer_minutes" min="0" max="45"
                                    value="{{ old('buffer_minutes', $settings->buffer_minutes) }}"
                                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            </div>
                            <div class="min-w-0">
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Auto-generate (days)</label>
                                <input type="number" name="auto_generate_days" min="7" max="90"
                                    value="{{ old('auto_generate_days', $settings->auto_generate_days) }}"
                                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                    <input type="hidden" name="weekend_enabled" value="0">
                                    <input type="checkbox" name="weekend_enabled" value="1"
                                        @checked(old('weekend_enabled', $settings->weekend_enabled))
                                        class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    Enable Weekends
                                </label>
                            </div>
                            <div class="min-w-0 sm:col-span-2 lg:col-span-3">
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Default Meeting Link</label>
                                <input type="url" name="default_meeting_link" value="{{ old('default_meeting_link', $settings->default_meeting_link) }}"
                                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            </div>
                            <div class="min-w-0 sm:col-span-2 lg:col-span-3">
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Default Location</label>
                                <input type="text" name="default_location" value="{{ old('default_location', $settings->default_location) }}"
                                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($dayKeys as $day)
                                @php $config = (array) ($weekdays[$day] ?? []); @endphp
                                <div class="app-subcard p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $dayLabels[$day] ?? ucfirst($day) }}</p>
                                        <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                                            <input type="hidden" name="weekdays[{{ $day }}][enabled]" value="0">
                                            <input type="checkbox" name="weekdays[{{ $day }}][enabled]" value="1"
                                                @checked(old("weekdays.$day.enabled", $config['enabled'] ?? false))
                                                class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                            Active
                                        </label>
                                    </div>
                                    <div class="mt-3 grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[11px] uppercase tracking-wide text-gray-500">Start</label>
                                            <input type="time" name="weekdays[{{ $day }}][start]" value="{{ old("weekdays.$day.start", $config['start'] ?? '09:00') }}"
                                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="text-[11px] uppercase tracking-wide text-gray-500">End</label>
                                            <input type="time" name="weekdays[{{ $day }}][end]" value="{{ old("weekdays.$day.end", $config['end'] ?? '17:30') }}"
                                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit"
                            class="inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">
                            Save Slot Configuration
                        </button>
                    </form>
                </div>

                <div class="space-y-4 border-t border-gray-200 pt-5 dark:border-gray-800">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Generate Slot Window</h3>
                        <p class="text-xs text-gray-500">Rebuild unbooked availability for a specific date range.</p>
                    </div>

                    <form method="POST" action="{{ route('recruiter.interview-slots.generate') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @csrf
                        <div class="min-w-0">
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">From</label>
                            <input type="date" name="from" value="{{ $from->toDateString() }}"
                                class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </div>
                        <div class="min-w-0">
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">To</label>
                            <input type="date" name="to" value="{{ $to->toDateString() }}"
                                class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                <input type="checkbox" name="overwrite_availability" value="1"
                                    class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                Reset Open Slots
                            </label>
                        </div>
                        <div class="flex items-end">
                            <button type="submit"
                                class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-black dark:bg-white/10 dark:hover:bg-white/20">
                                Regenerate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="app-card xl:col-span-4">
            <div class="app-card-body space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Blackout & Holiday Overrides</h2>
                    <p class="mt-1 text-xs text-gray-500">Block dates, or explicitly allow holiday scheduling.</p>
                </div>

                <form method="POST" action="{{ route('recruiter.interview-slots.exceptions.store') }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="min-w-0">
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Date</label>
                            <input type="date" name="exception_date" required
                                class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </div>
                        <div class="min-w-0">
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Type</label>
                            <select name="exception_type"
                                class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="blackout">Blackout Date</option>
                                <option value="holiday_override">Holiday Override</option>
                            </select>
                        </div>
                        <div class="min-w-0">
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Start (optional)</label>
                            <input type="time" name="starts_at_time"
                                class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </div>
                        <div class="min-w-0">
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">End (optional)</label>
                            <input type="time" name="ends_at_time"
                                class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </div>
                    </div>
                    <div class="min-w-0">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reason</label>
                        <input type="text" name="reason" maxlength="255"
                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" name="is_available" value="1"
                            class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        Mark as available (holiday override only)
                    </label>
                    <button type="submit"
                        class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">
                        Save Exception
                    </button>
                </form>

                <div class="space-y-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Current Exceptions</h3>
                        <span class="text-xs text-gray-500">{{ $exceptions->count() }} total</span>
                    </div>

                    @if($exceptions->isEmpty())
                        <p class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-xs text-gray-500 dark:border-gray-700">
                            No exceptions added in this range.
                        </p>
                    @else
                        <div class="space-y-2">
                            @foreach($exceptions as $exception)
                                <div class="app-subcard p-3">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $exception->exception_date->format('d M Y') }}</p>
                                            <p class="text-xs text-gray-500">{{ str_replace('_', ' ', ucfirst($exception->exception_type)) }} · {{ $exception->is_available ? 'Available' : 'Blocked' }}</p>
                                            @if($exception->reason)
                                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $exception->reason }}</p>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ route('recruiter.interview-slots.exceptions.delete', $exception) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex h-8 items-center rounded-lg border border-red-300 px-3 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-900/50 dark:text-red-300 dark:hover:bg-red-900/20">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    <section class="app-card">
        <div class="app-card-body space-y-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Generated Slots</h2>
                <p class="text-xs text-gray-500">Booked slots stay locked. Edit mode, link, location, and open availability where allowed.</p>
            </div>

            <div class="space-y-4">
                @forelse($slots as $date => $dateSlots)
                    @php $holiday = $holidayMap->get($date); @endphp
                    <article class="app-subcard p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ \Carbon\CarbonImmutable::parse($date)->format('D, d M Y') }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">
                                    {{ $dateSlots->count() }} slots
                                </span>
                                @if($holiday)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 font-semibold text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">
                                        {{ $holiday['title'] }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 space-y-3">
                            @foreach($dateSlots as $slotItem)
                                @php
                                    $localStart = $slotItem->starts_at->timezone($slotItem->timezone);
                                    $localEnd = $slotItem->ends_at->timezone($slotItem->timezone);
                                @endphp
                                <form method="POST" action="{{ route('recruiter.interview-slots.update', $slotItem) }}"
                                    class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                    @csrf
                                    @method('PATCH')

                                    <div class="grid min-w-0 grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                                        <div class="min-w-0">
                                            <label class="text-[11px] uppercase tracking-wide text-gray-500">Time</label>
                                            <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                {{ $localStart->format('H:i') }} - {{ $localEnd->format('H:i') }}
                                            </p>
                                        </div>
                                        <div class="min-w-0">
                                            <label class="text-[11px] uppercase tracking-wide text-gray-500">Availability</label>
                                            <label class="mt-1 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                <input type="hidden" name="is_available" value="0">
                                                <input type="checkbox" name="is_available" value="1"
                                                    @checked($slotItem->is_available)
                                                    @disabled($slotItem->booked_application_id)
                                                    class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 disabled:opacity-40">
                                                {{ $slotItem->booked_application_id ? 'Locked (Booked)' : 'Open' }}
                                            </label>
                                        </div>
                                        <div class="min-w-0">
                                            <label class="text-[11px] uppercase tracking-wide text-gray-500">Mode</label>
                                            <select name="mode"
                                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                                @foreach(['video' => 'Video', 'phone' => 'Phone', 'onsite' => 'Onsite'] as $mode => $label)
                                                    <option value="{{ $mode }}" @selected($slotItem->mode === $mode)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="min-w-0">
                                            <label class="text-[11px] uppercase tracking-wide text-gray-500">Meeting Link</label>
                                            <input type="url" name="meeting_link" value="{{ $slotItem->meeting_link }}"
                                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        </div>
                                        <div class="min-w-0">
                                            <label class="text-[11px] uppercase tracking-wide text-gray-500">Location</label>
                                            <input type="text" name="location" value="{{ $slotItem->location }}"
                                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        </div>
                                        <div class="flex items-end justify-between gap-2 xl:justify-end">
                                            <span class="text-xs font-semibold {{ $slotItem->booked_application_id ? 'text-amber-600 dark:text-amber-300' : 'text-emerald-600 dark:text-emerald-300' }}">
                                                {{ $slotItem->booked_application_id ? 'Booked' : 'Open' }}
                                            </span>
                                            <button type="submit"
                                                class="inline-flex h-9 items-center rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                                                Save
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500 dark:border-gray-700">
                        No slots generated in this range. Use "Regenerate" above.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
