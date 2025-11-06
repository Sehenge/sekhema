<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ChatGptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, ChatGptService $chatGpt)
    {
        Log::info(__LINE__ . 'Handle started');
        // Получаем апдейт от Telegram
        $update = $request->all();

        // Достаём chat_id и текст сообщения
        $chatId = $update['message']['chat']['id'] ?? null;
        $text = $update['message']['text'] ?? null;

        if (! $chatId || ! $text) {
            return response()->json(['ok' => true]); // ничего не делаем
        }

        Log::info(__LINE__ . ' its ok');
        // Отправляем текст в ChatGPT
        $reply = $chatGpt->ask($text, $chatId);

        return response()->json(['ok' => true]);
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
