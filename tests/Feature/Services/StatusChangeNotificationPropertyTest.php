<?php

// Feature: webstatus-v2, Property 8: Status change between down states triggers updated notification

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
 * **Validates: Requirements 13.6**
 *
 * Property 8: Status change between down states triggers updated notification
 *
 * For any site with consecutive_down_count at or above the False_Positive_Threshold,
 * if the status changes between "partially_down" and "totally_down", an updated notification
 * SHALL be sent. No notification SHALL be sent if the status remains the same.
 */

beforeEach(function () {
    $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
    $this->service = new NotificationService($this->telegramMock);

    $this->category = Category::create(['name' => 'Status Change Test Category']);
    $this->staff = ITStaff::create(['name' => 'Status Change Test Staff', 'position' => 'Engineer']);
    TelegramTarget::create(['chat_id' => '555444', 'is_active' => true]);
});

/**
 * Helper: Create a site that has already received initial notification and is above threshold.
 * The site's current status is set to $currentStatus to simulate the "previous" state.
 */
function createStatusChangeSite(SiteStatus $currentStatus, int $consecutiveDownCount): Site
{
    static $counter = 0;
    $counter++;

    return Site::create([
        'name' => "Status Change Test Site {$counter}",
        'category_id' => Category::first()->id,
        'base_url' => "https://status-change-{$counter}-" . time() . '-' . mt_rand(1000, 9999) . ".example.com",
        'responsible_person_id' => ITStaff::first()->id,
        'status' => $currentStatus,
        'consecutive_down_count' => $consecutiveDownCount,
        'notification_sent' => true,
        'notification_cycle_counter' => 1, // Non-zero, non-threshold-multiple to avoid triggering repeated notifications
        'avg_response_time' => 0,
        'first_down_at' => now()->subMinutes($consecutiveDownCount * 10),
    ]);
}

/**
 * Helper: Create a SiteCheckResult that results in "totally_down" status (all pages fail).
 */
function createTotallyDownResult(int $siteId): SiteCheckResult
{
    return new SiteCheckResult(
        siteId: $siteId,
        siteName: 'Status Change Test Site',
        pageResults: collect([
            new PageCheckResult(
                pageId: 1,
                siteId: $siteId,
                url: 'https://status-change-test.example.com/page1',
                httpCode: 0,
                responseTimeMs: 0,
                errorType: ErrorType::ConnectionFailure,
            ),
            new PageCheckResult(
                pageId: 2,
                siteId: $siteId,
                url: 'https://status-change-test.example.com/page2',
                httpCode: 0,
                responseTimeMs: 0,
                errorType: ErrorType::Timeout,
            ),
        ]),
    );
}

/**
 * Helper: Create a SiteCheckResult that results in "partially_down" status (some pages up, some down).
 */
function createPartiallyDownResult(int $siteId): SiteCheckResult
{
    return new SiteCheckResult(
        siteId: $siteId,
        siteName: 'Status Change Test Site',
        pageResults: collect([
            new PageCheckResult(
                pageId: 1,
                siteId: $siteId,
                url: 'https://status-change-test.example.com/page1',
                httpCode: 200,
                responseTimeMs: 150.0,
                errorType: ErrorType::None,
            ),
            new PageCheckResult(
                pageId: 2,
                siteId: $siteId,
                url: 'https://status-change-test.example.com/page2',
                httpCode: 0,
                responseTimeMs: 0,
                errorType: ErrorType::ConnectionFailure,
            ),
        ]),
    );
}

describe('Property 8: Status change between down states triggers updated notification', function () {

    it('sends notification when status changes from partially_down to totally_down (Requirement 13.6)', function () {
        // **Validates: Requirements 13.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random consecutive_down_count at or above threshold (3)
                $consecutiveDownCount = $faker->numberBetween(3, 30);

                return ['consecutiveDownCount' => $consecutiveDownCount];
            },
            assertion: function ($data) {
                // Site is currently partially_down, new check result shows totally_down
                $site = createStatusChangeSite(SiteStatus::PartiallyDown, $data['consecutiveDownCount']);

                // Expect status change notification to be sent
                $this->telegramMock->shouldReceive('sendMessage')
                    ->atLeast()->once()
                    ->andReturn(true);

                $siteResult = createTotallyDownResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                expect($site->status)->toBe(SiteStatus::TotallyDown);
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'status_change')->exists())->toBeTrue();

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

    it('sends notification when status changes from totally_down to partially_down (Requirement 13.6)', function () {
        // **Validates: Requirements 13.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random consecutive_down_count at or above threshold (3)
                $consecutiveDownCount = $faker->numberBetween(3, 30);

                return ['consecutiveDownCount' => $consecutiveDownCount];
            },
            assertion: function ($data) {
                // Site is currently totally_down, new check result shows partially_down
                $site = createStatusChangeSite(SiteStatus::TotallyDown, $data['consecutiveDownCount']);

                // Expect status change notification to be sent
                $this->telegramMock->shouldReceive('sendMessage')
                    ->atLeast()->once()
                    ->andReturn(true);

                $siteResult = createPartiallyDownResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                expect($site->status)->toBe(SiteStatus::PartiallyDown);
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'status_change')->exists())->toBeTrue();

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

    it('does NOT send notification when status remains totally_down (Requirement 13.6)', function () {
        // **Validates: Requirements 13.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random consecutive_down_count at or above threshold
                // Use notification_cycle_counter that won't trigger repeated notification
                $consecutiveDownCount = $faker->numberBetween(3, 30);

                return ['consecutiveDownCount' => $consecutiveDownCount];
            },
            assertion: function ($data) {
                // Site is currently totally_down, new check result also totally_down (no change)
                $site = createStatusChangeSite(SiteStatus::TotallyDown, $data['consecutiveDownCount']);

                // No notification should be sent for same-status (also avoid repeated notification trigger)
                $this->telegramMock->shouldNotReceive('sendMessage');

                $siteResult = createTotallyDownResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                expect($site->status)->toBe(SiteStatus::TotallyDown);
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'status_change')->exists())->toBeFalse();

                // Cleanup
                $site->delete();
                Mockery::close();
                $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                $this->service = new NotificationService($this->telegramMock);
            },
            iterations: 100
        );
    });

    it('does NOT send notification when status remains partially_down (Requirement 13.6)', function () {
        // **Validates: Requirements 13.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random consecutive_down_count at or above threshold
                $consecutiveDownCount = $faker->numberBetween(3, 30);

                return ['consecutiveDownCount' => $consecutiveDownCount];
            },
            assertion: function ($data) {
                // Site is currently partially_down, new check result also partially_down (no change)
                $site = createStatusChangeSite(SiteStatus::PartiallyDown, $data['consecutiveDownCount']);

                // No notification should be sent for same-status
                $this->telegramMock->shouldNotReceive('sendMessage');

                $siteResult = createPartiallyDownResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                expect($site->status)->toBe(SiteStatus::PartiallyDown);
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'status_change')->exists())->toBeFalse();

                // Cleanup
                $site->delete();
                Mockery::close();
                $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                $this->service = new NotificationService($this->telegramMock);
            },
            iterations: 100
        );
    });

    it('simulates random sequences of status changes and verifies notification behavior (Requirement 13.6)', function () {
        // **Validates: Requirements 13.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate a random sequence of down states (3-8 transitions)
                $sequenceLength = $faker->numberBetween(3, 8);
                $statuses = [];
                for ($i = 0; $i < $sequenceLength; $i++) {
                    $statuses[] = $faker->randomElement([SiteStatus::PartiallyDown, SiteStatus::TotallyDown]);
                }

                return ['statuses' => $statuses];
            },
            assertion: function ($data) {
                $statuses = $data['statuses'];

                // Set a very high notification_cycle_threshold to prevent repeated notifications
                // from interfering with this test (we only care about status change notifications)
                \App\Models\SystemConfig::updateOrCreate(
                    ['key' => 'notification_cycle_threshold'],
                    ['value' => '999', 'updated_at' => now()],
                );

                // Start with a site already past the threshold with first status from sequence
                $site = createStatusChangeSite($statuses[0], 3);

                $statusChangeCount = 0;

                // Process remaining statuses in sequence (starting from index 1)
                for ($i = 1; $i < count($statuses); $i++) {
                    $previousStatus = $statuses[$i - 1];
                    $currentStatus = $statuses[$i];
                    $shouldNotify = ($previousStatus !== $currentStatus);

                    if ($shouldNotify) {
                        $this->telegramMock->shouldReceive('sendMessage')
                            ->atLeast()->once()
                            ->andReturn(true);
                        $statusChangeCount++;
                    } else {
                        $this->telegramMock->shouldNotReceive('sendMessage');
                    }

                    // Create the appropriate result based on desired status
                    if ($currentStatus === SiteStatus::TotallyDown) {
                        $siteResult = createTotallyDownResult($site->id);
                    } else {
                        $siteResult = createPartiallyDownResult($site->id);
                    }

                    $this->service->evaluateAndNotify(collect([$siteResult]));
                    $site->refresh();

                    expect($site->status)->toBe($currentStatus);

                    // Reset mock for next iteration
                    Mockery::close();
                    $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                    $this->service = new NotificationService($this->telegramMock);
                }

                // Verify notifications were sent only on actual status changes
                $actualStatusChangeNotifications = NotificationLog::where('site_id', $site->id)
                    ->where('type', 'status_change')
                    ->count();
                expect($actualStatusChangeNotifications)->toBe($statusChangeCount);

                // Cleanup
                NotificationLog::where('site_id', $site->id)->delete();
                $site->delete();
            },
            iterations: 100
        );
    });
});
