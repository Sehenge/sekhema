<?php

declare(strict_types=1);

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'telegram_id',
        'bot_id',
        'end_date',
        'trial_kick_at',
        'kick_at',
        'subscription',
        'plan_tokens',
        'used_plan_tokens',
        'trial_tokens',
        'used_trial_tokens',
    ];

    /**
     * Check if a user has an active or trial subscription.
     */
    public function checkActiveOrTrialSubscription(int $telegramUserId): bool
    {
        return $this
            ->where('telegram_id', $telegramUserId)
            ->where(function ($query): void {
                $query
                    ->where($this->activeSubscriptionCondition())
                    ->orWhere($this->trialSubscriptionCondition());
            })
            ->exists(); // Explicitly check for existence
    }

    /**
     * Get the condition for an active subscription.
     */
    private function activeSubscriptionCondition(): Closure
    {
        return function ($query): void {
            $query
                ->where('kick_at', '>=', now()->toDateString())
                ->whereColumn('plan_tokens', '>', 'used_plan_tokens');
        };
    }

    /**
     * Get the condition for a trial subscription.
     */
    private function trialSubscriptionCondition(): Closure
    {
        return function ($query): void {
            $query
                ->where('trial_kick_at', '>=', now()->toDateString())
                ->whereColumn('trial_tokens', '>', 'used_trial_tokens');
        };
    }

    public function getSubscriptionForCancel(string $subscriptionId, string $telegramId): ?Subscription
    {
        return $this::query()
            ->where('subscription', $subscriptionId)
            ->where('telegram_id', $telegramId)
            ->first();
    }

    public function chargeTokens(int $telegramUserId, int $totalTokens): void
    {
        $subscription = $this::query()
            ->where('telegram_id', $telegramUserId)
            ->first();

        if ($subscription->trial_kick_at >= now()->toDateString()) {
            $subscription->update([
                'used_trial_tokens' => $subscription->used_trial_tokens + $totalTokens,
            ]);
        }

        if ($subscription->kick_at >= now()->toDateString()) {
            $subscription->update([
                'used_tokens' => $subscription->used_tokens + $totalTokens,
            ]);
        }
    }

    public function getBalance(int $telegramUserId): ?Subscription
    {
        return $this::query()
            ->where('telegram_id', $telegramUserId)
            ->first();
    }
}
