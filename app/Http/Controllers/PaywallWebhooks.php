<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PaywallWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaywallWebhooks extends Controller
{
    public function __construct(private readonly PaywallWebhookService $paywallWebhookService) {}

    public function paywallWebhook(Request $request): JsonResponse
    {
        Log::channel('paywall-webhook')->info('Webhook received', [
            'type' => $request->input('type'),
            'subscription' => $request->input('subscription'),
            'consumerTelegramId' => $request->input('consumerTelegramId'),
        ]);

        $validated = $request->validate([
            'type' => 'required|in:update,cancel',
            'subscription' => 'required|string',
            'consumerTelegramId' => 'required|string',
            'kickAt' => 'required|date',
        ]);

        try {
            match ($validated['type']) {
                'update' => $this->paywallWebhookService->handleUpdate($validated),
                'cancel' => $this->paywallWebhookService->handleCancel($validated),
            };

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::channel('paywall-webhook')->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'type' => $validated['type'],
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
