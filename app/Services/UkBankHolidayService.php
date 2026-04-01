<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UkBankHolidayService
{
    public function events(?int $year = null, ?string $division = null, bool $forceRefresh = false): array
    {
        $divisionKey = $this->resolveDivision($division);
        $payload = $this->payload($forceRefresh);

        $divisionData = $payload[$divisionKey] ?? null;
        if (!is_array($divisionData)) {
            throw new \RuntimeException("UK bank holiday division \"{$divisionKey}\" is not available in source data.");
        }

        $events = collect($divisionData['events'] ?? [])
            ->filter(fn($event) => is_array($event) && filled($event['date'] ?? null))
            ->map(function (array $event) {
                $date = CarbonImmutable::parse((string) $event['date'], config('recruitment.uk_timezone', 'Europe/London'));

                return [
                    'title' => (string) ($event['title'] ?? ''),
                    'date' => $date->toDateString(),
                    'year' => $date->year,
                    'notes' => (string) ($event['notes'] ?? ''),
                    'bunting' => (bool) ($event['bunting'] ?? false),
                ];
            });

        if ($year !== null) {
            $events = $events->where('year', $year);
        }

        return $events
            ->sortBy('date')
            ->values()
            ->all();
    }

    public function holidayForDate(CarbonInterface|string $date, ?string $division = null, bool $forceRefresh = false): ?array
    {
        $dateKey = $date instanceof CarbonInterface
            ? $date->copy()->timezone(config('recruitment.uk_timezone', 'Europe/London'))->toDateString()
            : CarbonImmutable::parse((string) $date, config('recruitment.uk_timezone', 'Europe/London'))->toDateString();

        return collect($this->events(null, $division, $forceRefresh))
            ->first(fn(array $event) => $event['date'] === $dateKey);
    }

    private function payload(bool $forceRefresh = false): array
    {
        $cacheKey = 'recruitment.uk_bank_holidays.payload.v1';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $cacheHours = max(1, (int) config('recruitment.uk_bank_holidays.cache_hours', 168));

        return Cache::remember($cacheKey, now()->addHours($cacheHours), function () {
            $url = (string) config('recruitment.uk_bank_holidays.url', 'https://www.gov.uk/bank-holidays.json');
            $timeout = max(1, (int) config('recruitment.uk_bank_holidays.timeout_seconds', 8));

            $response = Http::acceptJson()
                ->timeout($timeout)
                ->get($url);

            if (!$response->ok()) {
                throw new \RuntimeException("Unable to fetch UK bank holidays. HTTP status {$response->status()}.");
            }

            $json = $response->json();
            if (!is_array($json)) {
                throw new \RuntimeException('Unexpected UK bank holidays response payload.');
            }

            return $json;
        });
    }

    private function resolveDivision(?string $division): string
    {
        $raw = trim((string) ($division ?? config('recruitment.uk_bank_holidays.division', 'england-and-wales')));

        return $raw !== '' ? $raw : 'england-and-wales';
    }
}

