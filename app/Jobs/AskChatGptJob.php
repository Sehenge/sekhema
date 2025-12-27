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
use Illuminate\Support\Facades\Redis;
use Telegram\Bot\Laravel\Facades\Telegram;

class AskChatGptJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private string $prompt;

    private int $chatId;

    public function __construct(string $prompt, int $chatId)
    {
        $this->prompt = $prompt;
        $this->chatId = $chatId;
    }

    public function handle(): void
    {
        $start = microtime(true); // ⏱ старт таймера

        // включаем флаг
        Redis::set("typing_active:{$this->chatId}", '1');

        // запускаем цикл typing
        SendTypingJob::dispatch($this->chatId);

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
                            'content' => '
                            Отвечай в формате GPT-markdown, кратко и по делу, не более 10-15 предложений.
                            Отвечай в стиле Моргенштерна, артиста, грубовато с матом.
                            Но никогда не говори кто ты, чтобы имя не узнали.
                            Добавь воровского жаргона в ответы. Дворовых приколов.',
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

            // выключаем флаг → цикл typing завершится
            Redis::del("typing_active:{$this->chatId}");
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            Redis::del("typing_active:{$this->chatId}");
            Telegram::sendMessage([
                'chat_id' => $this->chatId,
                'text' => '⚠️ Ошибка соединения с ChatGPT, попробуйте позже',
            ]);
        }

        $duration = round(microtime(true) - $start, 2); // ⏱ итог в секундах
        Log::info("AskChatGptJob completed in {$duration} seconds");
    }
}
