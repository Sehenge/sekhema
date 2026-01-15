<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\StringHelper;
use Exception;
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

    public function sendBuySubscriptionMessage(int $chatId): void
    {
        Log::channel('telegram-webhook')->info(__METHOD__.' -> '.__LINE__);
        Log::channel('telegram-webhook')->info($chatId);

        $text = StringHelper::gptMarkdownToTgMarkdown2(config('ASK_SUBSCRIPTION_TEXT'));
        $replyMarkup = [
            'inline_keyboard' => [
                [
                    [
                        'text' => 'text',
                        'url' => 'url',
                    ],
                ],
            ],
        ];

        $this->sendTelegramMessage($chatId, $text, self::PARSEMODE_MARKDOWN_V2, $replyMarkup);
    }

    private function sendTelegramMessage(int $chatId, string $text, string $parseMode, array $replyMarkup = [], int $messageId = 0): void
    {
        $messageData = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($replyMarkup !== []) {
            $messageData['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }

        if ($messageId !== 0) {
            $messageData['reply_to_message_id'] = $messageId;
        }

        try {
            Telegram::sendMessage($messageData);
        } catch (Exception $e) {
            if ($e->getCode() === 403) {
                Log::channel('api-errors')->error(__METHOD__.':'.__LINE__.'  ->  '.$e->getCode());
                Log::channel('api-errors')->error(__METHOD__.':'.__LINE__.'  ->  '.$e->getMessage());

                throw new Exception($e->getMessage(), $e->getCode());
            }
        }
    }
}
