<?php

// Feature: webstatus-v2, Property 5: Average response time calculation

use App\Services\StatusDeterminationService;
use Tests\Helpers\PropertyTestHelpers;

/**
 * **Validates: Requirements 2.5, 2.6**
 *
 * Property 5: Average response time calculation
 *
 * For any set of page check results for a site, the calculated average response time
 * SHALL equal the sum of response times of all reachable pages divided by the count
 * of reachable pages. If all pages are unreachable (count of reachable pages is 0),
 * the average SHALL be exactly 0.
 */

beforeEach(function () {
    $this->service = new StatusDeterminationService();
});

describe('Property 5: Average response time calculation', function () {

    it('returns 0 when all pages are unreachable (Requirement 2.6)', function () {
        // **Validates: Requirements 2.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                $pageCount = $faker->numberBetween(1, 10);
                $pageResults = collect();

                // Generate only unreachable pages (non-2xx/3xx codes)
                $failureCodes = [0, 400, 401, 403, 404, 500, 502, 503, 504];

                for ($i = 0; $i < $pageCount; $i++) {
                    $pageResults->push([
                        'http_code' => $faker->randomElement($failureCodes),
                        'response_time_ms' => $faker->randomFloat(2, 0, 15000),
                    ]);
                }

                return ['pageResults' => $pageResults];
            },
            assertion: function ($data) {
                $result = $this->service->calculateAverageResponseTime($data['pageResults']);
                expect($result)->toBe(0.0);
            },
            iterations: 100
        );
    });

    it('returns 0 when page results collection is empty (Requirement 2.6)', function () {
        // **Validates: Requirements 2.6**
        forAll(
            generator: function () {
                return ['pageResults' => collect([])];
            },
            assertion: function ($data) {
                $result = $this->service->calculateAverageResponseTime($data['pageResults']);
                expect($result)->toBe(0.0);
            },
            iterations: 100
        );
    });

    it('correctly calculates average for reachable pages (Requirement 2.5)', function () {
        // **Validates: Requirements 2.5**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                $pageCount = $faker->numberBetween(1, 10);
                $pageResults = collect();

                // Generate only reachable pages (2xx/3xx codes)
                for ($i = 0; $i < $pageCount; $i++) {
                    $pageResults->push([
                        'http_code' => PropertyTestHelpers::randomHttpCode(successful: true),
                        'response_time_ms' => $faker->randomFloat(2, 10, 15000),
                    ]);
                }

                return ['pageResults' => $pageResults];
            },
            assertion: function ($data) {
                $pageResults = $data['pageResults'];
                $result = $this->service->calculateAverageResponseTime($pageResults);

                // Calculate expected average manually
                $sum = $pageResults->sum('response_time_ms');
                $count = $pageResults->count();
                $expectedAverage = $sum / $count;

                expect(abs($result - $expectedAverage))->toBeLessThan(0.0001);
            },
            iterations: 100
        );
    });

    it('calculates average using only reachable pages in mixed results (Requirements 2.5, 2.6)', function () {
        // **Validates: Requirements 2.5, 2.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Ensure at least 1 reachable and 1 unreachable page
                $reachableCount = $faker->numberBetween(1, 5);
                $unreachableCount = $faker->numberBetween(1, 5);
                $pageResults = collect();

                $failureCodes = [0, 400, 401, 403, 404, 500, 502, 503, 504];

                // Add reachable pages
                for ($i = 0; $i < $reachableCount; $i++) {
                    $pageResults->push([
                        'http_code' => PropertyTestHelpers::randomHttpCode(successful: true),
                        'response_time_ms' => $faker->randomFloat(2, 10, 15000),
                    ]);
                }

                // Add unreachable pages
                for ($i = 0; $i < $unreachableCount; $i++) {
                    $pageResults->push([
                        'http_code' => $faker->randomElement($failureCodes),
                        'response_time_ms' => $faker->randomFloat(2, 0, 15000),
                    ]);
                }

                // Shuffle to randomize order
                $pageResults = $pageResults->shuffle();

                return ['pageResults' => $pageResults];
            },
            assertion: function ($data) {
                $pageResults = $data['pageResults'];
                $result = $this->service->calculateAverageResponseTime($pageResults);

                // Manually calculate expected: sum of reachable response times / count of reachable
                $reachablePages = $pageResults->filter(function ($r) {
                    return PropertyTestHelpers::isSuccessfulCode($r['http_code']);
                });

                $expectedSum = $reachablePages->sum('response_time_ms');
                $expectedCount = $reachablePages->count();
                $expectedAverage = $expectedCount > 0 ? $expectedSum / $expectedCount : 0.0;

                expect(abs($result - $expectedAverage))->toBeLessThan(0.0001);
            },
            iterations: 100
        );
    });

    it('satisfies property: average = sum(reachable response times) / count(reachable) for any input', function () {
        // **Validates: Requirements 2.5, 2.6**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                $pageCount = $faker->numberBetween(0, 10);
                $pageResults = collect();

                for ($i = 0; $i < $pageCount; $i++) {
                    $pageResults->push([
                        'http_code' => PropertyTestHelpers::randomHttpCode(successful: false),
                        'response_time_ms' => $faker->randomFloat(2, 0, 15000),
                    ]);
                }

                return ['pageResults' => $pageResults];
            },
            assertion: function ($data) {
                $pageResults = $data['pageResults'];
                $result = $this->service->calculateAverageResponseTime($pageResults);

                // Manually calculate expected average
                $reachablePages = $pageResults->filter(function ($r) {
                    return PropertyTestHelpers::isSuccessfulCode($r['http_code']);
                });

                if ($reachablePages->isEmpty()) {
                    expect($result)->toBe(0.0);
                } else {
                    $expectedSum = $reachablePages->sum('response_time_ms');
                    $expectedCount = $reachablePages->count();
                    $expectedAverage = $expectedSum / $expectedCount;

                    expect(abs($result - $expectedAverage))->toBeLessThan(0.0001);
                }
            },
            iterations: 100
        );
    });
});
