<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\AskChatGptJob;

class ChatGptService
{
    public function ask(string $prompt, int $chatId): void
    {
        AskChatGptJob::dispatch($prompt, $chatId);
    }
}
