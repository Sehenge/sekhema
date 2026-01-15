<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ChatGptService;
use App\Services\TelegramService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class HandlePlainTextJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 20;

    public int $backoff = 1;

    public int $retryAfter = 60;

    public int $timeout = 60;

    public ?TelegramService $telegramService = null;

    public ?ChatGptService $chatGptService = null;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $text,
        private readonly int $chatId,
        private readonly int $messageId,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('coze_request')];
    }

    public function failed(Throwable $exception): void
    {
        Log::error($exception->getCode().'  ==>  '.$exception->getMessage());

        if ($this->telegramService instanceof TelegramService) {
            $errorMessage = 'Ошибка '.$exception->getCode().': '.$exception->getMessage();
            $this->telegramService->sendMessage(208791603, $errorMessage);
        }
        if (
            $exception->getCode() === 5001 ||
            $exception->getCode() === 403
        ) {
            if ($this->job->attempts() > 2) {
                $this->fail($exception);
            } else {
                $this->assertNotFailed();
            }
        } else {
            $this->fail($exception);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(ChatGptService $chatGptService, TelegramService $telegramService): void
    {
        $botId = 'sekhema';
        $this->chatGptService = $chatGptService;
        $this->telegramService = $telegramService;
        Log::info('HERE handle');

        if (RateLimiter::tooManyAttempts($botId, $this->tries)) {
            $seconds = RateLimiter::availableIn($botId);
            sleep($seconds);
        }

        try {
            $this->chatGptService->ask(
                $this->text,
                $this->chatId
            );

            RateLimiter::increment($botId, $this->backoff, $this->tries);
        } catch (Exception $exception) {
            $this->failed($exception);
        }
    }
}
