<?php

namespace App\Contracts;

interface AiContract
{
    public function getWorkspaces();

    public function createConversation(int $telegramId, array $requestPayload): mixed;

    public function chat(string $conversationId, int $userId);

    public function getConversationMessagesList(string $conversationId);

    public function getChatMessage(string $conversationId, string $chatId, int $telegramId): array;

    public function createMessage(string $question, int $telegramId);
}
