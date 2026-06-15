<?php

// Feature: webstatus-v2, Property 9: Recovery notification conditions

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
 * **Validates: Requirements 14.1, 14.3, 14.5**
 *
 * Property 9: Recovery notification conditions
 *
 * For any site that transitions from "totally_down" or "partially_down" to "up":
 * - A recovery notification SHALL be sent if and only if a down notification was previously sent (notification_sent = true)
 * - No recovery notification SHALL be sent if the site recovered before reaching the False_Positive_Threshold
 * - No recovery notification SHALL be sent when transitioning from "totally_down" to "partially_down" (both are still down states)
 */

beforeEach(function () {
    $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
    $this->service = new NotificationService($this->telegramMock);

    $this->category = Category::create(['name' => 'Recovery Test Category']);
    $this->staff = ITStaff::create(['name' => 'Recovery Test Staff', 'position' => 'Engineer']);
    TelegramTarget::create(['chat_id' => '111222', 'is_active' => true]);

    // Set a high notification_cycle_threshold to avoid repeated notification interference
    SystemConfig::updateOrCreate(
        ['key' => 'notification_cycle_threshold'],
        ['value' => '999', 'updated_at' => now()],
    );
});

/**
 * Helper: Create a site that was previously down and notified.
 */
function createRecoveryTestSite(SiteStatus $currentStatus, int $consecutiveDownCount, bool $notificationSent): Site
{
    static $counter = 0;
    $counter++;

    return Site::create([
        'name' => "Recovery Test Site {$counter}",
        'category_id' => Category::first()->id,
        'base_url' => "https://recovery-test-{$counter}-" . time() . '-' . mt_rand(1000, 9999) . ".example.com",
        'responsible_person_id' => ITStaff::first()->id,
        'status' => $currentStatus,
        'consecutive_down_count' => $consecutiveDownCount,
        'notification_sent' => $notificationSent,
        'notification_cycle_counter' => $notificationSent ? 1 : 0,
        'avg_response_time' => 0,
        'first_down_at' => $consecutiveDownCount > 0 ? now()->subMinutes($consecutiveDownCount * 10) : null,
    ]);
}

/**
 * Helper: Create a SiteCheckResult where all pages are up (results in "up" status).
 */
function createRecoveryUpResult(int $siteId): SiteCheckResult
{
    return new SiteCheckResult(
        siteId: $siteId,
        siteName: 'Recovery Test Site',
        pageResults: collect([
            new PageCheckResult(
                pageId: 1,
                siteId: $siteId,
                url: 'https://recovery-test.example.com/page1',
                httpCode: 200,
                responseTimeMs: 150.0,
                errorType: ErrorType::None,
            ),
            new PageCheckResult(
                pageId: 2,
                siteId: $siteId,
                url: 'https://recovery-test.example.com/page2',
                httpCode: 200,
                responseTimeMs: 120.0,
                errorType: ErrorType::None,
            ),
        ]),
    );
}

/**
 * Helper: Create a SiteCheckResult where all pages are down (results in "totally_down" status).
 */
function createRecoveryTotallyDownResult(int $siteId): SiteCheckResult
{
    return new SiteCheckResult(
        siteId: $siteId,
        siteName: 'Recovery Test Site',
        pageResults: collect([
            new PageCheckResult(
                pageId: 1,
                siteId: $siteId,
                url: 'https://recovery-test.example.com/page1',
                httpCode: 0,
                responseTimeMs: 0,
                errorType: ErrorType::ConnectionFailure,
            ),
            new PageCheckResult(
                pageId: 2,
                siteId: $siteId,
                url: 'https://recovery-test.example.com/page2',
                httpCode: 0,
                responseTimeMs: 0,
                errorType: ErrorType::Timeout,
            ),
        ]),
    );
}

/**
 * Helper: Create a SiteCheckResult where some pages are up, some down (results in "partially_down" status).
 */
function createRecoveryPartiallyDownResult(int $siteId): SiteCheckResult
{
    return new SiteCheckResult(
        siteId: $siteId,
        siteName: 'Recovery Test Site',
        pageResults: collect([
            new PageCheckResult(
                pageId: 1,
                siteId: $siteId,
                url: 'https://recovery-test.example.com/page1',
                httpCode: 200,
                responseTimeMs: 150.0,
                errorType: ErrorType::None,
            ),
            new PageCheckResult(
                pageId: 2,
                siteId: $siteId,
                url: 'https://recovery-test.example.com/page2',
                httpCode: 0,
                responseTimeMs: 0,
                errorType: ErrorType::ConnectionFailure,
            ),
        ]),
    );
}

describe('Property 9: Recovery notification conditions', function () {

    it('sends recovery notification when transitioning from down to up with notification_sent=true (Requirement 14.1)', function () {
        // **Validates: Requirements 14.1**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random previous down status (totally_down or partially_down)
                $previousStatus = $faker->randomElement([SiteStatus::TotallyDown, SiteStatus::PartiallyDown]);
                // consecutive_down_count at or above threshold (notification was already sent)
                $consecutiveDownCount = $faker->numberBetween(3, 30);

                return [
                    'previousStatus' => $previousStatus,
                    'consecutiveDownCount' => $consecutiveDownCount,
                ];
            },
            assertion: function ($data) {
                $site = createRecoveryTestSite(
                    $data['previousStatus'],
                    $data['consecutiveDownCount'],
                    notificationSent: true,
                );

                // Expect recovery notification to be sent
                $this->telegramMock->shouldReceive('sendMessage')
                    ->atLeast()->once()
                    ->andReturn(true);

                $siteResult = createRecoveryUpResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                // Site should be reset to up state
                expect($site->status)->toBe(SiteStatus::Up);
                expect($site->consecutive_down_count)->toBe(0);
                expect($site->notification_sent)->toBeFalse();
                expect($site->notification_cycle_counter)->toBe(0);
                // Recovery notification log should exist
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'recovery')->exists())->toBeTrue();

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

    it('does NOT send recovery notification when site recovered before reaching threshold (Requirement 14.3)', function () {
        // **Validates: Requirements 14.3**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random previous down status
                $previousStatus = $faker->randomElement([SiteStatus::TotallyDown, SiteStatus::PartiallyDown]);
                // consecutive_down_count below threshold (1 or 2) - notification was never sent
                $consecutiveDownCount = $faker->numberBetween(1, 2);

                return [
                    'previousStatus' => $previousStatus,
                    'consecutiveDownCount' => $consecutiveDownCount,
                ];
            },
            assertion: function ($data) {
                $site = createRecoveryTestSite(
                    $data['previousStatus'],
                    $data['consecutiveDownCount'],
                    notificationSent: false, // Below threshold, so no notification was ever sent
                );

                // No recovery notification should be sent
                $this->telegramMock->shouldNotReceive('sendMessage');

                $siteResult = createRecoveryUpResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                // Site should be reset to up state
                expect($site->status)->toBe(SiteStatus::Up);
                expect($site->consecutive_down_count)->toBe(0);
                expect($site->notification_sent)->toBeFalse();
                // No recovery notification log should exist
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'recovery')->exists())->toBeFalse();

                // Cleanup
                $site->delete();
                Mockery::close();
                $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                $this->service = new NotificationService($this->telegramMock);
            },
            iterations: 100
        );
    });

    it('does NOT send recovery notification when transitioning from totally_down to partially_down (Requirement 14.5)', function () {
        // **Validates: Requirements 14.5**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Site is totally_down with notification sent, above threshold
                $consecutiveDownCount = $faker->numberBetween(3, 30);

                return ['consecutiveDownCount' => $consecutiveDownCount];
            },
            assertion: function ($data) {
                $site = createRecoveryTestSite(
                    SiteStatus::TotallyDown,
                    $data['consecutiveDownCount'],
                    notificationSent: true,
                );

                // A status_change notification may be sent (Property 8),
                // but NOT a recovery notification
                $this->telegramMock->shouldReceive('sendMessage')
                    ->zeroOrMoreTimes()
                    ->andReturn(true);

                // Transition to partially_down (not up!)
                $siteResult = createRecoveryPartiallyDownResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));

                $site->refresh();
                // Site should still be in a down state (partially_down), not recovered
                expect($site->status)->toBe(SiteStatus::PartiallyDown);
                // notification_sent should remain true (still in outage)
                expect($site->notification_sent)->toBeTrue();
                // consecutive_down_count should have incremented (not reset)
                expect($site->consecutive_down_count)->toBe($data['consecutiveDownCount'] + 1);
                // No recovery notification log should exist
                expect(NotificationLog::where('site_id', $site->id)->where('type', 'recovery')->exists())->toBeFalse();

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

    it('simulates random outage/recovery sequences verifying recovery notification logic (Requirements 14.1, 14.3, 14.5)', function () {
        // **Validates: Requirements 14.1, 14.3, 14.5**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate a random number of down cycles before recovery (1 to 8)
                $downCyclesBeforeRecovery = $faker->numberBetween(1, 8);
                // Random down status for each cycle
                $downStatuses = [];
                for ($i = 0; $i < $downCyclesBeforeRecovery; $i++) {
                    $downStatuses[] = $faker->randomElement([SiteStatus::TotallyDown, SiteStatus::PartiallyDown]);
                }

                return [
                    'downCyclesBeforeRecovery' => $downCyclesBeforeRecovery,
                    'downStatuses' => $downStatuses,
                ];
            },
            assertion: function ($data) {
                $downCycles = $data['downCyclesBeforeRecovery'];
                $downStatuses = $data['downStatuses'];
                $falsePositiveThreshold = 3;

                // Start with a fresh site (up, count=0)
                $site = createRecoveryTestSite(SiteStatus::Up, 0, notificationSent: false);

                // Run down cycles
                for ($cycle = 0; $cycle < $downCycles; $cycle++) {
                    // Allow any notification behavior during down cycles
                    $this->telegramMock->shouldReceive('sendMessage')
                        ->zeroOrMoreTimes()
                        ->andReturn(true);

                    // Create the appropriate down result
                    if ($downStatuses[$cycle] === SiteStatus::TotallyDown) {
                        $siteResult = createRecoveryTotallyDownResult($site->id);
                    } else {
                        $siteResult = createRecoveryPartiallyDownResult($site->id);
                    }

                    $this->service->evaluateAndNotify(collect([$siteResult]));
                    $site->refresh();

                    // Reset mock for next cycle
                    Mockery::close();
                    $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
                    $this->service = new NotificationService($this->telegramMock);
                }

                // Capture state before recovery
                $notificationWasSent = $site->notification_sent;

                // Now trigger recovery (transition to up)
                if ($notificationWasSent) {
                    // Recovery notification SHOULD be sent (Req 14.1)
                    $this->telegramMock->shouldReceive('sendMessage')
                        ->atLeast()->once()
                        ->andReturn(true);
                } else {
                    // No recovery notification (Req 14.3 - recovered before threshold)
                    $this->telegramMock->shouldNotReceive('sendMessage');
                }

                $siteResult = createRecoveryUpResult($site->id);
                $this->service->evaluateAndNotify(collect([$siteResult]));
                $site->refresh();

                // After recovery, site should be in up state with counters reset
                expect($site->status)->toBe(SiteStatus::Up);
                expect($site->consecutive_down_count)->toBe(0);
                expect($site->notification_sent)->toBeFalse();
                expect($site->notification_cycle_counter)->toBe(0);

                // Verify recovery notification log matches expected behavior
                $recoveryLogExists = NotificationLog::where('site_id', $site->id)
                    ->where('type', 'recovery')
                    ->exists();

                if ($notificationWasSent) {
                    // Recovery notification should have been sent
                    expect($recoveryLogExists)->toBeTrue();
                } else {
                    // No recovery notification should have been sent
                    expect($recoveryLogExists)->toBeFalse();
                }

                // Key invariant: recovery notification only if down notification was previously sent
                // This is verified by checking notification_sent before recovery
                if ($downCycles < $falsePositiveThreshold) {
                    expect($recoveryLogExists)->toBeFalse();
                }

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
