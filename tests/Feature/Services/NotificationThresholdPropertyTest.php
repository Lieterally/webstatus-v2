<?php

// Feature: webstatus-v2, Property 6: Down notification is sent if and only if consecutive down count reaches threshold

use App\DTOs\PageCheckResult;
use App\DTOs\SiteCheckResult;
use App\Enums\ErrorType;
use App\Enums\SiteStatus;
use App\Models\Category;
use App\Models\ITStaff;
use App\Models\NotificationLog;
use App\Models\Site;
use App\Models\TelegramTarget;
use App\Services\NotificationService;
use App\Services\TelegramBotServiceInterface;
use Tests\Helpers\PropertyTestHelpers;

/**
 * **Validates: Requirements 13.1, 13.2**
 *
 * Property 6: Down notification is sent if and only if consecutive down count reaches threshold
 *
 * For any site, a down notification SHALL be sent when consecutive_down_count reaches
 * exactly the False_Positive_Threshold (3) for the first time during an outage.
 * For any consecutive_down_count less than 3, no down notification SHALL be sent.
 */

beforeEach(function () {
    $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
    $this->service = new NotificationService($this->telegramMock);

    $this->category = Category::create(['name' => 'Property Test Category']);
    $this->staff = ITStaff::create(['name' => 'Property Test Staff', 'position' => 'Engineer']);
    TelegramTarget::create(['chat_id' => '999888', 'is_active' => true]);
});

/**
 * Helper: Create a site with given consecutive_down_count and notification state.
 */
function createPropertyTestSite(int $consecutiveDownCount, bool $notificationSent = false): Site
{
    static $counter = 0;
    $counter++;

    return Site::create([
        'name' => "Property Test Site {$counter}",
        'category_id' => Category::first()->id,
        'base_url' => "https://prop-test-{$counter}.example.com",
        'responsible_person_id' => ITStaff::first()->id,
        'status' => $consecutiveDownCount > 0 ? SiteStatus::TotallyDown : SiteStatus::Up,
        'consecutive_down_count' => $consecutiveDownCount,
        'notification_sent' => $notificationSent,
        'notification_cycle_counter' => 0,
        'avg_response_time' => 0,
        'first_down_at' => $consecutiveDownCount > 0 ? now()->subMinutes($consecutiveDownCount * 10) : null,
    ]);
}

/**
 * Helper: Create a down SiteCheckResult for a given site.
 */
function createPropertyDownResult(int $siteId): SiteCheckResult
{
    return new SiteCheckResult(
        siteId: $siteId,
        siteName: 'Property Test Site',
        pageResults: collect([
            new PageCheckResult(
                pageId: 1,
                siteId: $siteId,
                url: 'https://prop-test.example.com/page1',
                httpCode: 0,
                responseTimeMs: 0,
                errorType: ErrorType::ConnectionFailure,
            ),
        ]),
    );
}

describe('Property 6: Down notification is sent if and only if consecutive down count reaches threshold', function () {

    it('sends notification exactly when consecutive_down_count reaches 3 for the first time (Requirement 13.1)', function () {
        // **Validates: Requirements 13.1**
        forAll(
            generator: function () {
                // consecutive_down_count is 2, so next cycle will increment it to 3 (the threshold)
                return ['consecutiveDownCount' => 2];
            },
            assertion: function ($data) {
                $site = createPropertyTestSite($data['consecutiveDownCount'], notificationSent: false);

                // Expect notification to be sent (count goes from 2 -> 3 = threshold)
                $this->telegramMock->shouldReceive('sendMessage')
                    ->atLeast()->once()
                    ->andReturn(true);

                $siteResult = createPropertyDownResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                expect($site->consecutive_down_count)->toBe(3);
                expect($site->notification_sent)->toBeTrue();
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'down')->exists())->toBeTrue();

                // Cleanup for next iteration
                NotificationLog::where('site_id', $site->id)->delete();
                $site->delete();
                Mockery::close();
                $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                $this->service = new NotificationService($this->telegramMock);
            },
            iterations: 100
        );
    });

    it('does not send notification when consecutive_down_count is below threshold (Requirement 13.2)', function () {
        // **Validates: Requirements 13.2**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate random count from 0 to 1 (after increment, will be 1 or 2 - still below 3)
                $consecutiveDownCount = $faker->numberBetween(0, 1);

                return ['consecutiveDownCount' => $consecutiveDownCount];
            },
            assertion: function ($data) {
                $site = createPropertyTestSite($data['consecutiveDownCount'], notificationSent: false);

                // Expect NO notification to be sent (count will be 1 or 2 after increment, below threshold of 3)
                $this->telegramMock->shouldNotReceive('sendMessage');

                $siteResult = createPropertyDownResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                expect($site->consecutive_down_count)->toBeLessThan(3);
                expect($site->notification_sent)->toBeFalse();
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'down')->exists())->toBeFalse();

                // Cleanup for next iteration
                $site->delete();
                Mockery::close();
                $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                $this->service = new NotificationService($this->telegramMock);
            },
            iterations: 100
        );
    });

    it('notification is sent only on the first time count reaches 3, not on subsequent cycles (Requirements 13.1, 13.2)', function () {
        // **Validates: Requirements 13.1, 13.2**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Site already has notification_sent=true and count >= 3
                // Simulate being well past threshold but NOT at a repeated notification boundary
                $consecutiveDownCount = $faker->numberBetween(3, 20);

                return ['consecutiveDownCount' => $consecutiveDownCount];
            },
            assertion: function ($data) {
                $site = createPropertyTestSite($data['consecutiveDownCount'], notificationSent: true);

                // With notification_sent=true and counter not at repeated threshold multiple,
                // no new "initial" down notification should be sent.
                // We set notification_cycle_counter to 1 (not at threshold multiple)
                $site->update(['notification_cycle_counter' => 1]);

                $this->telegramMock->shouldNotReceive('sendMessage');

                $siteResult = createPropertyDownResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                // notification_sent should remain true (no reset)
                expect($site->notification_sent)->toBeTrue();

                // Cleanup for next iteration
                NotificationLog::where('site_id', $site->id)->delete();
                $site->delete();
                Mockery::close();
                $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                $this->service = new NotificationService($this->telegramMock);
            },
            iterations: 100
        );
    });

    it('simulates random cycle sequences and verifies threshold boundary behavior (Requirements 13.1, 13.2)', function () {
        // **Validates: Requirements 13.1, 13.2**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate a random number of down cycles (1 to 6)
                $totalDownCycles = $faker->numberBetween(1, 6);

                return ['totalDownCycles' => $totalDownCycles];
            },
            assertion: function ($data) {
                $totalDownCycles = $data['totalDownCycles'];

                // Start with a fresh site at count=0
                $site = createPropertyTestSite(0, notificationSent: false);

                $notificationSentOnCycle = null;

                for ($cycle = 1; $cycle <= $totalDownCycles; $cycle++) {
                    $expectedCountAfterCycle = $cycle;

                    if ($expectedCountAfterCycle === 3) {
                        // Notification should be sent on this cycle
                        $this->telegramMock->shouldReceive('sendMessage')
                            ->atLeast()->once()
                            ->andReturn(true);
                    } elseif ($expectedCountAfterCycle < 3) {
                        // No notification below threshold
                        $this->telegramMock->shouldNotReceive('sendMessage');
                    } else {
                        // Above threshold but not first time - depends on repeated notification logic
                        // Allow any behavior (may or may not send based on notification_cycle_counter)
                        $this->telegramMock->shouldReceive('sendMessage')
                            ->zeroOrMoreTimes()
                            ->andReturn(true);
                    }

                    $siteResult = createPropertyDownResult($site->id);
                    $this->service->evaluateAndNotify(collect([$siteResult]));
                    $site->refresh();

                    // Verify consecutive_down_count increments correctly
                    expect($site->consecutive_down_count)->toBe($expectedCountAfterCycle);

                    if ($expectedCountAfterCycle < 3) {
                        expect($site->notification_sent)->toBeFalse();
                    }

                    if ($expectedCountAfterCycle === 3 && $notificationSentOnCycle === null) {
                        $notificationSentOnCycle = $cycle;
                        expect($site->notification_sent)->toBeTrue();
                        expect(NotificationLog::where('site_id', $site->id)->where('type', 'down')->exists())->toBeTrue();
                    }

                    // Reset mock for next cycle
                    Mockery::close();
                    $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                    $this->service = new NotificationService($this->telegramMock);
                }

                // Final assertions
                if ($totalDownCycles < 3) {
                    // Never reached threshold - no notification
                    expect($site->notification_sent)->toBeFalse();
                    expect(NotificationLog::where('site_id', $site->id)->where('type', 'down')->exists())->toBeFalse();
                } else {
                    // Reached threshold - notification was sent
                    expect($site->notification_sent)->toBeTrue();
                    expect(NotificationLog::where('site_id', $site->id)->where('type', 'down')->exists())->toBeTrue();
                }

                // Cleanup for next iteration
                NotificationLog::where('site_id', $site->id)->delete();
                $site->delete();
            },
            iterations: 100
        );
    });
});
