<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class PaywallWebhooks extends Controller
{
    private const UPDATE_TYPE = 'update';

    private const CANCEL_TYPE = 'cancel';

    private string $newSubscriptionText;

    public function __construct(private readonly TelegramService $telegramService) {}

    public function paywallWebhook(Request $request): void
    {
        Log::channel('paywall-webhook')->info(now().': paywall-webhook: '.__LINE__);
        Log::channel('paywall-webhook')->info($request);

        $this->telegramService->sendMessage(208791603, 'Paywall Webhook');

        match ($request->input('type')) { // TODO: handle firstbill type
            self::UPDATE_TYPE => $this->updateWebhook($request),
            self::CANCEL_TYPE => $this->cancelWebhook($request),
            default => ''
        };
    }

    private function updateWebhook(Request $request): false|string
    {
        Log::channel('paywall-webhook')->info('TRY UPDATE/CREATE: '.__LINE__);


        return true;
    }

    private function cancelWebhook(Request $request): false|string
    {
        Log::channel('paywall-webhook')->info('TRY CANCEL: '.__LINE__);

        return true;
    }
}
