<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UkBankHolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PhaseOneHealthController extends Controller
{
    public function __invoke(UkBankHolidayService $holidayService): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'uk_holidays' => $this->checkUkHolidays($holidayService),
            'openai' => [
                'ok' => filled(config('openai.api_key')),
                'message' => filled(config('openai.api_key'))
                    ? 'OpenAI API key detected (used for CV analysis and recruiter note generation).'
                    : 'OPENAI_API_KEY missing.',
            ],
            'email' => [
                'ok' => filled(config('mail.default')) && filled(config('mail.mailers.' . config('mail.default'))),
                'message' => 'Mailer: ' . (string) config('mail.default'),
            ],
            'uk_timezone' => [
                'ok' => true,
                'message' => 'Timezone configured: ' . (string) config('recruitment.uk_timezone', 'Europe/London'),
            ],
        ];

        $allHealthy = collect($checks)->every(fn(array $check) => (bool) ($check['ok'] ?? false));

        return response()->json([
            'phase' => 'phase_1_foundation',
            'healthy' => $allHealthy,
            'checks' => $checks,
            'api' => [
                'holidays' => route('api.v1.uk-bank-holidays'),
                'health' => route('api.v1.phase1.health'),
            ],
            'docs' => 'docs/phase1-api.md',
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::select('select 1 as ok');

            return [
                'ok' => true,
                'message' => 'Database reachable.',
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'Database check failed: ' . $exception->getMessage(),
            ];
        }
    }

    private function checkUkHolidays(UkBankHolidayService $holidayService): array
    {
        try {
            $events = $holidayService->events((int) now(config('recruitment.uk_timezone', 'Europe/London'))->year);

            return [
                'ok' => count($events) > 0,
                'message' => 'Loaded ' . count($events) . ' UK holiday events for current year.',
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'Holiday API check failed: ' . $exception->getMessage(),
            ];
        }
    }
}
