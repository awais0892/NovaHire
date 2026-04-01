<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UkBankHolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UkBankHolidayController extends Controller
{
    public function __invoke(Request $request, UkBankHolidayService $service): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'division' => [
                'nullable',
                Rule::in(['england-and-wales', 'scotland', 'northern-ireland']),
            ],
            'force' => ['nullable', 'boolean'],
        ]);

        $year = array_key_exists('year', $validated) ? (int) $validated['year'] : null;
        $division = array_key_exists('division', $validated) ? (string) $validated['division'] : null;
        $forceRefresh = filter_var((string) ($validated['force'] ?? false), FILTER_VALIDATE_BOOL);

        $events = $service->events($year, $division, $forceRefresh);

        return response()->json([
            'division' => $division ?: config('recruitment.uk_bank_holidays.division', 'england-and-wales'),
            'year' => $year,
            'timezone' => config('recruitment.uk_timezone', 'Europe/London'),
            'source' => config('recruitment.uk_bank_holidays.url'),
            'count' => count($events),
            'events' => $events,
        ]);
    }
}

