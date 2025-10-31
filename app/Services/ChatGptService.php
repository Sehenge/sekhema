<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\StringHelper;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class ChatGptService
{
    public function ask(string $prompt, int $chatId): string
    {
        try {
            $response = Http::withToken(config('openai.api_key'))
                ->timeout(60)              // общий таймаут
                ->connectTimeout(10)       // таймаут соединения
                ->retry(3, 2000)           // 3 попытки, пауза 2 сек
                ->post(config('openai.base_url').'/chat/completions', [
                    'model' => 'gpt-5-nano',
                    'messages' => [
//                        ['role' => 'system', 'content' => 'Отвечай кратко, максимум 1–2 предложения.'],
                        ['role' => 'system', 'content' => 'Подготовь ответ в формате GPT-markdown.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            $messages = StringHelper::gptMarkdownToTgHtml($response->json('choices.0.message.content') ?? ['Ошибка при получении ответа']);

            foreach ($messages as $msg) {
                Telegram::sendMessage([
                    'chat_id' => $chatId, // 506097513
                    'text' => $msg,
                    'parse_mode' => 'HTML',
                ]);
            }

            Log::info($response->json('choices.0.message.content'));

            return $response->json('choices.0.message.content') ?? 'Ошибка при получении ответа: '.$response->getBody()->getContents();
        } catch (RequestException $e) {
            // Логируем ошибку
            Log::error('ChatGPT API error: '.$e->getMessage());

            return '⚠️ Ошибка соединения с ChatGPT, попробуйте позже';
        }
    }
}
