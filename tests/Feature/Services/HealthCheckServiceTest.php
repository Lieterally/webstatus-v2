<?php

use App\DTOs\PageCheckResult;
use App\DTOs\SiteCheckResult;
use App\Enums\ErrorType;
use App\Models\Page;
use App\Models\Site;
use App\Services\HealthCheckService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new HealthCheckService();
});

it('returns empty page results for site with no pages', function () {
    $site = new Site(['name' => 'Test Site', 'base_url' => 'https://example.com']);
    $site->id = 1;
    $site->setRelation('pages', collect());

    $result = $this->service->checkAllSites(collect([$site]));

    expect($result)->toHaveCount(1);
    expect($result->first())->toBeInstanceOf(SiteCheckResult::class);
    expect($result->first()->siteId)->toBe(1);
    expect($result->first()->pageResults)->toBeEmpty();
});

it('records successful http response with status code and response time', function () {
    Http::fake([
        'https://example.com/' => Http::response('OK', 200),
    ]);

    $site = new Site(['name' => 'Test Site', 'base_url' => 'https://example.com']);
    $site->id = 1;

    $page = new Page(['path' => '/']);
    $page->id = 1;
    $page->site_id = 1;

    $site->setRelation('pages', collect([$page]));

    $result = $this->service->checkSite($site);

    expect($result)->toBeInstanceOf(SiteCheckResult::class);
    expect($result->siteId)->toBe(1);
    expect($result->pageResults)->toHaveCount(1);

    $pageResult = $result->pageResults->first();
    expect($pageResult)->toBeInstanceOf(PageCheckResult::class);
    expect($pageResult->httpCode)->toBe(200);
    expect($pageResult->errorType)->toBe(ErrorType::None);
    expect($pageResult->responseTimeMs)->toBeGreaterThanOrEqual(0);
});

it('handles dns resolution failure', function () {
    Http::fake(function ($request) {
        throw new \GuzzleHttp\Exception\ConnectException(
            'cURL error 6: Could not resolve host: nonexistent-domain-xyz.invalid (see https://curl.haxx.se/libcurl/c/libcurl-errors.html)',
            new \GuzzleHttp\Psr7\Request('GET', $request->url())
        );
    });

    $site = new Site(['name' => 'DNS Fail Site', 'base_url' => 'https://nonexistent-domain-xyz.invalid']);
    $site->id = 1;

    $page = new Page(['path' => '/']);
    $page->id = 1;
    $page->site_id = 1;

    $site->setRelation('pages', collect([$page]));

    $result = $this->service->checkSite($site);

    $pageResult = $result->pageResults->first();
    expect($pageResult->httpCode)->toBe(0);
    expect($pageResult->errorType)->toBe(ErrorType::DnsFailure);
    expect($pageResult->responseTimeMs)->toBe(0.0);
});

it('handles connection timeout failure', function () {
    Http::fake(function ($request) {
        throw new \GuzzleHttp\Exception\ConnectException(
            'cURL error 28: Connection timed out after 10001 milliseconds',
            new \GuzzleHttp\Psr7\Request('GET', $request->url())
        );
    });

    $site = new Site(['name' => 'Timeout Site', 'base_url' => 'https://slow-server.example.com']);
    $site->id = 1;

    $page = new Page(['path' => '/']);
    $page->id = 1;
    $page->site_id = 1;

    $site->setRelation('pages', collect([$page]));

    $result = $this->service->checkSite($site);

    $pageResult = $result->pageResults->first();
    expect($pageResult->httpCode)->toBe(0);
    expect($pageResult->errorType)->toBe(ErrorType::Timeout);
});

it('handles connection refused failure', function () {
    Http::fake(function ($request) {
        throw new \GuzzleHttp\Exception\ConnectException(
            'cURL error 7: Failed to connect to server.example.com port 443: Connection refused',
            new \GuzzleHttp\Psr7\Request('GET', $request->url())
        );
    });

    $site = new Site(['name' => 'Refused Site', 'base_url' => 'https://server.example.com']);
    $site->id = 1;

    $page = new Page(['path' => '/']);
    $page->id = 1;
    $page->site_id = 1;

    $site->setRelation('pages', collect([$page]));

    $result = $this->service->checkSite($site);

    $pageResult = $result->pageResults->first();
    expect($pageResult->httpCode)->toBe(0);
    expect($pageResult->errorType)->toBe(ErrorType::ConnectionFailure);
});

it('handles ssl error as connection failure', function () {
    Http::fake(function ($request) {
        throw new \GuzzleHttp\Exception\RequestException(
            'cURL error 60: SSL certificate problem: unable to get local issuer certificate',
            new \GuzzleHttp\Psr7\Request('GET', $request->url())
        );
    });

    $site = new Site(['name' => 'SSL Error Site', 'base_url' => 'https://bad-ssl.example.com']);
    $site->id = 1;

    $page = new Page(['path' => '/']);
    $page->id = 1;
    $page->site_id = 1;

    $site->setRelation('pages', collect([$page]));

    $result = $this->service->checkSite($site);

    $pageResult = $result->pageResults->first();
    expect($pageResult->httpCode)->toBe(0);
    expect($pageResult->errorType)->toBe(ErrorType::ConnectionFailure);
});

it('checks multiple pages of a single site concurrently', function () {
    Http::fake([
        'https://example.com/' => Http::response('Home', 200),
        'https://example.com/about' => Http::response('About', 200),
        'https://example.com/contact' => Http::response('Not Found', 404),
    ]);

    $site = new Site(['name' => 'Multi Page Site', 'base_url' => 'https://example.com']);
    $site->id = 1;

    $page1 = new Page(['path' => '/']);
    $page1->id = 1;
    $page1->site_id = 1;

    $page2 = new Page(['path' => '/about']);
    $page2->id = 2;
    $page2->site_id = 1;

    $page3 = new Page(['path' => '/contact']);
    $page3->id = 3;
    $page3->site_id = 1;

    $site->setRelation('pages', collect([$page1, $page2, $page3]));

    $result = $this->service->checkSite($site);

    expect($result->pageResults)->toHaveCount(3);

    $results = $result->pageResults->keyBy('pageId');
    expect($results[1]->httpCode)->toBe(200);
    expect($results[2]->httpCode)->toBe(200);
    expect($results[3]->httpCode)->toBe(404);
});

it('checks multiple sites concurrently', function () {
    Http::fake([
        'https://site1.com/' => Http::response('Site 1', 200),
        'https://site2.com/' => Http::response('Site 2', 503),
    ]);

    $site1 = new Site(['name' => 'Site 1', 'base_url' => 'https://site1.com']);
    $site1->id = 1;
    $page1 = new Page(['path' => '/']);
    $page1->id = 1;
    $page1->site_id = 1;
    $site1->setRelation('pages', collect([$page1]));

    $site2 = new Site(['name' => 'Site 2', 'base_url' => 'https://site2.com']);
    $site2->id = 2;
    $page2 = new Page(['path' => '/']);
    $page2->id = 2;
    $page2->site_id = 2;
    $site2->setRelation('pages', collect([$page2]));

    $results = $this->service->checkAllSites(collect([$site1, $site2]));

    expect($results)->toHaveCount(2);

    $siteResults = $results->keyBy('siteId');
    expect($siteResults[1]->pageResults->first()->httpCode)->toBe(200);
    expect($siteResults[2]->pageResults->first()->httpCode)->toBe(503);
});

it('continues processing remaining sites when one fails', function () {
    Http::fake(function ($request) {
        if (str_contains($request->url(), 'failing-site.com')) {
            throw new \GuzzleHttp\Exception\ConnectException(
                'cURL error 6: Could not resolve host: failing-site.com',
                new \GuzzleHttp\Psr7\Request('GET', $request->url())
            );
        }

        return Http::response('OK', 200);
    });

    $site1 = new Site(['name' => 'Failing Site', 'base_url' => 'https://failing-site.com']);
    $site1->id = 1;
    $page1 = new Page(['path' => '/']);
    $page1->id = 1;
    $page1->site_id = 1;
    $site1->setRelation('pages', collect([$page1]));

    $site2 = new Site(['name' => 'Working Site', 'base_url' => 'https://working-site.com']);
    $site2->id = 2;
    $page2 = new Page(['path' => '/']);
    $page2->id = 2;
    $page2->site_id = 2;
    $site2->setRelation('pages', collect([$page2]));

    $results = $this->service->checkAllSites(collect([$site1, $site2]));

    expect($results)->toHaveCount(2);

    $siteResults = $results->keyBy('siteId');
    // Failing site has DNS failure
    expect($siteResults[1]->pageResults->first()->httpCode)->toBe(0);
    expect($siteResults[1]->pageResults->first()->errorType)->toBe(ErrorType::DnsFailure);
    // Working site still gets checked
    expect($siteResults[2]->pageResults->first()->httpCode)->toBe(200);
    expect($siteResults[2]->pageResults->first()->errorType)->toBe(ErrorType::None);
});

it('constructs correct url from base_url and page path', function () {
    $requestedUrls = [];
    Http::fake(function ($request) use (&$requestedUrls) {
        $requestedUrls[] = $request->url();
        return Http::response('OK', 200);
    });

    $site = new Site(['name' => 'URL Test', 'base_url' => 'https://example.com']);
    $site->id = 1;

    $page1 = new Page(['path' => '/']);
    $page1->id = 1;
    $page1->site_id = 1;

    $page2 = new Page(['path' => '/api/health']);
    $page2->id = 2;
    $page2->site_id = 1;

    $site->setRelation('pages', collect([$page1, $page2]));

    $this->service->checkSite($site);

    expect($requestedUrls)->toContain('https://example.com/');
    expect($requestedUrls)->toContain('https://example.com/api/health');
});

it('handles base_url with trailing slash correctly', function () {
    $requestedUrls = [];
    Http::fake(function ($request) use (&$requestedUrls) {
        $requestedUrls[] = $request->url();
        return Http::response('OK', 200);
    });

    $site = new Site(['name' => 'Trailing Slash', 'base_url' => 'https://example.com/']);
    $site->id = 1;

    $page = new Page(['path' => '/about']);
    $page->id = 1;
    $page->site_id = 1;

    $site->setRelation('pages', collect([$page]));

    $this->service->checkSite($site);

    expect($requestedUrls)->toContain('https://example.com/about');
});

it('logs every page to the live log, failures included, with a rolling concurrency limit', function () {
    \App\Models\SystemConfig::create(['key' => 'concurrency_limit', 'value' => '2', 'updated_at' => now()]);

    Http::fake(function ($request) {
        if (str_contains($request->url(), '/down')) {
            throw new \GuzzleHttp\Exception\ConnectException(
                'cURL error 28: Operation timed out',
                new \GuzzleHttp\Psr7\Request('GET', $request->url())
            );
        }

        return Http::response('OK', 200);
    });

    $site = new Site(['name' => 'Mixed Site', 'base_url' => 'https://mixed.example']);
    $site->id = 1;
    $pages = collect(['/', '/a', '/down', '/b', '/c'])->map(function ($path, $i) {
        $page = new Page(['path' => $path]);
        $page->id = $i + 1;
        $page->site_id = 1;

        return $page;
    });
    $site->setRelation('pages', $pages);

    $result = $this->service->checkSite($site);

    // 5 pages with only 2 in flight at once: every page still gets checked, in order
    expect($result->pageResults->pluck('pageId')->all())->toBe([1, 2, 3, 4, 5]);
    expect($result->pageResults->firstWhere('pageId', 3)->errorType)->toBe(ErrorType::Timeout);

    $log = \Illuminate\Support\Facades\Cache::get('monitoring_cycle_live_log');
    expect($log)->toHaveCount(5);
    expect(collect($log)->pluck('url'))->toContain('https://mixed.example/down');
    expect(\Illuminate\Support\Facades\Cache::get('monitoring_cycle_total_pages'))->toBe(5);
});

it('keeps real page results when the live log cannot be written', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    // Setup writes succeed; every live-log write fails, as when Redis drops mid-cycle
    \Illuminate\Support\Facades\Cache::shouldReceive('get')->andReturn([]);
    \Illuminate\Support\Facades\Cache::shouldReceive('put')->andReturnUsing(
        fn ($key, $value) => $key === 'monitoring_cycle_live_log' && $value !== []
            ? throw new RuntimeException('cache down')
            : true
    );

    $site = new Site(['name' => 'Healthy Site', 'base_url' => 'https://healthy.example']);
    $site->id = 1;
    $pages = collect(['/', '/a', '/b'])->map(function ($path, $i) {
        $page = new Page(['path' => $path]);
        $page->id = $i + 1;
        $page->site_id = 1;

        return $page;
    });
    $site->setRelation('pages', $pages);

    $result = $this->service->checkSite($site);

    expect($result->pageResults)->toHaveCount(3);
    expect($result->pageResults->pluck('httpCode')->unique()->all())->toBe([200]);
    expect($result->pageResults->pluck('errorType')->unique()->all())->toBe([ErrorType::None]);
});

it('identifies itself with an ITK-Webstatus user agent instead of the Guzzle default', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $site = new Site(['name' => 'UA Site', 'base_url' => 'https://ua.example']);
    $site->id = 1;
    $page = new Page(['path' => '/']);
    $page->id = 1;
    $page->site_id = 1;
    $site->setRelation('pages', collect([$page]));

    $this->service->checkSite($site);

    Http::assertSent(fn ($request) => str_starts_with($request->header('User-Agent')[0] ?? '', 'ITK-Webstatus/1.0'));
});
