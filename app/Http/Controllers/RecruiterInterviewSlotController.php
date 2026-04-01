<?php

namespace App\Http\Controllers;

use App\Models\InterviewSlot;
use App\Models\InterviewSlotException;
use App\Services\InterviewSlotEngineService;
use App\Services\UkBankHolidayService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class RecruiterInterviewSlotController extends Controller
{
    private const DAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public function index(
        Request $request,
        InterviewSlotEngineService $slotEngine,
        UkBankHolidayService $holidayService
    ): View {
        $companyId = (int) (auth()->user()->company_id ?? 0);
        abort_unless($companyId > 0, 404);

        $settings = $slotEngine->settingsForCompany($companyId);
        $timezone = (string) $settings->timezone;
        $from = $this->resolveDate(
            input: (string) $request->query('from', ''),
            timezone: $timezone,
            fallback: CarbonImmutable::now($timezone)->startOfDay()
        );
        $days = max(7, min(42, (int) $request->query('days', 21)));
        $to = $from->addDays($days)->endOfDay();

        $slotEngine->generateSlots($companyId, $from, $to);

        $slots = InterviewSlot::query()
            ->where('company_id', $companyId)
            ->whereBetween('slot_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn(InterviewSlot $slot) => $slot->slot_date->toDateString());

        $exceptions = InterviewSlotException::query()
            ->where('company_id', $companyId)
            ->whereBetween('exception_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('exception_date')
            ->get();

        $holidays = collect();
        for ($year = $from->year; $year <= $to->year; $year++) {
            try {
                $holidays = $holidays->merge($holidayService->events($year));
            } catch (\Throwable $exception) {
                logger()->warning('Unable to load UK holidays on interview slot page.', [
                    'year' => $year,
                    'company_id' => $companyId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
        $holidayMap = $holidays
            ->filter(fn(array $holiday) => ($holiday['date'] ?? '') >= $from->toDateString() && ($holiday['date'] ?? '') <= $to->toDateString())
            ->keyBy(fn(array $holiday) => $holiday['date']);

        $viewName = view()->exists('pages.recruiter.interview-slots.index')
            ? 'pages.recruiter.interview-slots.index'
            : 'pages.recruiter.interview_slots.index';

        return view($viewName, [
            'title' => 'Interview Slots',
            'settings' => $settings,
            'weekdays' => $settings->weekdays ?? [],
            'from' => $from,
            'to' => $to,
            'days' => $days,
            'slots' => $slots,
            'exceptions' => $exceptions,
            'holidayMap' => $holidayMap,
            'dayKeys' => self::DAY_KEYS,
        ]);
    }

    public function updateSettings(Request $request, InterviewSlotEngineService $slotEngine): RedirectResponse
    {
        $companyId = (int) (auth()->user()->company_id ?? 0);
        abort_unless($companyId > 0, 404);

        $validated = $request->validate([
            'timezone' => ['required', 'timezone'],
            'slot_duration_minutes' => ['required', 'integer', 'min:15', 'max:180'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:45'],
            'weekend_enabled' => ['nullable', 'boolean'],
            'auto_generate_days' => ['required', 'integer', 'min:7', 'max:90'],
            'default_mode' => ['required', Rule::in(['video', 'phone', 'onsite'])],
            'default_location' => ['nullable', 'string', 'max:255'],
            'default_meeting_link' => ['nullable', 'url', 'max:255'],
            'weekdays' => ['required', 'array'],
        ]);

        $weekdays = [];
        foreach (self::DAY_KEYS as $day) {
            $start = substr((string) $request->input("weekdays.{$day}.start", '09:00'), 0, 5);
            $end = substr((string) $request->input("weekdays.{$day}.end", '17:30'), 0, 5);

            if (!preg_match('/^\d{2}:\d{2}$/', $start)) {
                $start = '09:00';
            }
            if (!preg_match('/^\d{2}:\d{2}$/', $end)) {
                $end = '17:30';
            }

            $weekdays[$day] = [
                'enabled' => $request->boolean("weekdays.{$day}.enabled"),
                'start' => $start,
                'end' => $end,
            ];
        }

        $slotEngine->updateSettings($companyId, [
            'timezone' => $validated['timezone'],
            'slot_duration_minutes' => (int) $validated['slot_duration_minutes'],
            'buffer_minutes' => (int) $validated['buffer_minutes'],
            'weekend_enabled' => $request->boolean('weekend_enabled'),
            'auto_generate_days' => (int) $validated['auto_generate_days'],
            'default_mode' => $validated['default_mode'],
            'default_location' => $validated['default_location'] ?? null,
            'default_meeting_link' => $validated['default_meeting_link'] ?? null,
            'weekdays' => $weekdays,
        ]);

        return back()->with('success', 'Interview slot settings updated.');
    }

    public function generate(Request $request, InterviewSlotEngineService $slotEngine): RedirectResponse
    {
        $companyId = (int) (auth()->user()->company_id ?? 0);
        abort_unless($companyId > 0, 404);

        $settings = $slotEngine->settingsForCompany($companyId);
        $timezone = (string) $settings->timezone;
        $from = $this->resolveDate(
            input: (string) $request->input('from', ''),
            timezone: $timezone,
            fallback: CarbonImmutable::now($timezone)->startOfDay()
        );
        $to = $this->resolveDate(
            input: (string) $request->input('to', ''),
            timezone: $timezone,
            fallback: $from->addDays(max(1, (int) $settings->auto_generate_days))->endOfDay()
        );

        $result = $slotEngine->generateSlots(
            companyId: $companyId,
            fromDate: $from,
            toDate: $to,
            overwriteAvailability: $request->boolean('overwrite_availability')
        );

        return back()->with(
            'success',
            "Slots generated. Created {$result['created']}, updated {$result['updated']}, removed {$result['deleted']} stale slots."
        );
    }

    public function storeException(Request $request): RedirectResponse
    {
        $companyId = (int) (auth()->user()->company_id ?? 0);
        abort_unless($companyId > 0, 404);

        $validated = $request->validate([
            'exception_date' => ['required', 'date'],
            'exception_type' => ['required', Rule::in(['blackout', 'holiday_override'])],
            'is_available' => ['nullable', 'boolean'],
            'starts_at_time' => ['nullable', 'date_format:H:i'],
            'ends_at_time' => ['nullable', 'date_format:H:i', 'after:starts_at_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $exceptionType = (string) $validated['exception_type'];
        $isAvailable = $exceptionType === 'holiday_override'
            ? $request->boolean('is_available')
            : false;

        InterviewSlotException::query()->updateOrCreate(
            [
                'company_id' => $companyId,
                'exception_date' => $validated['exception_date'],
                'exception_type' => $exceptionType,
            ],
            [
                'is_available' => $isAvailable,
                'starts_at_time' => $validated['starts_at_time'] ?? null,
                'ends_at_time' => $validated['ends_at_time'] ?? null,
                'reason' => $validated['reason'] ?? null,
            ]
        );

        return back()->with('success', 'Slot exception saved.');
    }

    public function deleteException(InterviewSlotException $exception): RedirectResponse
    {
        abort_unless($exception->company_id === (auth()->user()->company_id ?? null), 404);
        $exception->delete();

        return back()->with('success', 'Slot exception removed.');
    }

    public function updateSlot(Request $request, InterviewSlot $slot): RedirectResponse
    {
        abort_unless($slot->company_id === (auth()->user()->company_id ?? null), 404);

        $validated = $request->validate([
            'is_available' => ['nullable', 'boolean'],
            'mode' => ['required', Rule::in(['video', 'phone', 'onsite'])],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        if ($slot->booked_application_id && $request->boolean('is_available')) {
            return back()->withErrors(['slot' => 'A booked slot cannot be marked available.']);
        }

        $slot->update([
            'is_available' => $slot->booked_application_id ? false : $request->boolean('is_available'),
            'mode' => $validated['mode'],
            'meeting_link' => $validated['meeting_link'] ?? null,
            'location' => $validated['location'] ?? null,
        ]);

        return back()->with('success', 'Slot updated.');
    }

    private function resolveDate(string $input, string $timezone, CarbonImmutable $fallback): CarbonImmutable
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return $fallback;
        }

        try {
            return CarbonImmutable::parse($trimmed, $timezone);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
