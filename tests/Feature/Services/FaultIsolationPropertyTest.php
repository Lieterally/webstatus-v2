<?php

// Feature: webstatus-v2, Property 14: Fault isolation during checking cycles

use App\DTOs\SiteCheckResult;
use App\Enums\ErrorType;
use App\Models\Page;
use App\Models\Site;
use App\Services\HealthCheckService;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\PropertyTestHelpers;

/**
 * **Validates: Requirements 1.8**
 *
 * Property 14: Fault isolation during checking cycles
 *
 * For any set of monitored sites where some HTTP checks fail (timeout, connection error,
 * DNS failure), all remaining sites in the cycle SHALL still be processed and their results
 * recorded. The number of processed sites SHALL always equal the total number of monitored sites.
 */

beforeEach(function () {
    $this->service = new HealthCheckService();
});

describe('Property 14: Fault isolation during checking cycles', function () {

    it('processes all sites regardless of individual failures (Requirement 1.8)', function () {
        // **Validates: Requirements 1.8**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();

                // Generate random number of sites (2 to 8)
                $siteCount = $faker->numberBetween(2, 8);
                $sites = collect();
                $failingDomains = [];
                $failureTypes = ['timeout', 'connection_failure', 'dns_failure'];

                // Decide which sites will fail (at least 1 must fail, at least 1 should succeed)
                $failCount = $faker->numberBetween(1, max(1, $siteCount - 1));
                $failIndexes = $faker->randomElements(range(0, $siteCount - 1), $failCount);

                for ($i = 0; $i < $siteCount; $i++) {
                    $domain = "site-{$i}-" . $faker->unique()->word() . '.example.com';
                    $site = new Site(['name' => "Site {$i}", 'base_url' => "https://{$domain}"]);
                    $site->id = $i + 1;

                    // Each site has 1-3 pages
                    $pageCount = $faker->numberBetween(1, 3);
                    $pages = collect();
                    for ($j = 0; $j < $pageCount; $j++) {
                        $page = new Page(['path' => '/' . $faker->slug(1)]);
                        $page->id = ($i * 10) + $j + 1;
                        $page->site_id = $site->id;
                        $pages->push($page);
                    }
                    $site->setRelation('pages', $pages);
                    $sites->push($site);

                    if (in_array($i, $failIndexes)) {
                        $failingDomains[$domain] = $faker->randomElement($failureTypes);
                    }
                }

                // Reset faker unique
                $faker->unique(true);

                return [
                    'sites' => $sites,
                    'siteCount' => $siteCount,
                    'failingDomains' => $failingDomains,
                ];
            },
            assertion: function ($data) {
                $sites = $data['sites'];
                $siteCount = $data['siteCount'];
                $failingDomains = $data['failingDomains'];

                // Set up HTTP fake to simulate failures for specific domains
                Http::fake(function ($request) use ($failingDomains) {
                    $url = $request->url();

                    foreach ($failingDomains as $domain => $failureType) {
                        if (str_contains($url, $domain)) {
                            $message = match ($failureType) {
                                'timeout' => "cURL error 28: Connection timed out after 10001 milliseconds",
                                'dns_failure' => "cURL error 6: Could not resolve host: {$domain}",
                                'connection_failure' => "cURL error 7: Failed to connect to {$domain} port 443: Connection refused",
                            };

                            throw new \GuzzleHttp\Exception\ConnectException(
                                $message,
                                new \GuzzleHttp\Psr7\Request('GET', $url)
                            );
                        }
                    }

                    // Non-failing sites return 200
                    return Http::response('OK', 200);
                });

                $results = $this->service->checkAllSites($sites);

                // Property: number of processed sites equals total number of monitored sites
                expect($results)->toHaveCount($siteCount);

                // Property: every site has a result recorded
                $resultSiteIds = $results->pluck('siteId')->sort()->values()->toArray();
                $expectedSiteIds = $sites->pluck('id')->sort()->values()->toArray();
                expect($resultSiteIds)->toBe($expectedSiteIds);

                // Property: every site has page results (even if they failed)
                foreach ($results as $siteResult) {
                    expect($siteResult)->toBeInstanceOf(SiteCheckResult::class);
                    $site = $sites->firstWhere('id', $siteResult->siteId);
                    $expectedPageCount = $site->pages->count();
                    expect($siteResult->pageResults)->toHaveCount($expectedPageCount);
                }
            },
            iterations: 100
        );
    });

    it('records results for every site even when majority of checks fail (Requirement 1.8)', function () {
        // **Validates: Requirements 1.8**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();

                // Generate 3-8 sites where the majority fail
                $siteCount = $faker->numberBetween(3, 8);
                $sites = collect();
                $failingDomains = [];

                // Make most sites fail (60-90%)
                $failCount = $faker->numberBetween(
                    (int) ceil($siteCount * 0.6),
                    $siteCount
                );
                $failIndexes = $faker->randomElements(range(0, $siteCount - 1), $failCount);

                for ($i = 0; $i < $siteCount; $i++) {
                    $domain = "majority-{$i}-" . $faker->unique()->word() . '.test';
                    $site = new Site(['name' => "Majority Fail Site {$i}", 'base_url' => "https://{$domain}"]);
                    $site->id = $i + 1;

                    // 1-3 pages per site
                    $pageCount = $faker->numberBetween(1, 3);
                    $pages = collect();
                    for ($j = 0; $j < $pageCount; $j++) {
                        $page = new Page(['path' => '/' . $faker->slug(1)]);
                        $page->id = ($i * 10) + $j + 1;
                        $page->site_id = $site->id;
                        $pages->push($page);
                    }
                    $site->setRelation('pages', $pages);
                    $sites->push($site);

                    if (in_array($i, $failIndexes)) {
                        $failType = $faker->randomElement(['timeout', 'dns_failure', 'connection_failure']);
                        $failingDomains[$domain] = $failType;
                    }
                }

                // Reset faker unique
                $faker->unique(true);

                return [
                    'sites' => $sites,
                    'siteCount' => $siteCount,
                    'failingDomains' => $failingDomains,
                ];
            },
            assertion: function ($data) {
                $sites = $data['sites'];
                $siteCount = $data['siteCount'];
                $failingDomains = $data['failingDomains'];

                Http::fake(function ($request) use ($failingDomains) {
                    $url = $request->url();

                    foreach ($failingDomains as $domain => $failType) {
                        if (str_contains($url, $domain)) {
                            $message = match ($failType) {
                                'timeout' => "cURL error 28: Operation timed out",
                                'dns_failure' => "cURL error 6: Could not resolve host: {$domain}",
                                'connection_failure' => "cURL error 7: Failed to connect to {$domain}: Connection refused",
                            };

                            throw new \GuzzleHttp\Exception\ConnectException(
                                $message,
                                new \GuzzleHttp\Psr7\Request('GET', $url)
                            );
                        }
                    }

                    return Http::response('OK', 200);
                });

                $results = $this->service->checkAllSites($sites);

                // CORE PROPERTY: all sites processed regardless of failures
                expect($results)->toHaveCount($siteCount);

                // Every site must have results recorded for all its pages
                foreach ($results as $siteResult) {
                    expect($siteResult)->toBeInstanceOf(SiteCheckResult::class);
                    $site = $sites->firstWhere('id', $siteResult->siteId);
                    $expectedPageCount = $site->pages->count();

                    // Pages results are always recorded
                    expect($siteResult->pageResults)->toHaveCount($expectedPageCount);

                    // Each page result has valid structure
                    foreach ($siteResult->pageResults as $pageResult) {
                        expect($pageResult->pageId)->toBeGreaterThan(0);
                        expect($pageResult->siteId)->toBe($siteResult->siteId);
                        expect($pageResult->responseTimeMs)->toBeGreaterThanOrEqual(0);
                        // Error type must be a valid ErrorType enum value
                        expect($pageResult->errorType)->toBeInstanceOf(ErrorType::class);
                    }
                }
            },
            iterations: 100
        );
    });

    it('total processed sites always equals total monitored sites for any failure combination (Requirement 1.8)', function () {
        // **Validates: Requirements 1.8**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();

                // Generate random site count (1 to 10)
                $siteCount = $faker->numberBetween(1, 10);
                $sites = collect();
                $failureConfig = [];

                $failureTypes = ['timeout', 'connection_failure', 'dns_failure'];

                // Randomly assign failure probability to each site (0% to 100%)
                for ($i = 0; $i < $siteCount; $i++) {
                    $domain = "srv-{$i}-" . $faker->unique()->word() . '.net';
                    $site = new Site(['name' => "Server {$i}", 'base_url' => "https://{$domain}"]);
                    $site->id = $i + 1;

                    // 1-4 pages per site
                    $pageCount = $faker->numberBetween(1, 4);
                    $pages = collect();
                    for ($j = 0; $j < $pageCount; $j++) {
                        $page = new Page(['path' => '/' . $faker->slug(1)]);
                        $page->id = ($i * 100) + $j + 1;
                        $page->site_id = $site->id;
                        $pages->push($page);
                    }
                    $site->setRelation('pages', $pages);
                    $sites->push($site);

                    // Each site has a random chance of failing
                    if ($faker->boolean(60)) {
                        $failureConfig[$domain] = $faker->randomElement($failureTypes);
                    }
                }

                // Reset faker unique
                $faker->unique(true);

                return [
                    'sites' => $sites,
                    'siteCount' => $siteCount,
                    'failureConfig' => $failureConfig,
                ];
            },
            assertion: function ($data) {
                $sites = $data['sites'];
                $siteCount = $data['siteCount'];
                $failureConfig = $data['failureConfig'];

                Http::fake(function ($request) use ($failureConfig) {
                    $url = $request->url();

                    foreach ($failureConfig as $domain => $failType) {
                        if (str_contains($url, $domain)) {
                            $message = match ($failType) {
                                'timeout' => "cURL error 28: Connection timed out after 10001 milliseconds",
                                'dns_failure' => "cURL error 6: Could not resolve host: {$domain}",
                                'connection_failure' => "cURL error 7: Failed to connect to {$domain} port 443: Connection refused",
                            };

                            throw new \GuzzleHttp\Exception\ConnectException(
                                $message,
                                new \GuzzleHttp\Psr7\Request('GET', $url)
                            );
                        }
                    }

                    return Http::response('OK', 200);
                });

                $results = $this->service->checkAllSites($sites);

                // CORE PROPERTY: processed sites count always equals total monitored sites
                expect($results)->toHaveCount($siteCount);

                // Every site must have its pages checked (results recorded)
                $totalExpectedPages = $sites->sum(fn ($site) => $site->pages->count());
                $totalActualPages = $results->sum(fn ($r) => $r->pageResults->count());
                expect($totalActualPages)->toBe($totalExpectedPages);
            },
            iterations: 100
        );
    });
});
