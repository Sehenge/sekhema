<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class HandleCommandJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $retryAfter = 30;

    public int $timeout = 30;

    private const COMMAND_START = '/start';

    private const COMMAND_SUBSCRIPTION = '/subscription';

    private const COMMAND_BALANCE = '/balance';

    private const COMMAND_PRIVACY = '/privacy';

    private string $message;

    private int $userId;

    public ?TelegramService $telegramService = null;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly array $request
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegramService): void
    {
        $this->telegramService = $telegramService;

        try {
            RateLimiter::attempt(
                key: 'botCommand',
                maxAttempts: $perMinute = 5,
                callback: function (): void {
                    $command = explode(' ', (string) $this->request['message']['text']);
                    $source = $command[1] ?? 'organic';
                    match ($command[0]) {
                        self::COMMAND_START => $this->telegramService->startBot($this->request),
                        self::COMMAND_SUBSCRIPTION => $this->telegramService->sendSubscriptionInfo($this->request),
                        self::COMMAND_BALANCE => $this->telegramService->sendBalanceInfo($this->request),
                        default => null,
                    };
                },
                decaySeconds: 1
            );
        } catch (Exception $exception) {
            Log::error($exception->getCode().'  ==>  '.$exception->getMessage());

            $errorMessage = 'Ошибка '.$exception->getCode().': '.$exception->getMessage();
            $this->telegramService->sendMessage(478181013, $errorMessage);
            $this->telegramService->sendMessage(208791603, $errorMessage);
        }
    }
}
