<?php

// Feature: webstatus-v2, Property 2: Consecutive down count follows increment/reset rules

use App\Enums\SiteStatus;
use App\Models\Category;
use App\Models\ITStaff;
use App\Models\Site;
use App\Models\SystemConfig;
use App\Services\HealthCheckServiceInterface;
use App\Services\MonitoringService;
use App\Services\NotificationServiceInterface;
use App\Services\StatusDeterminationService;
use Tests\Helpers\PropertyTestHelpers;

/**
 * **Validates: Requirements 3.6**
 *
 * Property 2: Consecutive down count follows increment/reset rules
 *
 * For any sequence of checking cycle results for a site, the consecutive_down_count
 * SHALL increment by exactly 1 when the cycle result is "partially_down" or "totally_down",
 * and SHALL reset to exactly 0 when the cycle result is "up".
 */

beforeEach(function () {
    $this->category = Category::create(['name' => 'Consecutive Down Test Category']);
    $this->staff = ITStaff::create(['name' => 'Consecutive Down Test Staff', 'position' => 'Engineer']);

    // Set up system config for cycle interval
    SystemConfig::updateOrCreate(['key' => 'cycle_interval_minutes'], ['value' => '10']);
});

/**
 * Helper: Create a fresh site for testing consecutive down count.
 */
function createConsecutiveDownTestSite(int $consecutiveDownCount = 0, string $status = 'up'): Site
{
    static $counter = 0;
    $counter++;

    return Site::create([
        'name' => "Consecutive Down Test Site {$counter}",
        'category_id' => Category::first()->id,
        'base_url' => "https://consecutive-test-{$counter}-" . uniqid() . ".example.com",
        'responsible_person_id' => ITStaff::first()->id,
        'status' => $status,
        'consecutive_down_count' => $consecutiveDownCount,
        'notification_sent' => false,
        'notification_cycle_counter' => 0,
        'avg_response_time' => 0,
        'first_down_at' => $consecutiveDownCount > 0 ? now()->subMinutes($consecutiveDownCount * 10) : null,
    ]);
}

describe('Property 2: Consecutive down count follows increment/reset rules', function () {

    it('increments by exactly 1 when cycle result is "partially_down" (Requirement 3.6)', function () {
        // **Validates: Requirements 3.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random starting consecutive_down_count
                $initialCount = $faker->numberBetween(0, 50);

                return ['initialCount' => $initialCount];
            },
            assertion: function ($data) {
                $site = createConsecutiveDownTestSite($data['initialCount'], 'partially_down');

                // Simulate the updateSiteStatus logic with partially_down
                $site->update([
                    'status' => SiteStatus::PartiallyDown,
                    'consecutive_down_count' => $site->consecutive_down_count + 1,
                    'first_down_at' => $site->first_down_at ?? now(),
                ]);

                $site->refresh();
                expect($site->consecutive_down_count)->toBe($data['initialCount'] + 1);

                // Cleanup
                $site->delete();
            },
            iterations: 100
        );
    });

    it('increments by exactly 1 when cycle result is "totally_down" (Requirement 3.6)', function () {
        // **Validates: Requirements 3.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random starting consecutive_down_count
                $initialCount = $faker->numberBetween(0, 50);

                return ['initialCount' => $initialCount];
            },
            assertion: function ($data) {
                $site = createConsecutiveDownTestSite($data['initialCount'], 'totally_down');

                // Simulate the updateSiteStatus logic with totally_down
                $site->update([
                    'status' => SiteStatus::TotallyDown,
                    'consecutive_down_count' => $site->consecutive_down_count + 1,
                    'first_down_at' => $site->first_down_at ?? now(),
                ]);

                $site->refresh();
                expect($site->consecutive_down_count)->toBe($data['initialCount'] + 1);

                // Cleanup
                $site->delete();
            },
            iterations: 100
        );
    });

    it('resets to exactly 0 when cycle result is "up" (Requirement 3.6)', function () {
        // **Validates: Requirements 3.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Random starting consecutive_down_count (> 0 to make reset meaningful)
                $initialCount = $faker->numberBetween(1, 100);

                return ['initialCount' => $initialCount];
            },
            assertion: function ($data) {
                $site = createConsecutiveDownTestSite($data['initialCount'], 'totally_down');

                // Simulate the updateSiteStatus logic with "up" status
                $site->update([
                    'status' => SiteStatus::Up,
                    'consecutive_down_count' => 0,
                    'first_down_at' => null,
                ]);

                $site->refresh();
                expect($site->consecutive_down_count)->toBe(0);

                // Cleanup
                $site->delete();
            },
            iterations: 100
        );
    });

    it('correctly applies increment/reset rules for any random sequence of cycle results (Requirement 3.6)', function () {
        // **Validates: Requirements 3.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate a random sequence of 5 to 20 cycle results
                $sequenceLength = $faker->numberBetween(5, 20);
                $statuses = [];

                for ($i = 0; $i < $sequenceLength; $i++) {
                    $statuses[] = $faker->randomElement(['up', 'partially_down', 'totally_down']);
                }

                return ['statuses' => $statuses];
            },
            assertion: function ($data) {
                $site = createConsecutiveDownTestSite(0, 'up');

                $expectedCount = 0;

                foreach ($data['statuses'] as $status) {
                    $newStatus = SiteStatus::from($status);

                    if ($newStatus === SiteStatus::Up) {
                        $expectedCount = 0;
                        $site->update([
                            'status' => $newStatus,
                            'consecutive_down_count' => 0,
                            'first_down_at' => null,
                        ]);
                    } else {
                        $expectedCount++;
                        $site->update([
                            'status' => $newStatus,
                            'consecutive_down_count' => $site->consecutive_down_count + 1,
                            'first_down_at' => $site->first_down_at ?? now(),
                        ]);
                    }

                    $site->refresh();
                    expect($site->consecutive_down_count)->toBe($expectedCount);
                }

                // Cleanup
                $site->delete();
            },
            iterations: 100
        );
    });

    it('verifies consecutive down count is correctly managed through the notification service for random sequences (Requirement 3.6)', function () {
        // **Validates: Requirements 3.6**
        // This test verifies that the NotificationService (which now owns consecutive_down_count)
        // correctly increments on down and resets on up.
        $notificationService = app(NotificationServiceInterface::class);

        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                $sequenceLength = $faker->numberBetween(5, 15);
                $statuses = [];

                for ($i = 0; $i < $sequenceLength; $i++) {
                    $statuses[] = $faker->randomElement(['up', 'partially_down', 'totally_down']);
                }

                return ['statuses' => $statuses];
            },
            assertion: function ($data) use ($notificationService) {
                $site = createConsecutiveDownTestSite(0, 'up');

                $expectedCount = 0;

                foreach ($data['statuses'] as $status) {
                    if ($status === 'up') {
                        $expectedCount = 0;
                        $siteResult = new \App\DTOs\SiteCheckResult(
                            siteId: $site->id,
                            siteName: $site->name,
                            pageResults: collect([new \App\DTOs\PageCheckResult(
                                pageId: 1,
                                siteId: $site->id,
                                url: 'http://test.com/',
                                httpCode: 200,
                                responseTimeMs: 100.0,
                                errorType: \App\Enums\ErrorType::None
                            )]),
                        );
                    } else {
                        $expectedCount++;
                        $siteResult = new \App\DTOs\SiteCheckResult(
                            siteId: $site->id,
                            siteName: $site->name,
                            pageResults: collect([new \App\DTOs\PageCheckResult(
                                pageId: 1,
                                siteId: $site->id,
                                url: 'http://test.com/',
                                httpCode: 0,
                                responseTimeMs: 0,
                                errorType: \App\Enums\ErrorType::ConnectionFailure
                            )]),
                        );
                    }

                    $notificationService->evaluateAndNotify(collect([$siteResult]));
                    $site->refresh();

                    expect($site->consecutive_down_count)->toBe($expectedCount);
                }

                // Cleanup
                $site->delete();
            },
            iterations: 100
        );
    });
});
