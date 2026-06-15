<?php

// Feature: webstatus-v2, Property 1: Site status determination is correct for any combination of page results

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Services\StatusDeterminationService;
use Tests\Helpers\PropertyTestHelpers;

/**
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
 *
 * Property 1: Site status determination is correct for any combination of page results
 *
 * For any site with N defined pages (N >= 0) and any combination of HTTP response results
 * per page, the determineStatus function SHALL return:
 * - "up" when all pages return 2xx or 3xx status codes
 * - "partially_down" when some but not all pages return non-2xx/3xx or are unreachable
 * - "totally_down" when all pages return non-2xx/3xx or are unreachable
 * - "up" when the site has zero defined pages
 */

beforeEach(function () {
    $this->service = new StatusDeterminationService();
    $this->site = new Site();
});

describe('Property 1: Site status determination is correct for any combination of page results', function () {

    it('returns "up" when site has zero defined pages (Requirement 3.4)', function () {
        // **Validates: Requirements 3.4**
        forAll(
            generator: function () {
                // Generate an empty collection (no pages)
                return ['pageResults' => collect([])];
            },
            assertion: function ($data) {
                $result = $this->service->determineStatus($this->site, $data['pageResults']);
                expect($result)->toBe(SiteStatus::Up);
            },
            iterations: 100
        );
    });

    it('returns "up" when all pages return 2xx or 3xx (Requirement 3.1)', function () {
        // **Validates: Requirements 3.1**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                $pageCount = $faker->numberBetween(1, 10);
                $pageResults = collect();

                for ($i = 0; $i < $pageCount; $i++) {
                    $pageResults->push([
                        'http_code' => PropertyTestHelpers::randomHttpCode(successful: true),
                        'response_time_ms' => PropertyTestHelpers::randomResponseTime(),
                    ]);
                }

                return ['pageResults' => $pageResults];
            },
            assertion: function ($data) {
                $result = $this->service->determineStatus($this->site, $data['pageResults']);
                expect($result)->toBe(SiteStatus::Up);
            },
            iterations: 100
        );
    });

    it('returns "totally_down" when all pages return non-2xx/3xx or are unreachable (Requirement 3.3)', function () {
        // **Validates: Requirements 3.3**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                $pageCount = $faker->numberBetween(1, 10);
                $pageResults = collect();

                // Generate only failure codes: 4xx, 5xx, or 0 (unreachable)
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
                $result = $this->service->determineStatus($this->site, $data['pageResults']);
                expect($result)->toBe(SiteStatus::TotallyDown);
            },
            iterations: 100
        );
    });

    it('returns "partially_down" when some pages succeed and some fail (Requirement 3.2)', function () {
        // **Validates: Requirements 3.2**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Need at least 2 pages: at least 1 success and at least 1 failure
                $successCount = $faker->numberBetween(1, 5);
                $failureCount = $faker->numberBetween(1, 5);
                $pageResults = collect();

                $failureCodes = [0, 400, 401, 403, 404, 500, 502, 503, 504];

                // Add successful pages
                for ($i = 0; $i < $successCount; $i++) {
                    $pageResults->push([
                        'http_code' => PropertyTestHelpers::randomHttpCode(successful: true),
                        'response_time_ms' => PropertyTestHelpers::randomResponseTime(),
                    ]);
                }

                // Add failing pages
                for ($i = 0; $i < $failureCount; $i++) {
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
                $result = $this->service->determineStatus($this->site, $data['pageResults']);
                expect($result)->toBe(SiteStatus::PartiallyDown);
            },
            iterations: 100
        );
    });

    it('correctly classifies any random combination of page results', function () {
        // **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // 0 to 10 pages
                $pageCount = $faker->numberBetween(0, 10);
                $pageResults = collect();

                for ($i = 0; $i < $pageCount; $i++) {
                    $pageResults->push([
                        'http_code' => PropertyTestHelpers::randomHttpCode(successful: false),
                        'response_time_ms' => PropertyTestHelpers::randomResponseTime(),
                    ]);
                }

                return ['pageResults' => $pageResults, 'pageCount' => $pageCount];
            },
            assertion: function ($data) {
                $result = $this->service->determineStatus($this->site, $data['pageResults']);
                $pageResults = $data['pageResults'];

                if ($data['pageCount'] === 0) {
                    // No pages -> "up" (Requirement 3.4)
                    expect($result)->toBe(SiteStatus::Up);
                    return;
                }

                $totalPages = $pageResults->count();
                $successfulPages = $pageResults->filter(function ($r) {
                    return PropertyTestHelpers::isSuccessfulCode($r['http_code']);
                })->count();

                if ($successfulPages === $totalPages) {
                    // All successful -> "up" (Requirement 3.1)
                    expect($result)->toBe(SiteStatus::Up);
                } elseif ($successfulPages === 0) {
                    // All failed -> "totally_down" (Requirement 3.3)
                    expect($result)->toBe(SiteStatus::TotallyDown);
                } else {
                    // Mix of success and failure -> "partially_down" (Requirement 3.2)
                    expect($result)->toBe(SiteStatus::PartiallyDown);
                }
            },
            iterations: 100
        );
    });
});
