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
        $update = $request->all();
        $chatId = $update['message']['chat']['id'] ?? null;
        $text   = $update['message']['text'] ?? null;

        if (! $chatId || ! $text) {
            return response('ok', 200);
        }

        // Сразу отвечаем Telegram, чтобы он не ретраил
//        dispatch(static function () use ($chatId, $text, $chatGpt) {
            $reply = $chatGpt->ask($text, $chatId);
//        });

        return response('ok', 200);
    }
}
