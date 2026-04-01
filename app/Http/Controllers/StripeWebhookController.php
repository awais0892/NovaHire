<?php

namespace App\Http\Controllers;

use App\Services\Billing\StripeBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly StripeBillingService $billing)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->billing->handleWebhook(
            (string) $request->getContent(),
            (string) $request->header('Stripe-Signature', '')
        );

        if (($result['ok'] ?? false) === false) {
            Log::warning('Stripe webhook returned non-ok status.', [
                'status' => $result['status'] ?? 400,
                'ip' => $request->ip(),
            ]);
        }

        return response()->json(['ok' => (bool) ($result['ok'] ?? false)], (int) ($result['status'] ?? 200));
    }
}
