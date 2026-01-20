<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SubscriptionContract;
use App\Models\Subscription;

readonly class SubscriptionService implements SubscriptionContract
{
    public function __construct(private Subscription $subscription) {}

    public function checkSubscription(int $telegramUserId): bool
    {
        return $this->subscription->checkActiveOrTrialSubscription($telegramUserId);
    }

    public function getSubscriptionDuration(int $telegramUserId): string
    {
        // TODO: Implement getSubscriptionDuration() method.

        return (string) Subscription::query()
            ->where('telegram_id', $telegramUserId)
            ->pluck('kick_at');
    }

    public function renewSubscription(string $telegramUsername, int $days): bool
    {
        // TODO: Implement renewSubscription() method.

        return false;
    }

    public function activateTrial(int $telegramUserId): void
    {
        $trialDays = 1;
        $trialTokens = 10000;
        $newSubscription = Subscription::firstOrCreate(
            [
                'telegram_id' => $telegramUserId,
            ]
        );
        if (! $newSubscription->trial_kick_at) {
            $newSubscription->update([
                'trial_kick_at' => now()->addDays($trialDays - 1)->toDateString(),
                'trial_tokens' => $trialTokens,
            ]);
        }
    }
}
