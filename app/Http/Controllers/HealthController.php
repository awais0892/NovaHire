<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();

            return response()->json([
                'status' => 'ok',
                'app' => 'up',
                'database' => 'up',
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'degraded',
                'app' => 'up',
                'database' => 'down',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }
}
