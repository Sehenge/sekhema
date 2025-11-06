<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendTypingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $chatId;

    public function __construct(int $chatId)
    {
        $this->chatId = $chatId;
    }

    public function handle(): void
    {
        // пока флаг "typing_active" = true — шлём статус
        while (Redis::get("typing_active:{$this->chatId}") === '1') {
            Telegram::sendChatAction([
                'chat_id' => $this->chatId,
                'action' => 'typing',
            ]);
            sleep(5);
        }
    }
}
