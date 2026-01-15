<?php

declare(strict_types=1);

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

    public function __construct(private readonly Subscription $subscription, private readonly TelegramService $telegramService) {}

    public function paywallWebhook(Request $request): void
    {
        Log::channel('paywall-webhook')->info(now().': paywall-webhook: '.__LINE__);
        Log::channel('paywall-webhook')->info($request);

        $this->newSubscriptionText = 'Спасибо за подписку!';
        $this->telegramService->sendMessage(208791603, 'Paywall Webhook');

        match ($request->input('type')) {
            self::UPDATE_TYPE => $this->updateWebhook($request),
            self::CANCEL_TYPE => $this->cancelWebhook($request),
            default => ''
        };
    }

    private function updateWebhook(Request $request): bool
    {
        Log::channel('paywall-webhook')->info('TRY UPDATE/CREATE: '.__LINE__);
        Log::channel('paywall-webhook')->info($request);

        $subscription = $request->subscription;
        $telegramId = $request->consumerTelegramId;
        $botId = $request->bot_id;

        try {
            $subscription = $this->subscription::updateOrCreate(
                [
                    'telegram_id' => $telegramId,
                    'bot_id' => $botId,
                ],
                [
                    'subscription' => $subscription,
                    'kick_at' => Carbon::createFromFormat('Y-m-d', $request->kickAt)?->addYears(100)->toDateString(),
                    'trial_kick_at' => now()->toDateString(),
                    'updated_at' => now()->toDateTimeString(),
                ]
            );

            if ($telegramId === '1234567890') {
                // test id for webhook
                //                $this->telegramService->sendMessage(208791603, 'Test webhook, not real: ' . $this->newSubscriptionText);
                Telegram::sendMessage([
                    'chat_id' => 208791603,
                    'text' => 'Test webhook, not real: '.$this->newSubscriptionText,
                    'parse_mode' => TelegramService::PARSEMODE_MARKDOWN_V2,
                ]);
            } else {
                //                $this->telegramService->sendMessage($telegramId, $this->newSubscriptionText);
                Telegram::sendMessage([
                    'chat_id' => $telegramId,
                    'text' => $this->newSubscriptionText,
                    'parse_mode' => TelegramService::PARSEMODE_MARKDOWN_V2,
                ]);
            }

            Log::channel('paywall-webhook')->info('SUCCESS UPDATE/CREATE: '.__LINE__);
            Log::channel('paywall-webhook')->info($subscription);
        } catch (\Exception $e) {
            Log::channel('paywall-webhook')->info($e);
        }

        return true;
    }

    private function cancelWebhook(Request $request): false|string
    {
        Log::channel('paywall-webhook')->info('TRY CANCEL: '.__LINE__);
        Log::channel('paywall-webhook')->info($request);

        $subscriptionId = $request->subscription;
        $telegramId = $request->consumerTelegramId;
        $botId = $request->bot_id;

        try {

            $subscription = $this->subscription->getSubscriptionForCancel($subscriptionId, $telegramId, $botId);
            if ($subscription !== null) {
                $subscription->kick_at = $request->kickAt;
                $subscription->save();

                $this->telegramService->sendCancelSubscriptionMessage($telegramId, $subscription->kick_at);

                Log::channel('paywall-webhook')->info('SUCCESS CANCEL: '.__LINE__);
                Log::channel('paywall-webhook')->info($subscription);
            } else {
                Log::channel('paywall-webhook')->info('CANCEL: '.__LINE__);
                Log::channel('paywall-webhook')->info('SUBSCRIPTION IS NULL');
            }
        } catch (\Exception $e) {
            Log::channel('paywall-webhook')->info($e);
        }

        return true;
    }
}
