<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ChatGptService;
use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, ChatGptService $chatGpt)
    {
        // Получаем апдейт от Telegram
        $update = $request->all();

        // Достаём chat_id и текст сообщения
        $chatId = $update['message']['chat']['id'] ?? null;
        $text = $update['message']['text'] ?? null;

        if (! $chatId || ! $text) {
            return response()->json(['ok' => true]); // ничего не делаем
        }

        // Отправляем текст в ChatGPT
        $reply = $chatGpt->ask($text, $chatId);

        return response()->json(['ok' => true]);
    }
}
