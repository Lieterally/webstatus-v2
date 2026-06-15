<?php

namespace App\Console\Commands;

use App\Services\TelegramBotServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll';
    protected $description = 'Poll Telegram for updates (long-polling mode for local development without ngrok)';

    public function __construct(
        private readonly TelegramBotServiceInterface $botService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $token = config('services.telegram.bot_token');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured in .env');
            return self::FAILURE;
        }

        // Delete any existing webhook first so polling works
        Http::get("https://api.telegram.org/bot{$token}/deleteWebhook");

        $this->info('Telegram polling started. Send commands to your bot!');
        $this->info('Press Ctrl+C to stop.');
        $this->newLine();

        $offset = 0;

        while (true) {
            try {
                $response = Http::timeout(30)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => 25, // Long polling timeout
                ]);

                if (!$response->successful()) {
                    $this->error('API error: ' . $response->body());
                    sleep(5);
                    continue;
                }

                $data = $response->json();
                $updates = $data['result'] ?? [];

                foreach ($updates as $update) {
                    $offset = $update['update_id'] + 1;

                    $text = $update['message']['text'] ?? '[no text]';
                    $chatId = $update['message']['chat']['id'] ?? '?';
                    $from = $update['message']['from']['first_name'] ?? 'Unknown';

                    $this->line("<info>[{$from}]</info> <comment>{$text}</comment> (chat_id: {$chatId})");

                    // Process through the bot service
                    $this->botService->handleUpdate($update);
                }
            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
                sleep(5);
            }
        }

        return self::SUCCESS;
    }
}
