<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

readonly class PaywallWebhookService
{
    public function __construct(
        private Subscription $subscription,
        private TelegramService $telegramService,
    ) {}

    public function handleUpdate(array $data): void
    {
        Log::channel('paywall-webhook')->info('TRY UPDATE/CREATE subscription');

        $subscriptionId = $data['subscription'];
        $telegramId = $data['consumerTelegramId'];
        $kickAt = $data['kickAt'];

        try {
            $subscription = $this->subscription->newQuery()->updateOrCreate(
                ['telegram_id' => $telegramId],
                [
                    'subscription' => $subscriptionId,
                    'kick_at' => Carbon::createFromFormat('Y-m-d', $kickAt)?->toDateString(),
                    'trial_kick_at' => now()->toDateString(),
                    'plan_tokens' => config('paywall.default_plan_tokens'),
                ]
            );

            $this->sendNewSubscriptionMessage((string) $telegramId);

            Log::channel('paywall-webhook')->info('SUCCESS UPDATE/CREATE subscription', [
                'telegram_id' => $telegramId,
                'subscription_id' => $subscriptionId,
            ]);
        } catch (\Exception $e) {
            Log::channel('paywall-webhook')->error('Failed to update/create subscription', [
                'telegram_id' => $telegramId,
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function handleCancel(array $data): void
    {
        Log::channel('paywall-webhook')->info('TRY CANCEL subscription');

        $subscriptionId = $data['subscription'];
        $telegramId = $data['consumerTelegramId'];
        $kickAt = $data['kickAt'];

        try {
            $subscription = $this->subscription->getSubscriptionForCancel($subscriptionId, (string) $telegramId);

            if ($subscription !== null) {
                $subscription->update(['kick_at' => $kickAt]);

                $this->telegramService->sendCancelSubscriptionMessage((int) $telegramId, $kickAt);

                Log::channel('paywall-webhook')->info('SUCCESS CANCEL subscription', [
                    'telegram_id' => $telegramId,
                    'subscription_id' => $subscriptionId,
                ]);
            } else {
                Log::channel('paywall-webhook')->warning('Subscription not found for cancel', [
                    'telegram_id' => $telegramId,
                    'subscription_id' => $subscriptionId,
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('paywall-webhook')->error('Failed to cancel subscription', [
                'telegram_id' => $telegramId,
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function sendNewSubscriptionMessage(string $telegramId): void
    {
        $chatId = $this->getChatIdForNotification($telegramId);
        $text = config('aiservices.sekhema.new_subscription_text');

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => TelegramService::PARSEMODE_MARKDOWN_V2,
        ]);
    }

    private function getChatIdForNotification(string $telegramId): int
    {
        if ($telegramId === config('paywall.test_user_id')) {
            return config('paywall.admin_chat_id');
        }

        return (int) $telegramId;
    }
}
