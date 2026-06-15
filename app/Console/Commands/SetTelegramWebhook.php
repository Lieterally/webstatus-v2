<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:set-telegram-webhook
                            {--url= : The webhook URL (defaults to APP_URL/telegram/webhook)}
                            {--remove : Remove the current webhook}';

    /**
     * The console command description.
     */
    protected $description = 'Set or remove the Telegram bot webhook URL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = config('services.telegram.bot_token');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured in .env');
            return self::FAILURE;
        }

        // Handle webhook removal
        if ($this->option('remove')) {
            return $this->removeWebhook($token);
        }

        // Determine the webhook URL
        $url = $this->option('url') ?? rtrim(config('app.url'), '/') . '/telegram/webhook';

        // Validate the URL
        if (!str_starts_with($url, 'https://') && !str_starts_with($url, 'http://')) {
            $this->error('Webhook URL must start with https:// (or http:// for local development).');
            return self::FAILURE;
        }

        return $this->setWebhook($token, $url);
    }

    /**
     * Set the webhook URL via Telegram Bot API.
     */
    private function setWebhook(string $token, string $url): int
    {
        $this->info("Setting webhook URL: {$url}");

        $params = [
            'url' => $url,
        ];

        // Include secret token if configured
        $secret = config('services.telegram.webhook_secret');
        if (!empty($secret)) {
            $params['secret_token'] = $secret;
        }

        $response = Http::post(
            "https://api.telegram.org/bot{$token}/setWebhook",
            $params
        );

        if ($response->successful() && ($response->json('ok') === true)) {
            $this->info('✅ Webhook set successfully.');
            $this->line('Description: ' . ($response->json('description') ?? 'N/A'));
            return self::SUCCESS;
        }

        $this->error('Failed to set webhook.');
        $this->error('Response: ' . $response->body());
        return self::FAILURE;
    }

    /**
     * Remove the current webhook via Telegram Bot API.
     */
    private function removeWebhook(string $token): int
    {
        $this->info('Removing webhook...');

        $response = Http::post(
            "https://api.telegram.org/bot{$token}/deleteWebhook"
        );

        if ($response->successful() && ($response->json('ok') === true)) {
            $this->info('✅ Webhook removed successfully.');
            return self::SUCCESS;
        }

        $this->error('Failed to remove webhook.');
        $this->error('Response: ' . $response->body());
        return self::FAILURE;
    }
}
