<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Interview;
use App\Models\InterviewSlot;
use App\Models\InterviewSlotException;
use App\Models\InterviewSlotSetting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterviewSlotEngineService
{
    private const DAY_KEYS = [
        1 => 'mon',
        2 => 'tue',
        3 => 'wed',
        4 => 'thu',
        5 => 'fri',
        6 => 'sat',
        7 => 'sun',
    ];

    public function __construct(
        private readonly UkBankHolidayService $ukBankHolidayService
    ) {
    }

    public function settingsForCompany(int $companyId): InterviewSlotSetting
    {
        $settings = InterviewSlotSetting::query()->firstOrCreate(
            ['company_id' => $companyId],
            $this->defaultSettingsPayload()
        );

        if (empty($settings->weekdays)) {
            $settings->update([
                'weekdays' => InterviewSlotSetting::defaultWeekdays(),
            ]);
            $settings->refresh();
        }

        return $settings;
    }

    public function updateSettings(int $companyId, array $attributes): InterviewSlotSetting
    {
        $settings = $this->settingsForCompany($companyId);

        $payload = [];
        foreach ([
            'timezone',
            'slot_duration_minutes',
            'buffer_minutes',
            'weekend_enabled',
            'auto_generate_days',
            'default_mode',
            'default_location',
            'default_meeting_link',
        ] as $key) {
            if (array_key_exists($key, $attributes)) {
                $payload[$key] = $attributes[$key];
            }
        }

        if (array_key_exists('weekdays', $attributes)) {
            $payload['weekdays'] = $this->normalizeWeekdays(
                (array) $attributes['weekdays'],
                (bool) ($payload['weekend_enabled'] ?? $settings->weekend_enabled)
            );
        }

        if ($payload !== []) {
            $settings->update($payload);
        }

        return $settings->fresh();
    }

    public function generateSlots(
        int $companyId,
        CarbonInterface|string $fromDate,
        CarbonInterface|string $toDate,
        bool $overwriteAvailability = false
    ): array {
        $settings = $this->settingsForCompany($companyId);
        $timezone = (string) $settings->timezone;

        $startDate = $this->toLocalDate($fromDate, $timezone)->startOfDay();
        $endDate = $this->toLocalDate($toDate, $timezone)->endOfDay();
        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->startOfDay(), $startDate->endOfDay()];
        }

        $exceptionsByDate = InterviewSlotException::query()
            ->where('company_id', $companyId)
            ->whereBetween('exception_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('exception_date')
            ->get()
            ->groupBy(fn(InterviewSlotException $exception) => $exception->exception_date->toDateString());

        $holidayMap = $this->holidayMapForRange($startDate, $endDate);
        $weekdays = $this->normalizeWeekdays((array) ($settings->weekdays ?? []), (bool) $settings->weekend_enabled);
        $durationMinutes = max(15, (int) $settings->slot_duration_minutes);
        $bufferMinutes = max(0, (int) $settings->buffer_minutes);
        $stepMinutes = max(5, $durationMinutes + $bufferMinutes);

        $generatedByStartUtc = [];
        $cursor = $startDate;

        while ($cursor->lte($endDate)) {
            $dateKey = $cursor->toDateString();
            $weekdayKey = self::DAY_KEYS[$cursor->dayOfWeekIso] ?? 'mon';
            $weekdayConfig = $weekdays[$weekdayKey] ?? InterviewSlotSetting::defaultWeekdays()[$weekdayKey];
            $dailyExceptions = $exceptionsByDate->get($dateKey, collect());
            $holiday = $holidayMap->get($dateKey);
            $blackout = $dailyExceptions->first(fn(InterviewSlotException $exception) => $exception->exception_type === 'blackout');
            $holidayOverride = $dailyExceptions->first(fn(InterviewSlotException $exception) => $exception->exception_type === 'holiday_override');

            if ($blackout && !$blackout->is_available) {
                $cursor = $cursor->addDay();
                continue;
            }

            if ($holiday && !($holidayOverride?->is_available ?? false)) {
                $cursor = $cursor->addDay();
                continue;
            }

            $enabled = (bool) ($weekdayConfig['enabled'] ?? false);
            if (in_array($weekdayKey, ['sat', 'sun'], true) && !$settings->weekend_enabled) {
                $enabled = false;
            }
            if (!$enabled) {
                $cursor = $cursor->addDay();
                continue;
            }

            $startTime = (string) ($weekdayConfig['start'] ?? '09:00');
            $endTime = (string) ($weekdayConfig['end'] ?? '17:30');

            if ($holidayOverride?->is_available) {
                if (filled($holidayOverride->starts_at_time)) {
                    $startTime = substr((string) $holidayOverride->starts_at_time, 0, 5);
                }
                if (filled($holidayOverride->ends_at_time)) {
                    $endTime = substr((string) $holidayOverride->ends_at_time, 0, 5);
                }
            }

            if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
                $cursor = $cursor->addDay();
                continue;
            }

            $windowStart = CarbonImmutable::parse("{$dateKey} {$startTime}:00", $timezone);
            $windowEnd = CarbonImmutable::parse("{$dateKey} {$endTime}:00", $timezone);
            if ($windowEnd->lte($windowStart)) {
                $cursor = $cursor->addDay();
                continue;
            }

            $slotPointer = $windowStart;
            while ($slotPointer->addMinutes($durationMinutes)->lte($windowEnd)) {
                $slotStartLocal = $slotPointer;
                $slotEndLocal = $slotPointer->addMinutes($durationMinutes);
                $slotStartUtc = $slotStartLocal->utc();
                $slotEndUtc = $slotEndLocal->utc();
                $startKey = $slotStartUtc->format('Y-m-d H:i:s');

                $generatedByStartUtc[$startKey] = [
                    'company_id' => $companyId,
                    'slot_date' => $slotStartLocal->toDateString(),
                    'starts_at' => $slotStartUtc->toDateTimeString(),
                    'ends_at' => $slotEndUtc->toDateTimeString(),
                    'timezone' => $timezone,
                    'duration_minutes' => $durationMinutes,
                    'buffer_minutes' => $bufferMinutes,
                    'mode' => (string) $settings->default_mode,
                    'is_available' => true,
                    'is_bank_holiday' => (bool) $holiday,
                    'holiday_name' => $holiday['title'] ?? null,
                    'location' => $settings->default_location,
                    'meeting_link' => $settings->default_meeting_link,
                    'meta' => [
                        'generated_by' => 'phase4_slot_engine',
                        'generated_at' => now()->toIso8601String(),
                    ],
                ];

                $slotPointer = $slotPointer->addMinutes($stepMinutes);
            }

            $cursor = $cursor->addDay();
        }

        return DB::transaction(function () use (
            $companyId,
            $startDate,
            $endDate,
            $generatedByStartUtc,
            $overwriteAvailability
        ) {
            $existingSlots = InterviewSlot::query()
                ->where('company_id', $companyId)
                ->whereBetween('slot_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get()
                ->keyBy(fn(InterviewSlot $slot) => $slot->starts_at->copy()->utc()->format('Y-m-d H:i:s'));

            $created = 0;
            $updated = 0;

            foreach ($generatedByStartUtc as $startKey => $payload) {
                $existing = $existingSlots->get($startKey);
                if (!$existing) {
                    InterviewSlot::query()->create($payload);
                    $created++;
                    continue;
                }

                $updatePayload = [
                    'ends_at' => $payload['ends_at'],
                    'timezone' => $payload['timezone'],
                    'duration_minutes' => $payload['duration_minutes'],
                    'buffer_minutes' => $payload['buffer_minutes'],
                    'is_bank_holiday' => $payload['is_bank_holiday'],
                    'holiday_name' => $payload['holiday_name'],
                    'meta' => $payload['meta'],
                ];

                if ($existing->booked_application_id === null) {
                    $updatePayload['mode'] = $payload['mode'];
                    $updatePayload['location'] = $payload['location'];
                    $updatePayload['meeting_link'] = $payload['meeting_link'];
                    if ($overwriteAvailability) {
                        $updatePayload['is_available'] = true;
                    }
                }

                $existing->fill($updatePayload);
                if ($existing->isDirty()) {
                    $existing->save();
                    $updated++;
                }
            }

            $generatedKeys = array_keys($generatedByStartUtc);
            $toDelete = $existingSlots
                ->filter(
                    fn(InterviewSlot $slot, string $key) =>
                    !in_array($key, $generatedKeys, true)
                    && $slot->booked_application_id === null
                    && (string) data_get($slot->meta, 'generated_by', '') === 'phase4_slot_engine'
                );

            $deleted = 0;
            if ($toDelete->isNotEmpty()) {
                $deleted = InterviewSlot::query()
                    ->whereIn('id', $toDelete->pluck('id'))
                    ->delete();
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'deleted' => $deleted,
                'generated_total' => count($generatedByStartUtc),
            ];
        });
    }

    public function listAvailableSlots(
        int $companyId,
        ?CarbonInterface $from = null,
        ?int $days = null
    ): Collection {
        $settings = $this->settingsForCompany($companyId);
        $timezone = (string) $settings->timezone;
        $windowDays = max(1, min(90, $days ?? (int) $settings->auto_generate_days));
        $windowStart = $from
            ? $this->toLocalDate($from, $timezone)->startOfDay()
            : CarbonImmutable::now($timezone)->startOfDay();
        $windowEnd = $windowStart->addDays($windowDays)->endOfDay();

        $this->generateSlots($companyId, $windowStart, $windowEnd);

        return InterviewSlot::query()
            ->where('company_id', $companyId)
            ->where('is_available', true)
            ->whereNull('booked_application_id')
            ->where('starts_at', '>=', now()->utc())
            ->whereBetween('slot_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->orderBy('starts_at')
            ->get()
            ->map(function (InterviewSlot $slot) {
                $localStart = $slot->starts_at->copy()->timezone($slot->timezone);
                $localEnd = $slot->ends_at->copy()->timezone($slot->timezone);

                return [
                    'id' => $slot->id,
                    'starts_at' => $slot->starts_at?->toIso8601String(),
                    'ends_at' => $slot->ends_at?->toIso8601String(),
                    'local_start' => $localStart->toIso8601String(),
                    'local_end' => $localEnd->toIso8601String(),
                    'date_label' => $localStart->format('D, d M Y'),
                    'time_label' => $localStart->format('H:i') . ' - ' . $localEnd->format('H:i'),
                    'timezone' => $slot->timezone,
                    'mode' => $slot->mode,
                    'location' => $slot->location,
                    'meeting_link' => $slot->meeting_link,
                    'is_bank_holiday' => (bool) $slot->is_bank_holiday,
                    'holiday_name' => $slot->holiday_name,
                ];
            })
            ->values();
    }

    public function bookSlotForApplication(
        Application $application,
        int $slotId,
        ?int $scheduledByUserId = null,
        array $overrides = []
    ): Interview {
        $companyId = (int) $application->company_id;

        return DB::transaction(function () use ($application, $slotId, $scheduledByUserId, $companyId, $overrides) {
            $slot = InterviewSlot::query()
                ->where('company_id', $companyId)
                ->where('id', $slotId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($slot->starts_at->lt(now()->utc())) {
                throw ValidationException::withMessages([
                    'slot_id' => 'Selected slot is in the past. Choose a future slot.',
                ]);
            }

            $bookedByAnotherApplication = $slot->booked_application_id !== null
                && $slot->booked_application_id !== $application->id;
            if ($bookedByAnotherApplication || !$slot->is_available) {
                throw ValidationException::withMessages([
                    'slot_id' => 'This slot has just been booked by someone else. Please select another slot.',
                ]);
            }

            $currentScheduled = Interview::query()
                ->where('application_id', $application->id)
                ->where('status', 'scheduled')
                ->lockForUpdate()
                ->get();

            $slotIdsToRelease = $currentScheduled
                ->pluck('interview_slot_id')
                ->filter()
                ->unique()
                ->filter(fn(int $existingSlotId) => $existingSlotId !== $slot->id)
                ->values();

            if ($currentScheduled->isNotEmpty()) {
                Interview::query()
                    ->whereIn('id', $currentScheduled->pluck('id'))
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancelled_reason' => 'Replaced by new schedule',
                        'updated_at' => now(),
                    ]);
            }

            if ($slotIdsToRelease->isNotEmpty()) {
                InterviewSlot::query()
                    ->where('company_id', $companyId)
                    ->whereIn('id', $slotIdsToRelease)
                    ->update([
                        'booked_application_id' => null,
                        'is_available' => true,
                        'updated_at' => now(),
                    ]);
            }

            $slot->update([
                'booked_application_id' => $application->id,
                'is_available' => false,
            ]);

            $interview = Interview::query()->create([
                'company_id' => $companyId,
                'application_id' => $application->id,
                'interview_slot_id' => $slot->id,
                'scheduled_by' => $scheduledByUserId,
                'starts_at' => $slot->starts_at,
                'ends_at' => $slot->ends_at,
                'timezone' => $slot->timezone,
                'mode' => (string) ($overrides['mode'] ?? $slot->mode),
                'meeting_link' => $overrides['meeting_link'] ?? $slot->meeting_link,
                'location' => $overrides['location'] ?? $slot->location,
                'notes' => $overrides['notes'] ?? null,
                'status' => 'scheduled',
                'candidate_response' => null,
                'candidate_responded_at' => null,
            ]);

            if ($application->status !== 'interview') {
                $application->update([
                    'status' => 'interview',
                    'status_changed_at' => now(),
                ]);
            }

            return $interview;
        });
    }

    public function bookNextAvailableSlotForApplication(
        Application $application,
        ?int $scheduledByUserId = null,
        array $overrides = []
    ): ?Interview {
        $companyId = (int) $application->company_id;
        $settings = $this->settingsForCompany($companyId);
        $timezone = (string) $settings->timezone;
        $days = max(7, min(90, (int) $settings->auto_generate_days));
        $from = CarbonImmutable::now($timezone)->startOfDay();
        $to = $from->addDays($days)->endOfDay();

        $this->generateSlots($companyId, $from, $to);

        $nextSlot = InterviewSlot::query()
            ->where('company_id', $companyId)
            ->where('is_available', true)
            ->whereNull('booked_application_id')
            ->where('starts_at', '>=', now()->utc())
            ->orderBy('starts_at')
            ->first();

        if (!$nextSlot) {
            return null;
        }

        return $this->bookSlotForApplication(
            application: $application,
            slotId: $nextSlot->id,
            scheduledByUserId: $scheduledByUserId,
            overrides: $overrides
        );
    }

    public function releaseInterviewSlot(?Interview $interview): void
    {
        if (!$interview || !$interview->interview_slot_id) {
            return;
        }

        InterviewSlot::query()
            ->where('id', $interview->interview_slot_id)
            ->where('company_id', $interview->company_id)
            ->where(function ($query) use ($interview) {
                $query->whereNull('booked_application_id')
                    ->orWhere('booked_application_id', $interview->application_id);
            })
            ->update([
                'booked_application_id' => null,
                'is_available' => true,
                'updated_at' => now(),
            ]);
    }

    private function defaultSettingsPayload(): array
    {
        return [
            'timezone' => (string) config('recruitment.phase4.default_timezone', config('recruitment.uk_timezone', 'Europe/London')),
            'slot_duration_minutes' => (int) config('recruitment.phase4.default_slot_duration_minutes', 45),
            'buffer_minutes' => (int) config('recruitment.phase4.default_buffer_minutes', 10),
            'weekend_enabled' => (bool) config('recruitment.phase4.weekend_enabled', false),
            'auto_generate_days' => (int) config('recruitment.phase4.auto_generate_days', 28),
            'default_mode' => (string) config('recruitment.phase4.default_mode', 'video'),
            'default_location' => null,
            'default_meeting_link' => null,
            'weekdays' => InterviewSlotSetting::defaultWeekdays(),
        ];
    }

    private function normalizeWeekdays(array $weekdays, bool $weekendEnabled): array
    {
        $normalized = InterviewSlotSetting::defaultWeekdays();
        foreach ($normalized as $day => $config) {
            $incoming = (array) ($weekdays[$day] ?? []);
            $enabled = (bool) ($incoming['enabled'] ?? $config['enabled']);
            if (in_array($day, ['sat', 'sun'], true) && !$weekendEnabled) {
                $enabled = false;
            }

            $normalized[$day] = [
                'enabled' => $enabled,
                'start' => $this->normalizeTime((string) ($incoming['start'] ?? $config['start']), $config['start']),
                'end' => $this->normalizeTime((string) ($incoming['end'] ?? $config['end']), $config['end']),
            ];
        }

        return $normalized;
    }

    private function normalizeTime(string $value, string $fallback): string
    {
        $value = trim($value);
        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        return $fallback;
    }

    private function holidayMapForRange(CarbonImmutable $startDate, CarbonImmutable $endDate): Collection
    {
        $events = collect();

        for ($year = $startDate->year; $year <= $endDate->year; $year++) {
            try {
                $events = $events->merge($this->ukBankHolidayService->events($year));
            } catch (\Throwable $exception) {
                logger()->warning('Failed to load UK bank holidays while generating interview slots.', [
                    'year' => $year,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $events
            ->filter(fn(array $event) => filled($event['date'] ?? null))
            ->keyBy(fn(array $event) => (string) $event['date']);
    }

    private function toLocalDate(CarbonInterface|string $value, string $timezone): CarbonImmutable
    {
        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value)->setTimezone($timezone);
        }

        return CarbonImmutable::parse((string) $value, $timezone);
    }
}
