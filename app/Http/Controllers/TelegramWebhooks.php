<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\HandleCommandJob;
use App\Services\ChatGptService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramWebhooks extends Controller
{
    private ChatGptService $chatGpt;

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
        $update = $request->all();
        $chatId = $update['message']['chat']['id'] ?? null;
        $text = $update['message']['text'] ?? null;

        if (! $chatId || ! $text) {
            response()->json(['ok' => true]); // ничего не делаем
        }

        $this->chatGpt->ask($text, $chatId);

        response()->json(['ok' => true]); // ничего не делаем
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
