<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\StringHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class AskChatGptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $prompt;

    private int $chatId;

    public function __construct(string $prompt, int $chatId)
    {
        $this->prompt = $prompt;
        $this->chatId = $chatId;
    }

    public function handle(): void
    {
        // показываем "печатает..."
        Telegram::sendChatAction([
            'chat_id' => $this->chatId,
            'action' => 'typing',
        ]);

        try {
            $response = Http::withToken(config('openai.api_key'))
                ->timeout(60)
                ->connectTimeout(10)
                ->retry(3, 2000)
                ->post(config('openai.base_url').'/chat/completions', [
                    'model' => 'gpt-5-nano',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Отвечай в формате GPT-markdown, кратко и по делу, не более 8 предложений.',
                        ],
                        ['role' => 'user', 'content' => $this->prompt],
                    ],
                ]);

            $content = $response->json('choices.0.message.content') ?? 'Ошибка при получении ответа';
            $messages = StringHelper::gptMarkdownToTgHtml($content);

            foreach ($messages as $msg) {
                Telegram::sendMessage([
                    'chat_id' => $this->chatId,
                    'text' => $msg,
                    'parse_mode' => 'HTML',
                ]);
            }

            Log::info('Ответ отправлен в Telegram');
        } catch (\Throwable $e) {
            Log::error('ChatGPT API error: '.$e->getMessage());
            Telegram::sendMessage([
                'chat_id' => $this->chatId,
                'text' => '⚠️ Ошибка соединения с ChatGPT, попробуйте позже',
            ]);
        }
    }
}
