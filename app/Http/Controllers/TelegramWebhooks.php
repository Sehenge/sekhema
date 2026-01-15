<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\HandleCommandJob;
use App\Jobs\HandlePlainTextJob;
use App\Services\ChatGptService;
use App\Services\SubscriptionService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramWebhooks extends Controller
{
    private ChatGptService $chatGpt;

    public function __construct(
        private readonly TelegramService $telegramService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function handle(Request $request, ChatGptService $chatGpt): JsonResponse
    {
        Log::info(__LINE__.'Handle started');
        $this->chatGpt = $chatGpt;

        try {
            if (isset($request['message']['entities']) &&
                $request['message']['entities'][0]['type'] === 'bot_command') {
                $this->handleBotCommand($request);
            } elseif (isset($request['message']['text'])) {
                $userId = $request['message']['from']['id'];
                TelegramService::sendChatAction($userId);
                $this->handlePlainTextMessage($userId, $request);
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    private function handlePlainTextMessage(int $userId, Request $request): void
    {
        $chatId = $request['message']['chat']['id'];
        $botId = $request['bot_id'];

        $this->subscriptionService->activateTrial($userId, $botId);

        if ($this->subscriptionService->checkSubscription($userId, $botId)) {
            $messageId = $request['message']['message_id'];
            //            dispatch(new TgTypingJob($chatId));
            dispatch(new HandlePlainTextJob($request['message']['text'], $chatId, $botId, $messageId))->onQueue('coze_request'); // todo: uncomment before git push
        } else {
            $this->telegramService->sendBuySubscriptionMessage($userId);
        }
    }

    private function handleBotCommand(Request $request): void
    {
        dispatch(new HandleCommandJob($request->all()));
    }

    public function setWebhook(Request $request)
    {
        $token = config('telegram.bots.mybot.token');

        // URL вебхука (тот самый маршрут, который обрабатывает апдейты)
        $webhookUrl = 'https://sekhema.dev/api/telegram/webhook';

        // Запрос к Telegram API
        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $webhookUrl,
        ]);

        return $response->json();
    }
}
