<?php

declare(strict_types=1);

namespace App\Services;

use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramService
{
    public function __construct() {}

    public function sendMessage(int $chatId, string $text): void
    {
        $messageData = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        Telegram::sendMessage($messageData);
    }
}
