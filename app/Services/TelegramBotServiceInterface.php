<?php

namespace App\Services;

interface TelegramBotServiceInterface
{
    /** Process an incoming webhook update */
    public function handleUpdate(array $update): void;

    /** Send a message to a specific chat_id */
    public function sendMessage(string $chatId, string $message): bool;

    /** Send a message to all active targets */
    public function broadcastToActiveTargets(string $message): void;
}
