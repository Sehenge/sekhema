<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Actions;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramService
{
    public const PARSEMODE_MARKDOWN_V2 = 'MarkdownV2';

    public function __construct() {}

    public function sendMessage(int $chatId, string $text): void
    {
        $messageData = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        Telegram::sendMessage($messageData);
    }

    public function sendCancelSubscriptionMessage(int $chatId, string $kickAt): void
    {
        Log::channel('telegram-webhook')->info(__METHOD__.' -> '.__LINE__);
        Log::channel('telegram-webhook')->info($chatId);

        if ($chatId === 1234567890) {
            $chatId = 208791603;
        }

        $cancelText = 'Жаль, что отписались :(';

        $this->sendMessage($chatId, $cancelText);
    }

    public function startBot(array $request): void
    {
        $chatId = $request['message']['chat']['id'];
        $onboardingText = 'Добро пожаловать в чат!';

        $this->sendMessage($chatId, $onboardingText);
    }

    public function sendSubscriptionInfo(array $request): void
    {
        $subscriptionText = config('telegram.bots.mybot.subscription');
        $chatId = $request['message']['chat']['id'];
        $this->sendMessage($chatId, $subscriptionText);
    }

    public static function sendChatAction(int $chatId): void
    {
        Telegram::sendChatAction([
            'chat_id' => $chatId,
            'action' => Actions::TYPING,
        ]);
    }
}
