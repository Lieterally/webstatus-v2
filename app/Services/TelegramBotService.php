<?php

namespace App\Services;

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Models\TelegramTarget;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService implements TelegramBotServiceInterface
{
    /** Maximum Telegram message length */
    private const MAX_MESSAGE_LENGTH = 4096;

    /**
     * Process an incoming webhook update.
     *
     * Parses the incoming Telegram update array, extracts the command,
     * and routes to the appropriate command handler.
     */
    public function handleUpdate(array $update): void
    {
        // Extract message from update
        $message = $update['message'] ?? null;

        if (!$message) {
            return;
        }

        $text = trim($message['text'] ?? '');
        $chatId = (string) ($message['chat']['id'] ?? '');
        $username = $message['from']['username'] ?? null;

        if ($chatId === '' || $text === '') {
            return;
        }

        // Extract command (first word, case-insensitive)
        $command = strtolower(explode(' ', $text)[0]);

        match ($command) {
            '/start' => $this->handleStart($chatId),
            '/help' => $this->handleHelp($chatId),
            '/chat_id' => $this->handleChatId($chatId),
            '/recepient' => $this->handleRecepient($chatId, $username),
            '/subscribe' => $this->handleSubscribe($chatId),
            '/unsubscribe' => $this->handleUnsubscribe($chatId),
            '/down' => $this->handleDown($chatId),
            '/refresh' => $this->handleRefresh($chatId),
            default => $this->handleUnrecognized($chatId),
        };
    }

    /**
     * Send a message to a specific chat_id via Telegram Bot API.
     *
     * If the message exceeds 4096 characters, it is split into multiple messages.
     */
    public function sendMessage(string $chatId, string $message): bool
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            Log::error('Telegram bot token is not configured.');
            return false;
        }

        // Split message if it exceeds the max length
        $messages = $this->splitMessage($message);

        foreach ($messages as $chunk) {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $chunk,
                'parse_mode' => 'HTML',
            ]);

            if (!$response->successful()) {
                Log::warning('Failed to send Telegram message', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Send a message to all active Telegram targets.
     */
    public function broadcastToActiveTargets(string $message): void
    {
        $targets = TelegramTarget::where('is_active', true)->get();

        foreach ($targets as $target) {
            $this->sendMessage($target->chat_id, $message);
        }
    }

    /**
     * Handle /start command.
     *
     * Responds with bot name, description of the monitoring system, and list of all 8 commands.
     */
    private function handleStart(string $chatId): void
    {
        $message = "🤖 <b>ITK Webstatus Bot</b>\n\n"
            . "I am the website monitoring bot for Institut Teknologi Kalimantan (ITK). "
            . "I monitor institutional websites and notify you when outages are detected.\n\n"
            . "<b>Available Commands:</b>\n"
            . "/start - Welcome message and bot info\n"
            . "/help - List all available commands\n"
            . "/chat_id - Get your Telegram chat ID\n"
            . "/recepient - Register as notification recipient\n"
            . "/subscribe - Activate notifications\n"
            . "/unsubscribe - Deactivate notifications\n"
            . "/down - List currently down sites\n"
            . "/refresh - Trigger manual refresh cycle";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /help command.
     *
     * Responds with all 8 commands and one-line descriptions.
     */
    private function handleHelp(string $chatId): void
    {
        $message = "<b>Available Commands:</b>\n\n"
            . "/start - Welcome message and bot info\n"
            . "/help - List all available commands\n"
            . "/chat_id - Get your Telegram chat ID\n"
            . "/recepient - Register as notification recipient\n"
            . "/subscribe - Activate notifications\n"
            . "/unsubscribe - Deactivate notifications\n"
            . "/down - List currently down sites\n"
            . "/refresh - Trigger manual refresh cycle";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /chat_id command.
     *
     * Returns the user's numeric Telegram chat_id. Works for all users.
     */
    private function handleChatId(string $chatId): void
    {
        $message = "Your chat ID is: <code>{$chatId}</code>";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /recepient command.
     *
     * Self-register as notification recipient. Sets is_active=1.
     * If already registered, responds accordingly.
     */
    private function handleRecepient(string $chatId, ?string $username): void
    {
        $existing = TelegramTarget::where('chat_id', $chatId)->first();

        if ($existing) {
            // Update username if it has changed
            if ($username && $existing->username !== $username) {
                $existing->update(['username' => $username]);
            }

            $this->sendMessage($chatId, "You are already registered as a notification recipient.");
            return;
        }

        try {
            TelegramTarget::create([
                'chat_id' => $chatId,
                'username' => $username,
                'is_active' => true,
            ]);

            $this->sendMessage($chatId, "✅ You have been successfully registered as a notification recipient.");
        } catch (\Throwable $e) {
            Log::error('Failed to register Telegram target', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            $this->sendMessage($chatId, "❌ Registration could not be completed. Please try again later.");
        }
    }

    /**
     * Handle /subscribe command.
     *
     * Activate notifications (set is_active=1). Must be registered first.
     */
    private function handleSubscribe(string $chatId): void
    {
        $target = TelegramTarget::where('chat_id', $chatId)->first();

        if (!$target) {
            $this->sendMessage($chatId, "You are not registered. Please register first using /recepient.");
            return;
        }

        if ($target->is_active) {
            $this->sendMessage($chatId, "You are already subscribed.");
            return;
        }

        $target->update(['is_active' => true]);

        $this->sendMessage($chatId, "✅ Notifications activated. You will now receive outage alerts.");
    }

    /**
     * Handle /unsubscribe command.
     *
     * Deactivate notifications (set is_active=0). Must be registered first.
     */
    private function handleUnsubscribe(string $chatId): void
    {
        $target = TelegramTarget::where('chat_id', $chatId)->first();

        if (!$target) {
            $this->sendMessage($chatId, "You are not registered. Please register first using /recepient.");
            return;
        }

        if (!$target->is_active) {
            $this->sendMessage($chatId, "You are already unsubscribed.");
            return;
        }

        $target->update(['is_active' => false]);

        $this->sendMessage($chatId, "✅ Notifications deactivated. You will no longer receive outage alerts.");
    }

    /**
     * Handle /down command.
     *
     * List currently down sites with name, status, and down page URLs.
     * If all sites are up, responds that all sites are operational.
     * Splits messages exceeding 4096 characters.
     */
    private function handleDown(string $chatId): void
    {
        try {
            $downSites = Site::whereIn('status', [SiteStatus::PartiallyDown, SiteStatus::TotallyDown])
                ->orderBy('name')
                ->get();

            if ($downSites->isEmpty()) {
                $this->sendMessage($chatId, "✅ All monitored sites are operational.");
                return;
            }

            $message = "<b>⚠️ Currently Down Sites:</b>\n\n";

            foreach ($downSites as $site) {
                $statusLabel = $site->status === SiteStatus::TotallyDown
                    ? '🔴 Totally Down'
                    : '🟡 Partially Down';

                $message .= "<b>{$site->name}</b>\n";
                $message .= "🔗 {$site->base_url}\n";
                $message .= "Status: {$statusLabel}\n\n";
            }

            $this->sendMessage($chatId, trim($message));
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve down sites', [
                'error' => $e->getMessage(),
            ]);

            $this->sendMessage($chatId, "❌ Status information is temporarily unavailable. Please try again later.");
        }
    }

    /**
     * Handle /refresh command.
     *
     * Trigger a manual refresh cycle. If a cycle is already in progress, respond accordingly.
     * On completion, return summary with total sites, up count, down count, and down site names.
     */
    private function handleRefresh(string $chatId): void
    {
        try {
            /** @var MonitoringServiceInterface $monitoringService */
            $monitoringService = app(MonitoringServiceInterface::class);

            // Check if a cycle is already in progress
            if ($monitoringService->isCycleInProgress()) {
                $this->sendMessage($chatId, "⏳ A refresh cycle is already in progress. Please wait for it to complete.");
                return;
            }

            // Execute the cycle
            $result = $monitoringService->executeCycle();

            // Build summary message
            $sitesUp = $result->sitesChecked - $result->sitesDown;

            $message = "✅ <b>Refresh Complete</b>\n\n"
                . "📊 Total Sites: {$result->sitesChecked}\n"
                . "🟢 Up: {$sitesUp}\n"
                . "🔴 Down: {$result->sitesDown}\n";

            if ($result->sitesDown > 0) {
                $downSites = Site::whereIn('status', [SiteStatus::PartiallyDown, SiteStatus::TotallyDown])
                    ->orderBy('name')
                    ->pluck('name');

                $message .= "\n<b>Down Sites:</b>\n";
                foreach ($downSites as $siteName) {
                    $message .= "  • {$siteName}\n";
                }
            }

            $this->sendMessage($chatId, trim($message));
        } catch (\Throwable $e) {
            Log::error('Failed to execute refresh from Telegram', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            $this->sendMessage($chatId, "❌ The refresh could not be completed. Please try again later.");
        }
    }

    /**
     * Handle unrecognized commands.
     *
     * Responds with a message that the command is not recognized and directs to /help.
     */
    private function handleUnrecognized(string $chatId): void
    {
        $this->sendMessage($chatId, "❓ Command not recognized. Send /help to see the list of available commands.");
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
