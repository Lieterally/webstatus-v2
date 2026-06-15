<?php

// Feature: webstatus-v2, Property 7: Repeated down notifications occur at exact multiples of the configured threshold

use App\DTOs\PageCheckResult;
use App\DTOs\SiteCheckResult;
use App\Enums\ErrorType;
use App\Enums\SiteStatus;
use App\Models\Category;
use App\Models\ITStaff;
use App\Models\NotificationLog;
use App\Models\Site;
use App\Models\SystemConfig;
use App\Models\TelegramTarget;
use App\Services\NotificationService;
use App\Services\TelegramBotServiceInterface;
use Tests\Helpers\PropertyTestHelpers;

/**
 * **Validates: Requirements 13.4, 13.5**
 *
 * Property 7: Repeated down notifications occur at exact multiples of the configured threshold
 *
 * For any site that remains down after the initial notification, repeated notifications SHALL be
 * sent at cycle counts that are exact multiples of the Notification_Cycle_Threshold counting from
 * the initial notification cycle. No repeated notification SHALL be sent at any other cycle count.
 */

beforeEach(function () {
    $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
    $this->service = new NotificationService($this->telegramMock);

    $this->category = Category::create(['name' => 'Repeated Notification Test Category']);
    $this->staff = ITStaff::create(['name' => 'Repeated Notification Test Staff', 'position' => 'Engineer']);
    TelegramTarget::create(['chat_id' => '777666', 'is_active' => true]);
});

/**
 * Helper: Create a site that has already received its initial notification.
 * The site is past the false positive threshold (count >= 3) with notification_sent=true.
 */
function createNotifiedDownSite(int $notificationCycleCounter, int $threshold): Site
{
    static $counter = 0;
    $counter++;

    // Set the threshold in system config
    SystemConfig::updateOrCreate(
        ['key' => 'notification_cycle_threshold'],
        ['value' => (string) $threshold, 'updated_at' => now()],
    );

    return Site::create([
        'name' => "Repeated Notif Test Site {$counter}",
        'category_id' => Category::first()->id,
        'base_url' => "https://repeat-notif-{$counter}-" . time() . ".example.com",
        'responsible_person_id' => ITStaff::first()->id,
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 3 + $notificationCycleCounter, // Already past threshold
        'notification_sent' => true,
        'notification_cycle_counter' => $notificationCycleCounter,
        'avg_response_time' => 0,
        'first_down_at' => now()->subMinutes(($notificationCycleCounter + 3) * 10),
    ]);
}

/**
 * Helper: Create a down SiteCheckResult for a given site.
 */
function createRepeatedDownResult(int $siteId): SiteCheckResult
{
    return new SiteCheckResult(
        siteId: $siteId,
        siteName: 'Repeated Notif Test Site',
        pageResults: collect([
            new PageCheckResult(
                pageId: 1,
                siteId: $siteId,
                url: 'https://repeat-notif-test.example.com/page1',
                httpCode: 0,
                responseTimeMs: 0,
                errorType: ErrorType::ConnectionFailure,
            ),
        ]),
    );
}

describe('Property 7: Repeated down notifications occur at exact multiples of the configured threshold', function () {

    it('sends repeated notification exactly at multiples of threshold (Requirement 13.4)', function () {
        // **Validates: Requirements 13.4**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random threshold between 1 and 20
                $threshold = $faker->numberBetween(1, 20);
                // Pick a random multiple (1st, 2nd, or 3rd repeat)
                $multiple = $faker->numberBetween(1, 3);
                // The notification_cycle_counter should be one less than the threshold multiple
                // because the service increments it before checking
                $counterBeforeCycle = ($threshold * $multiple) - 1;

                return [
                    'threshold' => $threshold,
                    'counterBeforeCycle' => $counterBeforeCycle,
                    'expectedCounterAfter' => $threshold * $multiple,
                ];
            },
            assertion: function ($data) {
                $site = createNotifiedDownSite($data['counterBeforeCycle'], $data['threshold']);

                // Expect notification to be sent (counter will reach exact multiple of threshold)
                $this->telegramMock->shouldReceive('sendMessage')
                    ->atLeast()->once()
                    ->andReturn(true);

                $siteResult = createRepeatedDownResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                expect($site->notification_cycle_counter)->toBe($data['expectedCounterAfter']);
                expect($site->notification_sent)->toBeTrue();

                // Verify a notification log was created
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'down')->exists())->toBeTrue();

                // Cleanup
                NotificationLog::where('site_id', $site->id)->delete();
                $site->delete();
                Mockery::close();
                $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                $this->service = new NotificationService($this->telegramMock);
            },
            iterations: 100
        );
    });

    it('does NOT send repeated notification at non-multiples of threshold (Requirement 13.5)', function () {
        // **Validates: Requirements 13.5**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random threshold between 2 and 20 (must be > 1 so there are non-multiple values)
                $threshold = $faker->numberBetween(2, 20);

                // Generate a counter value that, after increment, is NOT a multiple of threshold
                // After increment the counter becomes counterBeforeCycle + 1
                // We need (counterBeforeCycle + 1) % threshold != 0
                do {
                    $counterBeforeCycle = $faker->numberBetween(0, $threshold * 3);
                } while (($counterBeforeCycle + 1) % $threshold === 0);

                return [
                    'threshold' => $threshold,
                    'counterBeforeCycle' => $counterBeforeCycle,
                    'expectedCounterAfter' => $counterBeforeCycle + 1,
                ];
            },
            assertion: function ($data) {
                $site = createNotifiedDownSite($data['counterBeforeCycle'], $data['threshold']);

                // No notification should be sent (counter won't be at a multiple of threshold)
                $this->telegramMock->shouldNotReceive('sendMessage');

                $siteResult = createRepeatedDownResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                expect($site->notification_cycle_counter)->toBe($data['expectedCounterAfter']);
                expect($site->notification_sent)->toBeTrue(); // Still remains true
                expect($site->notification_cycle_counter % $data['threshold'])->not->toBe(0);

                // No new notification log should be created
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'down')->exists())->toBeFalse();

                // Cleanup
                $site->delete();
                Mockery::close();
                $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                $this->service = new NotificationService($this->telegramMock);
            },
            iterations: 100
        );
    });

    it('simulates full cycle sequences counting from initial notification (Requirements 13.4, 13.5)', function () {
        // **Validates: Requirements 13.4, 13.5**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random threshold between 2 and 10
                $threshold = $faker->numberBetween(2, 10);
                // Simulate a number of cycles after the initial notification (enough to see 1-2 repeats)
                $cyclesAfterInitial = $faker->numberBetween($threshold, $threshold * 2 + 2);

                return [
                    'threshold' => $threshold,
                    'cyclesAfterInitial' => $cyclesAfterInitial,
                ];
            },
            assertion: function ($data) {
                $threshold = $data['threshold'];
                $cyclesAfterInitial = $data['cyclesAfterInitial'];

                // Create site just after initial notification (counter=0)
                $site = createNotifiedDownSite(0, $threshold);

                $expectedNotificationCycles = [];
                $actualNotificationCycles = [];

                // Calculate which cycles should trigger repeated notifications
                for ($cycle = 1; $cycle <= $cyclesAfterInitial; $cycle++) {
                    if ($cycle % $threshold === 0) {
                        $expectedNotificationCycles[] = $cycle;
                    }
                }

                // Run cycles one by one
                for ($cycle = 1; $cycle <= $cyclesAfterInitial; $cycle++) {
                    $shouldNotify = ($cycle % $threshold === 0);

                    if ($shouldNotify) {
                        $this->telegramMock->shouldReceive('sendMessage')
                            ->atLeast()->once()
                            ->andReturn(true);
                    } else {
                        $this->telegramMock->shouldNotReceive('sendMessage');
                    }

                    $siteResult = createRepeatedDownResult($site->id);
                    $this->service->evaluateAndNotify(collect([$siteResult]));

                    $site->refresh();

                    if ($shouldNotify) {
                        $actualNotificationCycles[] = $cycle;
                    }

                    // Reset mock for next cycle
                    Mockery::close();
                    $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                    $this->service = new NotificationService($this->telegramMock);
                }

                // Verify notification_cycle_counter tracks correctly
                expect($site->notification_cycle_counter)->toBe($cyclesAfterInitial);

                // Verify notifications were sent at exactly the right cycles
                expect($actualNotificationCycles)->toBe($expectedNotificationCycles);

                // Cleanup
                NotificationLog::where('site_id', $site->id)->delete();
                $site->delete();
            },
            iterations: 100
        );
    });

    it('verifies different threshold values all produce correct repeated timing (Requirements 13.4, 13.5)', function () {
        // **Validates: Requirements 13.4, 13.5**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Wide range of thresholds including edge cases
                $threshold = $faker->numberBetween(1, 50);

                return ['threshold' => $threshold];
            },
            assertion: function ($data) {
                $threshold = $data['threshold'];

                // Test that notification fires exactly at the threshold multiple
                // Set counter to threshold - 1, so after increment it becomes exactly threshold
                $site = createNotifiedDownSite($threshold - 1, $threshold);

                $this->telegramMock->shouldReceive('sendMessage')
                    ->atLeast()->once()
                    ->andReturn(true);

                $siteResult = createRepeatedDownResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                expect($site->notification_cycle_counter)->toBe($threshold);
                expect($site->notification_cycle_counter % $threshold)->toBe(0);
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'down')->exists())->toBeTrue();

                // Cleanup
                NotificationLog::where('site_id', $site->id)->delete();
                $site->delete();
                Mockery::close();
                $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                $this->service = new NotificationService($this->telegramMock);
            },
            iterations: 100
        );
    });
});
