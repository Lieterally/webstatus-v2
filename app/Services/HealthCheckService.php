<?php

namespace App\Services;

use App\Services\HealthCheckServiceInterface;
use App\DTOs\PageCheckResult;
use App\DTOs\SiteCheckResult;
use App\Enums\ErrorType;
use App\Models\Site;
use App\Models\SystemConfig;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HealthCheckService implements HealthCheckServiceInterface
{
    /** Default connection timeout in seconds */
    private const DEFAULT_CONNECTION_TIMEOUT = 20;

    /** Default response timeout in seconds */
    private const DEFAULT_RESPONSE_TIMEOUT = 50;

    /** Default max requests in flight at once */
    private const DEFAULT_CONCURRENCY_LIMIT = 50;

    /**
     * Get the connection timeout from system config or fall back to default.
     */
    private function getConnectionTimeout(): int
    {
        $value = SystemConfig::getValue('connection_timeout_seconds');
        return $value !== null ? (int) $value : self::DEFAULT_CONNECTION_TIMEOUT;
    }

    /**
     * Get the response timeout from system config or fall back to default.
     */
    private function getResponseTimeout(): int
    {
        $value = SystemConfig::getValue('response_timeout_seconds');
        return $value !== null ? (int) $value : self::DEFAULT_RESPONSE_TIMEOUT;
    }

    /**
     * Get the concurrency limit from system config or fall back to default.
     */
    private function getConcurrencyLimit(): int
    {
        $value = SystemConfig::getValue('concurrency_limit');
        return $value !== null ? (int) $value : self::DEFAULT_CONCURRENCY_LIMIT;
    }

    /** Cache key for live cycle logs */
    private const CYCLE_LOG_KEY = 'monitoring_cycle_live_log';

    /** Cache key for total pages count in current cycle */
    private const CYCLE_TOTAL_KEY = 'monitoring_cycle_total_pages';

    /**
     * Initialize live log for a new cycle.
     * Only resets if no log exists yet (avoids wiping on retry).
     */
    private function initLiveLog(int $totalPages): void
    {
        // If log already has entries, this is a retry — just update the total count
        $existingLogs = Cache::get(self::CYCLE_LOG_KEY, []);
        if (!empty($existingLogs)) {
            $currentTotal = (int) Cache::get(self::CYCLE_TOTAL_KEY, 0);
            Cache::put(self::CYCLE_TOTAL_KEY, $currentTotal + $totalPages, 600);
            return;
        }

        Cache::put(self::CYCLE_TOTAL_KEY, $totalPages, 600);
        Cache::put(self::CYCLE_LOG_KEY, [], 600);
    }

    /** Seconds between live-log writes; the dashboard polls every 2 seconds */
    private const LOG_FLUSH_SECONDS = 1;

    /**
     * Build one live-log entry for a finished page check.
     */
    private function logEntry(array $check, PageCheckResult $result): array
    {
        return [
            'site' => $check['site_name'],
            'url' => $check['url'],
            'http_code' => $result->httpCode,
            'error_type' => $result->errorType->value,
            'response_time_ms' => round($result->responseTimeMs, 1),
            'time' => now()->format('H:i:s'),
        ];
    }

    /**
     * Append buffered entries to the live log in one cache read and write.
     *
     * Best effort: this runs inside the pool's per-page callback, where an exception would
     * replace that page's HTTP result and mark a healthy site down.
     */
    private function flushLogEntries(array &$entries): void
    {
        if ($entries === []) {
            return;
        }

        try {
            Cache::put(self::CYCLE_LOG_KEY, [...Cache::get(self::CYCLE_LOG_KEY, []), ...$entries], 600);
        } catch (\Throwable $e) {
            Log::warning('Could not write the monitoring live log', ['error' => $e->getMessage()]);
        }

        $entries = [];
    }

    /**
     * Check all pages of all provided sites concurrently.
     *
     * Uses one Laravel HTTP Client pool. Each page has its own connection and
     * response timeout; a page that times out is marked as unreachable.
     *
     * @param Collection<int, Site> $sites
     * @return Collection<int, SiteCheckResult>
     */
    public function checkAllSites(Collection $sites): Collection
    {
        // Build a flat list of all pages to check across all sites
        $pageChecks = $this->buildPageCheckList($sites);

        if ($pageChecks->isEmpty()) {
            return $sites->map(fn(Site $site) => new SiteCheckResult(
                siteId: $site->id,
                siteName: $site->name,
                pageResults: collect(),
            ));
        }

        // Initialize live log for this cycle
        $this->initLiveLog($pageChecks->count());

        $results = $this->executeChecks($pageChecks);

        // Group results by site and build SiteCheckResult DTOs
        return $this->buildSiteResults($sites, $results);
    }

    /**
     * Check all pages of a single site.
     */
    public function checkSite(Site $site): SiteCheckResult
    {
        $results = $this->checkAllSites(collect([$site]));

        return $results->first();
    }

    /**
     * Build a flat list of page check descriptors from all sites.
     *
     * @param Collection<int, Site> $sites
     * @return Collection<int, array{page_id: int, site_id: int, url: string, site_name: string}>
     */
    private function buildPageCheckList(Collection $sites): Collection
    {
        return $sites->flatMap(function (Site $site) {
            $site->loadMissing('pages');

            return $site->pages->map(fn($page) => [
                'page_id' => $page->id,
                'site_id' => $site->id,
                'url' => rtrim($site->base_url, '/') . '/' . ltrim($page->path, '/'),
                'site_name' => $site->name,
            ]);
        });
    }

    /**
     * Execute all HTTP checks in one pool with a rolling concurrency limit.
     *
     * At most CONCURRENCY_LIMIT requests are in flight, and a new one starts as soon as any
     * finishes, so one slow page no longer holds up a whole batch of others.
     *
     * @param Collection $pageChecks
     * @return Collection<int, PageCheckResult>
     */
    private function executeChecks(Collection $pageChecks): Collection
    {
        $checks = $pageChecks->values()->all();
        $responseTimeout = $this->getResponseTimeout();
        $connectionTimeout = $this->getConnectionTimeout();

        $logBuffer = [];
        $lastFlush = microtime(true);
        // Runs as each page finishes, so the dashboard progress bar moves during the cycle.
        // Must return $response: the pool collects this callback's return value, not the original.
        $onFinished = function (array $check, mixed $response) use (&$logBuffer, &$lastFlush) {
            $logBuffer[] = $this->logEntry($check, $this->buildResultFromResponse($check, $response));

            if (microtime(true) - $lastFlush >= self::LOG_FLUSH_SECONDS) {
                $this->flushLogEntries($logBuffer);
                $lastFlush = microtime(true);
            }

            return $response;
        };

        $responses = Http::pool(function (Pool $pool) use ($checks, $responseTimeout, $connectionTimeout, $onFinished) {
            foreach ($checks as $index => $check) {
                $pool->as((string) $index)
                    ->timeout($responseTimeout)
                    ->connectTimeout($connectionTimeout)
                    ->withOptions([
                        'verify' => false,
                    ])
                    ->get($check['url'])
                    ->then(
                        fn ($response) => $onFinished($check, $response),
                        fn ($reason) => $onFinished($check, $reason),
                    );
            }
        }, $this->getConcurrencyLimit());

        $results = collect();

        foreach ($checks as $index => $check) {
            $response = $responses[(string) $index] ?? null;

            if ($response === null) {
                $result = $this->buildUnreachableResult($check, ErrorType::ConnectionFailure);
                $logBuffer[] = $this->logEntry($check, $result);
                $results->push($result);
                continue;
            }

            $results->push($this->buildResultFromResponse($check, $response));
        }

        $this->flushLogEntries($logBuffer);

        return $results;
    }

    /**
     * Build a PageCheckResult from an HTTP response, handling various error types.
     */
    private function buildResultFromResponse(array $check, mixed $response): PageCheckResult
    {
        // Check if this is a successful HTTP response (may still be 4xx/5xx)
        if ($response instanceof \Illuminate\Http\Client\Response) {
            $transferTime = $response->transferStats?->getTransferTime();
            $responseTimeMs = $transferTime !== null
                ? $transferTime * 1000
                : 0.0;

            return new PageCheckResult(
                pageId: $check['page_id'],
                siteId: $check['site_id'],
                url: $check['url'],
                httpCode: $response->status(),
                responseTimeMs: round($responseTimeMs, 2),
                errorType: ErrorType::None,
            );
        }

        // Handle connection/timeout errors from the pool
        $errorType = $this->classifyError($response);

        return new PageCheckResult(
            pageId: $check['page_id'],
            siteId: $check['site_id'],
            url: $check['url'],
            httpCode: 0,
            responseTimeMs: 0.0,
            errorType: $errorType,
        );
    }

    /**
     * Classify the error type from a failed pool response.
     */
    private function classifyError(mixed $response): ErrorType
    {
        // Laravel HTTP pool wraps exceptions in ConnectionException
        if ($response instanceof \Illuminate\Http\Client\ConnectionException) {
            $message = strtolower($response->getMessage());

            if (str_contains($message, 'could not resolve') || str_contains($message, 'name or service not known') || str_contains($message, 'getaddrinfo')) {
                return ErrorType::DnsFailure;
            }

            if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
                return ErrorType::Timeout;
            }

            if (str_contains($message, 'ssl') || str_contains($message, 'certificate') || str_contains($message, 'tls')) {
                return ErrorType::ConnectionFailure;
            }

            // Check the previous exception for more context
            $previous = $response->getPrevious();
            if ($previous !== null) {
                return $this->classifyError($previous);
            }

            return ErrorType::ConnectionFailure;
        }

        if ($response instanceof \GuzzleHttp\Exception\ConnectException) {
            $message = strtolower($response->getMessage());

            if (str_contains($message, 'could not resolve') || str_contains($message, 'name or service not known') || str_contains($message, 'getaddrinfo')) {
                return ErrorType::DnsFailure;
            }

            if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
                return ErrorType::Timeout;
            }

            return ErrorType::ConnectionFailure;
        }

        if ($response instanceof \GuzzleHttp\Exception\RequestException) {
            $message = strtolower($response->getMessage());

            if (str_contains($message, 'ssl') || str_contains($message, 'certificate') || str_contains($message, 'tls')) {
                return ErrorType::ConnectionFailure;
            }

            if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
                return ErrorType::Timeout;
            }

            return ErrorType::ConnectionFailure;
        }

        return ErrorType::ConnectionFailure;
    }

    /**
     * Build an unreachable PageCheckResult for pages that couldn't be checked.
     */
    private function buildUnreachableResult(array $check, ErrorType $errorType): PageCheckResult
    {
        return new PageCheckResult(
            pageId: $check['page_id'],
            siteId: $check['site_id'],
            url: $check['url'],
            httpCode: 0,
            responseTimeMs: 0.0,
            errorType: $errorType,
        );
    }

    /**
     * Group page results by site and build SiteCheckResult DTOs.
     *
     * @param Collection<int, Site> $sites
     * @param Collection<int, PageCheckResult> $results
     * @return Collection<int, SiteCheckResult>
     */
    private function buildSiteResults(Collection $sites, Collection $results): Collection
    {
        $groupedResults = $results->groupBy('siteId');

        return $sites->map(function (Site $site) use ($groupedResults) {
            return new SiteCheckResult(
                siteId: $site->id,
                siteName: $site->name,
                pageResults: $groupedResults->get($site->id, collect()),
            );
        });
    }
}
