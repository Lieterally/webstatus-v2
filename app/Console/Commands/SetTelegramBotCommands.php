<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramBotCommands extends Command
{
    protected $signature = 'telegram:set-commands';
    protected $description = 'Register bot command menu buttons with Telegram';

    public function handle(): int
    {
        $token = config('services.telegram.bot_token');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured in .env');
            return self::FAILURE;
        }

        $commands = [
            ['command' => 'start', 'description' => 'Welcome message and bot info'],
            ['command' => 'help', 'description' => 'List all available commands'],
            ['command' => 'chat_id', 'description' => 'Get your Telegram chat ID'],
            ['command' => 'recepient', 'description' => 'Register as notification recipient'],
            ['command' => 'subscribe', 'description' => 'Activate notifications'],
            ['command' => 'unsubscribe', 'description' => 'Deactivate notifications'],
            ['command' => 'down', 'description' => 'List currently down sites'],
            ['command' => 'refresh', 'description' => 'Trigger manual refresh of all sites'],
        ];

        $response = Http::post("https://api.telegram.org/bot{$token}/setMyCommands", [
            'commands' => $commands,
        ]);

        if ($response->successful() && $response->json('ok')) {
            $this->info('Bot command menu registered successfully!');
            return self::SUCCESS;
        }

        $this->error('Failed: ' . $response->body());
        return self::FAILURE;
    }
}
