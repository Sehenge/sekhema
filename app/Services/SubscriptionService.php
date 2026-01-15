<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SubscriptionContract;
use App\Models\Subscription;

readonly class SubscriptionService implements SubscriptionContract
{
    public function __construct(private Subscription $subscription) {}

    public function checkSubscription(int $telegramUserId, int $botId): bool
    {
        return $this->subscription->checkActiveOrTrialSubscription($telegramUserId, $botId);
    }

    public function getSubscriptionDuration(int $telegramUserId, int $botId): string
    {
        // TODO: Implement getSubscriptionDuration() method.

        return (string) Subscription::query()
            ->where('telegram_id', $telegramUserId)
            ->pluck('kick_at');
    }

    public function renewSubscription(string $telegramUsername, int $botId, int $days): bool
    {
        // TODO: Implement renewSubscription() method.

        return false;
    }

    public function activateTrial(int $telegramUserId, int $botId): void
    {
        $trialDays = 1;
        $newSubscription = Subscription::firstOrCreate(
            [
                'telegram_id' => $telegramUserId,
            ]
        );
        if (! $newSubscription->trial_kick_at) {
            $newSubscription->update([
                'trial_kick_at' => now()->addDays($trialDays - 1)->toDateString(),
            ]);
        }
    }
}
