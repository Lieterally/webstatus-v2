<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramBotServiceInterface $telegramBotService,
    ) {}

    /**
     * Handle incoming Telegram webhook updates.
     *
     * Validates the request structure and routes the update
     * to the TelegramBotService for command processing.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Validate the incoming request has a valid Telegram update structure
        if (!$this->isValidUpdate($request)) {
            Log::warning('Invalid Telegram webhook request received', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => 'invalid'], 400);
        }

        // Optionally verify the webhook token if configured
        if (!$this->verifyWebhookToken($request)) {
            Log::warning('Telegram webhook token verification failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => 'unauthorized'], 401);
        }

        try {
            $update = $request->all();

            $this->telegramBotService->handleUpdate($update);

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('Error processing Telegram webhook update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to Telegram to prevent retry loops
            return response()->json(['status' => 'error']);
        }
    }

    /**
     * Validate that the incoming request contains a valid Telegram update structure.
     *
     * A valid update must have an 'update_id' field (integer) and at least one
     * of the recognized update types (message, edited_message, callback_query, etc.).
     */
    private function isValidUpdate(Request $request): bool
    {
        $data = $request->all();

        // Must have an update_id
        if (!isset($data['update_id']) || !is_numeric($data['update_id'])) {
            return false;
        }

        // Must have at least one recognized update type
        $recognizedTypes = [
            'message',
            'edited_message',
            'channel_post',
            'edited_channel_post',
            'callback_query',
            'inline_query',
            'chosen_inline_result',
        ];

        foreach ($recognizedTypes as $type) {
            if (isset($data[$type])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify the webhook token if one is configured.
     *
     * When TELEGRAM_WEBHOOK_SECRET is set in .env, the incoming request
     * must include a matching X-Telegram-Bot-Api-Secret-Token header.
     * If no secret is configured, all requests are accepted.
     */
    private function verifyWebhookToken(Request $request): bool
    {
        $secret = config('services.telegram.webhook_secret');

        // If no secret is configured, skip verification
        if (empty($secret)) {
            return true;
        }

        $headerToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        return $headerToken === $secret;
    }
}
