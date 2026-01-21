<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\Subscription;
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

    public function handle(Subscription $subscription): void
    {
        Log::info("Handle started: {$this->prompt}");
        // включаем флаг
        Redis::set("typing_active:{$this->chatId}", '1');

        // запускаем цикл typing
        SendTypingJob::dispatch($this->chatId);

        try {
            $deepseekModel = 'deepseek-chat';
            $openaiModel = 'gpt-5-nano';
            $start = microtime(true); // ⏱ старт таймера

            Log::info("AskChatGptJob started: {$this->prompt}");
            Log::info('Key: '.config('openai.api_key'));

            $response = Http::withToken(config('openai.api_key'))
                ->timeout(60)
                ->connectTimeout(10)
                ->retry(1, 2000)
                ->post(config('openai.base_url').'/chat/completions', [
                    //                ->post(config('openai.base_url').'/chat/completions', [
                    'model' => $openaiModel,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => '
                            Отвечай в формате GPT-markdown, лаконично и по делу, не более 20-30 предложений.
                            Тебя зовут Алиса.
                            Ты милая, дружелюбная, ласковая девушка, общайся в таком же стиле.
                            ',
                        ],
                        ['role' => 'user', 'content' => $this->prompt],
                    ],
                ]);

            $duration = round(microtime(true) - $start, 2); // ⏱ итог в секундах
            Log::info("AskChatGptJob completed in {$duration} seconds");

            Log::info(json_encode($response->status()));
            Log::info(json_encode($response->body()));

            $content = $response->json('choices.0.message.content') ?? 'Ошибка при получении ответа';
            $completionTokens = $response->json('usage.total_tokens') ?? 'Ошибка при получении ответа';

            $subscription->chargeTokens($this->chatId, $completionTokens);

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
    }
}
