<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\TelegramBotServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum Telegram message length */
    private const MAX_MESSAGE_LENGTH = 4096;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $chatId,
        public readonly string $message,
        public readonly ?int $siteId = null,
        public readonly string $notificationType = 'down',
    ) {
        $this->onConnection('database');
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramBotServiceInterface $telegramBotService): void
    {
        $messages = $this->splitMessage($this->message);
        $allSent = true;

        foreach ($messages as $chunk) {
            $success = $telegramBotService->sendMessage($this->chatId, $chunk);

            if (!$success) {
                $allSent = false;
                break;
            }
        }

        if ($allSent) {
            $this->logDeliveryResult(targetsSent: 1, targetsFailed: 0);

            Log::info('Telegram notification delivered successfully', [
                'chat_id' => $this->chatId,
                'site_id' => $this->siteId,
                'type' => $this->notificationType,
            ]);
        } else {
            // If this is the last attempt, log the failure
            if ($this->attempts() >= $this->tries) {
                $this->logDeliveryResult(targetsSent: 0, targetsFailed: 1);

                Log::error('Telegram notification delivery failed after all retries', [
                    'chat_id' => $this->chatId,
                    'site_id' => $this->siteId,
                    'type' => $this->notificationType,
                    'attempts' => $this->attempts(),
                ]);
            }

            // Release back to queue for retry if attempts remain
            $this->release($this->backoff);
        }
    }

    /**
     * Handle a job failure after all retries exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        $this->logDeliveryResult(targetsSent: 0, targetsFailed: 1);

        Log::error('Telegram notification job failed permanently', [
            'chat_id' => $this->chatId,
            'site_id' => $this->siteId,
            'type' => $this->notificationType,
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * Log notification delivery result to the notification_logs table.
     */
    private function logDeliveryResult(int $targetsSent, int $targetsFailed): void
    {
        NotificationLog::create([
            'site_id' => $this->siteId,
            'type' => $this->notificationType,
            'message' => $this->message,
            'targets_sent' => $targetsSent,
            'targets_failed' => $targetsFailed,
            'sent_at' => now(),
        ]);
    }

    /**
     * Split a message into chunks that fit within Telegram's 4096 character limit.
     *
     * Splits on newline boundaries when possible.
     *
     * @return array<string>
     */
    private function splitMessage(string $message): array
    {
        if (mb_strlen($message) <= self::MAX_MESSAGE_LENGTH) {
            return [$message];
        }

        $chunks = [];
        $remaining = $message;

        while (mb_strlen($remaining) > 0) {
            if (mb_strlen($remaining) <= self::MAX_MESSAGE_LENGTH) {
                $chunks[] = $remaining;
                break;
            }

            // Try to split at a newline within the limit
            $chunk = mb_substr($remaining, 0, self::MAX_MESSAGE_LENGTH);
            $lastNewline = mb_strrpos($chunk, "\n");

            if ($lastNewline !== false && $lastNewline > 0) {
                $chunks[] = mb_substr($remaining, 0, $lastNewline);
                $remaining = mb_substr($remaining, $lastNewline + 1);
            } else {
                // No newline found; hard split at the limit
                $chunks[] = $chunk;
                $remaining = mb_substr($remaining, self::MAX_MESSAGE_LENGTH);
            }
        }

        return $chunks;
    }
}
