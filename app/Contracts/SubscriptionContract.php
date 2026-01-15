<?php

namespace App\Contracts;

interface SubscriptionContract
{
    public function checkSubscription(int $telegramUserId): bool;

    public function renewSubscription(string $telegramUsername, int $days): bool;

    public function getSubscriptionDuration(int $telegramUserId): string;

    public function activateTrial(int $telegramUserId): void;
}
