<?php

// Feature: webstatus-v2, Property 15: Subscribe/unsubscribe round trip

use App\Models\TelegramTarget;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\PropertyTestHelpers;

/**
 * **Validates: Requirements 18.1, 18.4**
 *
 * Property 15: Subscribe/unsubscribe round trip
 *
 * For any registered Telegram target, executing subscribe followed by unsubscribe
 * SHALL result in is_active = 0, and executing unsubscribe followed by subscribe
 * SHALL result in is_active = 1. The operations are inverses of each other.
 */

beforeEach(function () {
    // Fake HTTP calls so Telegram API messages don't actually send
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    config(['services.telegram.bot_token' => 'test-token']);
});

/**
 * Helper: Create a registered Telegram target with given active state.
 */
function createTelegramTarget(bool $isActive = true): TelegramTarget
{
    static $counter = 0;
    $counter++;

    return TelegramTarget::create([
        'chat_id' => (string) (1000000 + $counter + random_int(0, 999999)),
        'is_active' => $isActive,
    ]);
}

/**
 * Helper: Simulate a subscribe command for a given chat_id through TelegramBotService.
 */
function simulateSubscribe(TelegramBotService $service, string $chatId): void
{
    $service->handleUpdate([
        'message' => [
            'text' => '/subscribe',
            'chat' => ['id' => $chatId],
        ],
    ]);
}

/**
 * Helper: Simulate an unsubscribe command for a given chat_id through TelegramBotService.
 */
function simulateUnsubscribe(TelegramBotService $service, string $chatId): void
{
    $service->handleUpdate([
        'message' => [
            'text' => '/unsubscribe',
            'chat' => ['id' => $chatId],
        ],
    ]);
}

describe('Property 15: Subscribe/unsubscribe round trip', function () {

    it('subscribe followed by unsubscribe results in is_active = 0 (Requirements 18.1, 18.4)', function () {
        // **Validates: Requirements 18.1, 18.4**
        $service = new TelegramBotService();

        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Start with is_active = true (subscribed) or false (unsubscribed)
                // We need the target to be active before unsubscribe, so start inactive
                // and subscribe first, then unsubscribe.
                return [
                    'chatId' => (string) $faker->unique()->numberBetween(100000, 99999999),
                ];
            },
            assertion: function ($data) use ($service) {
                // Create a registered target that is initially inactive
                $target = TelegramTarget::create([
                    'chat_id' => $data['chatId'],
                    'is_active' => false,
                ]);

                // Subscribe: should set is_active = 1
                simulateSubscribe($service, $data['chatId']);
                $target->refresh();
                expect($target->is_active)->toBeTrue();

                // Unsubscribe: should set is_active = 0
                simulateUnsubscribe($service, $data['chatId']);
                $target->refresh();
                expect($target->is_active)->toBeFalse();

                // Cleanup
                $target->delete();
            },
            iterations: 100
        );
    });

    it('unsubscribe followed by subscribe results in is_active = 1 (Requirements 18.1, 18.4)', function () {
        // **Validates: Requirements 18.1, 18.4**
        $service = new TelegramBotService();

        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                return [
                    'chatId' => (string) $faker->unique()->numberBetween(100000, 99999999),
                ];
            },
            assertion: function ($data) use ($service) {
                // Create a registered target that is initially active
                $target = TelegramTarget::create([
                    'chat_id' => $data['chatId'],
                    'is_active' => true,
                ]);

                // Unsubscribe: should set is_active = 0
                simulateUnsubscribe($service, $data['chatId']);
                $target->refresh();
                expect($target->is_active)->toBeFalse();

                // Subscribe: should set is_active = 1
                simulateSubscribe($service, $data['chatId']);
                $target->refresh();
                expect($target->is_active)->toBeTrue();

                // Cleanup
                $target->delete();
            },
            iterations: 100
        );
    });

    it('operations are inverses for random sequences of subscribe/unsubscribe (Requirements 18.1, 18.4)', function () {
        // **Validates: Requirements 18.1, 18.4**
        $service = new TelegramBotService();

        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate a random sequence of operations (5 to 20 operations)
                $sequenceLength = $faker->numberBetween(5, 20);
                $operations = [];

                for ($i = 0; $i < $sequenceLength; $i++) {
                    $operations[] = $faker->randomElement(['subscribe', 'unsubscribe']);
                }

                // Random initial state
                $initialActive = $faker->boolean();

                return [
                    'chatId' => (string) $faker->unique()->numberBetween(100000, 99999999),
                    'operations' => $operations,
                    'initialActive' => $initialActive,
                ];
            },
            assertion: function ($data) use ($service) {
                // Create a registered target with random initial state
                $target = TelegramTarget::create([
                    'chat_id' => $data['chatId'],
                    'is_active' => $data['initialActive'],
                ]);

                $expectedActive = $data['initialActive'];

                foreach ($data['operations'] as $operation) {
                    if ($operation === 'subscribe') {
                        simulateSubscribe($service, $data['chatId']);
                        // Subscribe sets is_active = 1 (unless already active, still 1)
                        $expectedActive = true;
                    } else {
                        simulateUnsubscribe($service, $data['chatId']);
                        // Unsubscribe sets is_active = 0 (unless already inactive, still 0)
                        $expectedActive = false;
                    }

                    $target->refresh();
                    expect($target->is_active)->toBe($expectedActive);
                }

                // Cleanup
                $target->delete();
            },
            iterations: 100
        );
    });

    it('subscribe is always the inverse of unsubscribe regardless of repetition (Requirements 18.1, 18.4)', function () {
        // **Validates: Requirements 18.1, 18.4**
        $service = new TelegramBotService();

        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Number of times to repeat the round trip
                $repetitions = $faker->numberBetween(1, 10);

                return [
                    'chatId' => (string) $faker->unique()->numberBetween(100000, 99999999),
                    'repetitions' => $repetitions,
                ];
            },
            assertion: function ($data) use ($service) {
                // Create a registered target, initially inactive
                $target = TelegramTarget::create([
                    'chat_id' => $data['chatId'],
                    'is_active' => false,
                ]);

                for ($i = 0; $i < $data['repetitions']; $i++) {
                    // Subscribe -> should be active
                    simulateSubscribe($service, $data['chatId']);
                    $target->refresh();
                    expect($target->is_active)->toBeTrue();

                    // Unsubscribe -> should be inactive
                    simulateUnsubscribe($service, $data['chatId']);
                    $target->refresh();
                    expect($target->is_active)->toBeFalse();
                }

                // Cleanup
                $target->delete();
            },
            iterations: 100
        );
    });
});
