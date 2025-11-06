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
    public function ask(string $prompt, int $chatId): void
    {
        Log::info(__LINE__.' promt:');
        Log::info($prompt);

        dispatch(static function () use ($prompt, $chatId) {
            // Пока ждём ответ от ChatGPT — каждые 5 секунд обновляем статус
            $running = true;
            $pid = pcntl_fork();
            if ($pid === 0) {
                // дочерний процесс: слать typing каждые 5 секунд
                while ($running) {
                    Telegram::sendChatAction([
                        'chat_id' => $chatId,
                        'action' => 'typing',
                    ]);
                    sleep(5);
                }
                exit;
            }

            // основной процесс: ждём ответ от ChatGPT
            try {
                $response = Http::withToken(config('openai.api_key'))
                    ->timeout(60)              // общий таймаут
                    ->connectTimeout(10)       // таймаут соединения
                    ->retry(3, 2000)           // 3 попытки, пауза 2 сек
                    ->post(config('openai.base_url').'/chat/completions', [
                        'model' => 'gpt-5-nano',
                        'messages' => [
                            //                        ['role' => 'system', 'content' => 'Отвечай кратко, максимум 1–2 предложения.'],
                            [
                                'role' => 'system',
                                'content' => '
                            Отвечай в формате GPT-markdown, кратко и по делу, не более 8 предложений.
                            Отвечай в стиле Моргенштерна, артиста, грубовато с матом.
                            Но никогда не говори кто ты, чтобы имя не узнали.
                            Пиши с опечатками немного',
                            ],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ]);

                $messages = StringHelper::gptMarkdownToTgHtml($response->json('choices.0.message.content') ?? ['Ошибка при получении ответа']);
                Log::info($messages[0]);
                foreach ($messages as $msg) {
                    Telegram::sendMessage([
                        'chat_id' => $chatId, // 506097513
                        'text' => $msg,
                        'parse_mode' => 'HTML',
                    ]);
                }
                Log::info('Sended');

                posix_kill($pid, SIGTERM);

                return $response->json('choices.0.message.content') ?? 'Ошибка при получении ответа: '.$response->getBody()->getContents();
            } catch (RequestException $e) {
                // Логируем ошибку
                Log::error('ChatGPT API error: '.$e->getMessage());

                posix_kill($pid, SIGTERM);

                return '⚠️ Ошибка соединения с ChatGPT, попробуйте позже';
            }
        });
    }
}
