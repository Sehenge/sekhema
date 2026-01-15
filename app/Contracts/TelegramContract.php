<?php

namespace App\Contracts;

interface TelegramContract
{
    public function sendMessage(int $chatId, string $text): void;

    public static function sendChatAction(int $chatId, int $botId): void;

    public function sendBuySubscriptionMessage(int $chatId): void;

    public function answerCallbackQuery(int $callbackQueryId): void;

    public function sendPreparedReplyMarkup(array $reply, int $chatId): void;

    public function startBot(array $request, string $source): void;

    public function sendSubscriptionInfo(array $request): void;

    public function sendPrivacyPolicy(array $request): void;
}
