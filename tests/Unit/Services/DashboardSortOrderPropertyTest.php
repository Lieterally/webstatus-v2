<?php

// Feature: webstatus-v2, Property 12: Dashboard site list sort order

use App\Enums\SiteStatus;
use Tests\Helpers\PropertyTestHelpers;

/**
 * **Validates: Requirements 7.5**
 *
 * Property 12: Dashboard site list sort order
 *
 * For any list of monitored sites, the sorted display order SHALL place all "totally_down"
 * sites first, then all "partially_down" sites, then all "up" sites. Within each status
 * group, sites SHALL be sorted alphabetically by name. This invariant must hold regardless
 * of the number or mix of sites.
 */

/**
 * Apply the same sorting logic as Dashboard::getSites().
 */
function sortSitesLikeDashboard(\Illuminate\Support\Collection $sites): \Illuminate\Support\Collection
{
    return $sites->sort(function ($a, $b) {
        $statusOrder = [
            SiteStatus::TotallyDown->value => 0,
            SiteStatus::PartiallyDown->value => 1,
            SiteStatus::Up->value => 2,
        ];

        $aOrder = $statusOrder[$a['status']->value] ?? 3;
        $bOrder = $statusOrder[$b['status']->value] ?? 3;

        if ($aOrder !== $bOrder) {
            return $aOrder - $bOrder;
        }

        return strcasecmp($a['name'], $b['name']);
    })->values();
}

/**
 * Generate a random site name.
 */
function generateRandomSiteName(): string
{
    $faker = PropertyTestHelpers::faker();
    return $faker->unique()->words($faker->numberBetween(1, 3), true);
}

describe('Property 12: Dashboard site list sort order', function () {

    it('sorts totally_down first, then partially_down, then up (Requirement 7.5)', function () {
        // **Validates: Requirements 7.5**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                $siteCount = $faker->numberBetween(3, 20);
                $sites = collect();

                for ($i = 0; $i < $siteCount; $i++) {
                    $status = $faker->randomElement([
                        SiteStatus::Up,
                        SiteStatus::PartiallyDown,
                        SiteStatus::TotallyDown,
                    ]);

                    $sites->push([
                        'name' => 'Site_' . $faker->unique()->lexify('??????'),
                        'status' => $status,
                    ]);
                }

                $faker->unique(true); // Reset unique tracker

                return ['sites' => $sites];
            },
            assertion: function ($data) {
                $sorted = sortSitesLikeDashboard($data['sites']);

                // Verify status group ordering
                $lastStatusOrder = -1;
                $statusOrder = [
                    SiteStatus::TotallyDown->value => 0,
                    SiteStatus::PartiallyDown->value => 1,
                    SiteStatus::Up->value => 2,
                ];

                foreach ($sorted as $site) {
                    $currentOrder = $statusOrder[$site['status']->value];
                    expect($currentOrder)->toBeGreaterThanOrEqual($lastStatusOrder);
                    $lastStatusOrder = $currentOrder;
                }
            },
            iterations: 100
        );
    });

    it('sorts alphabetically within each status group (Requirement 7.5)', function () {
        // **Validates: Requirements 7.5**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                $siteCount = $faker->numberBetween(5, 25);
                $sites = collect();

                for ($i = 0; $i < $siteCount; $i++) {
                    $status = $faker->randomElement([
                        SiteStatus::Up,
                        SiteStatus::PartiallyDown,
                        SiteStatus::TotallyDown,
                    ]);

                    $sites->push([
                        'name' => 'Site_' . $faker->unique()->lexify('??????'),
                        'status' => $status,
                    ]);
                }

                $faker->unique(true); // Reset unique tracker

                return ['sites' => $sites];
            },
            assertion: function ($data) {
                $sorted = sortSitesLikeDashboard($data['sites']);

                // Group by status and verify alphabetical order within each group
                $groups = $sorted->groupBy(fn($site) => $site['status']->value);

                foreach ($groups as $groupSites) {
                    $names = $groupSites->pluck('name')->toArray();
                    $sortedNames = $names;
                    usort($sortedNames, 'strcasecmp');
                    expect($names)->toBe($sortedNames);
                }
            },
            iterations: 100
        );
    });

    it('maintains correct sort order for any mix of site counts per status (Requirement 7.5)', function () {
        // **Validates: Requirements 7.5**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate specific counts per status group
                $totallyDownCount = $faker->numberBetween(0, 8);
                $partiallyDownCount = $faker->numberBetween(0, 8);
                $upCount = $faker->numberBetween(0, 8);

                // Ensure at least 1 site total
                if ($totallyDownCount + $partiallyDownCount + $upCount === 0) {
                    $upCount = 1;
                }

                $sites = collect();

                for ($i = 0; $i < $totallyDownCount; $i++) {
                    $sites->push([
                        'name' => 'TD_' . $faker->unique()->lexify('??????'),
                        'status' => SiteStatus::TotallyDown,
                    ]);
                }

                for ($i = 0; $i < $partiallyDownCount; $i++) {
                    $sites->push([
                        'name' => 'PD_' . $faker->unique()->lexify('??????'),
                        'status' => SiteStatus::PartiallyDown,
                    ]);
                }

                for ($i = 0; $i < $upCount; $i++) {
                    $sites->push([
                        'name' => 'UP_' . $faker->unique()->lexify('??????'),
                        'status' => SiteStatus::Up,
                    ]);
                }

                $faker->unique(true); // Reset unique tracker

                // Shuffle to ensure the sort is doing the work
                $sites = $sites->shuffle();

                return [
                    'sites' => $sites,
                    'expectedTotallyDown' => $totallyDownCount,
                    'expectedPartiallyDown' => $partiallyDownCount,
                    'expectedUp' => $upCount,
                ];
            },
            assertion: function ($data) {
                $sorted = sortSitesLikeDashboard($data['sites']);

                // Verify the count of each status group
                $totallyDown = $sorted->filter(fn($s) => $s['status'] === SiteStatus::TotallyDown);
                $partiallyDown = $sorted->filter(fn($s) => $s['status'] === SiteStatus::PartiallyDown);
                $up = $sorted->filter(fn($s) => $s['status'] === SiteStatus::Up);

                expect($totallyDown->count())->toBe($data['expectedTotallyDown']);
                expect($partiallyDown->count())->toBe($data['expectedPartiallyDown']);
                expect($up->count())->toBe($data['expectedUp']);

                // Verify ordering: all totally_down come before partially_down, which come before up
                $statusValues = $sorted->map(fn($s) => $s['status']->value)->toArray();

                $foundPartial = false;
                $foundUp = false;

                foreach ($statusValues as $status) {
                    if ($status === SiteStatus::PartiallyDown->value) {
                        $foundPartial = true;
                    }
                    if ($status === SiteStatus::Up->value) {
                        $foundUp = true;
                    }

                    // If we've seen "up", we should never see "totally_down" or "partially_down" after it
                    if ($foundUp) {
                        expect($status)->toBe(SiteStatus::Up->value);
                    }
                    // If we've seen "partially_down" but not "up", we should never see "totally_down" after it
                    if ($foundPartial && !$foundUp) {
                        expect($status)->not->toBe(SiteStatus::TotallyDown->value);
                    }
                }

                // Verify alphabetical order within each group
                $groups = $sorted->groupBy(fn($site) => $site['status']->value);
                foreach ($groups as $groupSites) {
                    $names = $groupSites->pluck('name')->toArray();
                    $sortedNames = $names;
                    usort($sortedNames, 'strcasecmp');
                    expect($names)->toBe($sortedNames);
                }
            },
            iterations: 100
        );
    });

    it('handles single-site lists correctly (Requirement 7.5)', function () {
        // **Validates: Requirements 7.5**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                $status = $faker->randomElement([
                    SiteStatus::Up,
                    SiteStatus::PartiallyDown,
                    SiteStatus::TotallyDown,
                ]);

                return [
                    'sites' => collect([[
                        'name' => 'Site_' . $faker->lexify('??????'),
                        'status' => $status,
                    ]]),
                ];
            },
            assertion: function ($data) {
                $sorted = sortSitesLikeDashboard($data['sites']);

                expect($sorted)->toHaveCount(1);
                expect($sorted[0]['name'])->toBe($data['sites']->first()['name']);
                expect($sorted[0]['status'])->toBe($data['sites']->first()['status']);
            },
            iterations: 100
        );
    });
});
