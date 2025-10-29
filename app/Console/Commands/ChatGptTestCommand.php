<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ChatGptService;
use Illuminate\Console\Command;

class ChatGptTestCommand extends Command
{
    /**
     * Название команды для вызова в CLI
     *
     * @var string
     */
    protected $signature = 'chatgpt:test {prompt? : Текст запроса к ChatGPT}';

    /**
     * Описание команды
     *
     * @var string
     */
    protected $description = 'Отправить тестовый запрос в ChatGPT через ChatGptService';

    /**
     * Выполнение команды
     */
    public function handle(ChatGptService $chatGpt): int
    {
        // Берём prompt из аргумента или спрашиваем у пользователя
        $prompt = $this->argument('prompt')
            ?? $this->ask('Введите текст запроса для ChatGPT');

        $this->info("⏳ Отправляем запрос: {$prompt}");

        $reply = $chatGpt->ask($prompt);

        $this->newLine();
        $this->info('💬 Ответ ChatGPT:');
        $this->line($reply);

        return self::SUCCESS;
    }
}
