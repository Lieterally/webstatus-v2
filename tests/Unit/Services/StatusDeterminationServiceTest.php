<?php

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Services\StatusDeterminationService;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->service = new StatusDeterminationService();
    $this->site = new Site();
});

describe('determineStatus', function () {
    it('returns "up" when no pages are defined (empty collection)', function () {
        $result = $this->service->determineStatus($this->site, collect([]));

        expect($result)->toBe(SiteStatus::Up);
    });

    it('returns "up" when all pages return 2xx status codes', function () {
        $pageResults = collect([
            ['http_code' => 200, 'response_time_ms' => 150.0],
            ['http_code' => 201, 'response_time_ms' => 200.0],
            ['http_code' => 204, 'response_time_ms' => 100.0],
        ]);

        $result = $this->service->determineStatus($this->site, $pageResults);

        expect($result)->toBe(SiteStatus::Up);
    });

    it('returns "up" when all pages return 3xx status codes', function () {
        $pageResults = collect([
            ['http_code' => 301, 'response_time_ms' => 120.0],
            ['http_code' => 302, 'response_time_ms' => 130.0],
            ['http_code' => 304, 'response_time_ms' => 110.0],
        ]);

        $result = $this->service->determineStatus($this->site, $pageResults);

        expect($result)->toBe(SiteStatus::Up);
    });

    it('returns "up" when all pages return a mix of 2xx and 3xx codes', function () {
        $pageResults = collect([
            ['http_code' => 200, 'response_time_ms' => 150.0],
            ['http_code' => 301, 'response_time_ms' => 120.0],
            ['http_code' => 204, 'response_time_ms' => 100.0],
        ]);

        $result = $this->service->determineStatus($this->site, $pageResults);

        expect($result)->toBe(SiteStatus::Up);
    });

    it('returns "totally_down" when all pages return 4xx/5xx codes', function () {
        $pageResults = collect([
            ['http_code' => 500, 'response_time_ms' => 0.0],
            ['http_code' => 404, 'response_time_ms' => 0.0],
            ['http_code' => 503, 'response_time_ms' => 0.0],
        ]);

        $result = $this->service->determineStatus($this->site, $pageResults);

        expect($result)->toBe(SiteStatus::TotallyDown);
    });

    it('returns "totally_down" when all pages are unreachable (http_code = 0)', function () {
        $pageResults = collect([
            ['http_code' => 0, 'response_time_ms' => 0.0],
            ['http_code' => 0, 'response_time_ms' => 0.0],
        ]);

        $result = $this->service->determineStatus($this->site, $pageResults);

        expect($result)->toBe(SiteStatus::TotallyDown);
    });

    it('returns "partially_down" when some pages succeed and some fail', function () {
        $pageResults = collect([
            ['http_code' => 200, 'response_time_ms' => 150.0],
            ['http_code' => 500, 'response_time_ms' => 0.0],
            ['http_code' => 301, 'response_time_ms' => 120.0],
        ]);

        $result = $this->service->determineStatus($this->site, $pageResults);

        expect($result)->toBe(SiteStatus::PartiallyDown);
    });

    it('returns "partially_down" when some pages are reachable and some are unreachable', function () {
        $pageResults = collect([
            ['http_code' => 200, 'response_time_ms' => 150.0],
            ['http_code' => 0, 'response_time_ms' => 0.0],
        ]);

        $result = $this->service->determineStatus($this->site, $pageResults);

        expect($result)->toBe(SiteStatus::PartiallyDown);
    });

    it('returns "totally_down" for a single page with failure code', function () {
        $pageResults = collect([
            ['http_code' => 500, 'response_time_ms' => 0.0],
        ]);

        $result = $this->service->determineStatus($this->site, $pageResults);

        expect($result)->toBe(SiteStatus::TotallyDown);
    });

    it('returns "up" for a single page with success code', function () {
        $pageResults = collect([
            ['http_code' => 200, 'response_time_ms' => 100.0],
        ]);

        $result = $this->service->determineStatus($this->site, $pageResults);

        expect($result)->toBe(SiteStatus::Up);
    });
});

describe('calculateAverageResponseTime', function () {
    it('returns 0 when page results are empty', function () {
        $result = $this->service->calculateAverageResponseTime(collect([]));

        expect($result)->toBe(0.0);
    });

    it('returns 0 when all pages are unreachable', function () {
        $pageResults = collect([
            ['http_code' => 0, 'response_time_ms' => 0.0],
            ['http_code' => 500, 'response_time_ms' => 0.0],
            ['http_code' => 404, 'response_time_ms' => 0.0],
        ]);

        $result = $this->service->calculateAverageResponseTime($pageResults);

        expect($result)->toBe(0.0);
    });

    it('calculates average for all reachable pages', function () {
        $pageResults = collect([
            ['http_code' => 200, 'response_time_ms' => 100.0],
            ['http_code' => 200, 'response_time_ms' => 200.0],
            ['http_code' => 200, 'response_time_ms' => 300.0],
        ]);

        $result = $this->service->calculateAverageResponseTime($pageResults);

        expect($result)->toBe(200.0);
    });

    it('excludes unreachable pages from the average calculation', function () {
        $pageResults = collect([
            ['http_code' => 200, 'response_time_ms' => 100.0],
            ['http_code' => 0, 'response_time_ms' => 0.0],
            ['http_code' => 200, 'response_time_ms' => 300.0],
        ]);

        $result = $this->service->calculateAverageResponseTime($pageResults);

        // Only reachable pages: (100 + 300) / 2 = 200
        expect($result)->toBe(200.0);
    });

    it('excludes pages with 4xx/5xx codes from the average', function () {
        $pageResults = collect([
            ['http_code' => 200, 'response_time_ms' => 150.0],
            ['http_code' => 500, 'response_time_ms' => 50.0],
            ['http_code' => 301, 'response_time_ms' => 250.0],
        ]);

        $result = $this->service->calculateAverageResponseTime($pageResults);

        // Only reachable (2xx/3xx): (150 + 250) / 2 = 200
        expect($result)->toBe(200.0);
    });

    it('returns the single response time when only one page is reachable', function () {
        $pageResults = collect([
            ['http_code' => 0, 'response_time_ms' => 0.0],
            ['http_code' => 200, 'response_time_ms' => 450.0],
            ['http_code' => 500, 'response_time_ms' => 0.0],
        ]);

        $result = $this->service->calculateAverageResponseTime($pageResults);

        expect($result)->toBe(450.0);
    });

    it('handles pages with 3xx codes as reachable', function () {
        $pageResults = collect([
            ['http_code' => 301, 'response_time_ms' => 100.0],
            ['http_code' => 302, 'response_time_ms' => 200.0],
        ]);

        $result = $this->service->calculateAverageResponseTime($pageResults);

        expect($result)->toBe(150.0);
    });
});
