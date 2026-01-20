<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\StringHelper;
use App\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Actions;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramService
{
    public const PARSEMODE_MARKDOWN_V2 = 'MarkdownV2';

    private Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

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
        $onboardingText = 'Привет! Меня зовут Сехема ❤️. Предлагаю познакомиться и начать общаться, о чём ты хочешь поговорить? Или задать мне вопрос? 🔥';

        $this->sendMessage($chatId, $onboardingText);

        $this->sendMessage(208791603, '🔥 Новый пользователь!');
    }

    public function sendSubscriptionInfo(array $request): void
    {
        $subscriptionText = config('telegram.bots.mybot.subscription');
        $chatId = $request['message']['chat']['id'];
        $this->sendMessage($chatId, $subscriptionText);
    }

    public function sendBalanceInfo(array $request): void
    {
        $chatId = $request['message']['chat']['id'];
        $balance = $this->subscription->getBalance($chatId);

        if ($balance && $balance->plan_tokens) {
            $balanceText =
                'Всего '.$balance->plan_tokens." токенов.\n".
                'Использовано '.$balance->used_plan_tokens;

        } else {
            $balanceText =
                'Пробный период '.$balance->trial_tokens." токенов. \n".
                'Использовано в пробный период '.$balance->used_trial_tokens.' токенов.';
        }
        $this->sendMessage($chatId, $balanceText);
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

        $text = StringHelper::gptMarkdownToTgMarkdown2(config('aiservices.sekhema.ask_subscription_text'));
        $replyMarkup = [
            'inline_keyboard' => [
                [
                    [
                        'text' => config('aiservices.sekhema.buy_button_text'),
                        'url' => config('aiservices.sekhema.buy_button_link'),
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
            $messageData['reply_markup'] = json_encode($replyMarkup, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        if ($messageId !== 0) {
            $messageData['reply_to_message_id'] = $messageId;
        }

        try {
            Telegram::sendMessage($messageData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
