<?php

namespace App\Contracts;

interface SubscriptionContract
{
    public function checkSubscription(int $telegramUserId, int $botId): bool;

    public function renewSubscription(string $telegramUsername, int $botId, int $days): bool;

    public function getSubscriptionDuration(int $telegramUserId, int $botId): string;

    public function activateTrial(int $telegramUserId, int $botId): void;
}
