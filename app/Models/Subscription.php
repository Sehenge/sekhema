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
                ->where('kick_at', '>=', now()->toDateString()); // Fixed the comparison
        };
    }

    /**
     * Get the condition for a trial subscription.
     */
    private function trialSubscriptionCondition(): Closure
    {
        return function ($query): void {
            $query
                ->where('trial_kick_at', '>=', now()->toDateString()); // Fixed the comparison
        };
    }

    public function getSubscriptionForCancel(string $subscriptionId, string $telegramId): ?Subscription
    {
        return $this
            ->where('subscription', $subscriptionId)
            ->where('telegram_id', $telegramId)
            ->first();
    }
}
