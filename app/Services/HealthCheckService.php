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

class HealthCheckService implements HealthCheckServiceInterface
{
    /** Default connection timeout in seconds */
    private const DEFAULT_CONNECTION_TIMEOUT = 20;

    /** Default response timeout in seconds */
    private const DEFAULT_RESPONSE_TIMEOUT = 50;

    /** Default max concurrent requests per batch */
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

    /**
     * Append a log entry to the live log.
     */
    private function appendLogEntry(string $siteName, string $url, int $httpCode, string $errorType, float $responseTimeMs): void
    {
        $logs = Cache::get(self::CYCLE_LOG_KEY, []);
        $logs[] = [
            'site' => $siteName,
            'url' => $url,
            'http_code' => $httpCode,
            'error_type' => $errorType,
            'response_time_ms' => round($responseTimeMs, 1),
            'time' => now()->format('H:i:s'),
        ];
        Cache::put(self::CYCLE_LOG_KEY, $logs, 600);
    }

    /**
     * Check all pages of all provided sites concurrently.
     *
     * Uses Laravel HTTP Client pool for concurrent requests with an overall
     * cycle timeout of 10 seconds. Pages that don't complete within the cycle
     * timeout are marked as unreachable.
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

        // Execute all checks concurrently with cycle timeout
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
     * Execute HTTP checks concurrently using Laravel HTTP Client pool
     * with concurrency limiting via batches.
     *
     * Processes requests in batches of CONCURRENCY_LIMIT to avoid
     * overwhelming OS connection limits and DNS resolution.
     *
     * @param Collection $pageChecks
     * @return Collection<int, PageCheckResult>
     */
    private function executeChecks(Collection $pageChecks): Collection
    {
        $results = collect();

        // Process in batches to limit concurrency
        $batches = $pageChecks->chunk($this->getConcurrencyLimit());

        foreach ($batches as $batch) {
            $batchResults = $this->executeBatch($batch);
            $results = $results->concat($batchResults);
        }

        return $results;
    }

    /**
     * Execute a single batch of HTTP checks concurrently.
     *
     * @param Collection $batch
     * @return Collection<int, PageCheckResult>
     */
    private function executeBatch(Collection $batch): Collection
    {
        $results = collect();
        $batchArray = $batch->values()->all();
        $responseTimeout = $this->getResponseTimeout();
        $connectionTimeout = $this->getConnectionTimeout();

        $responses = Http::pool(function (Pool $pool) use ($batchArray, $responseTimeout, $connectionTimeout) {
            foreach ($batchArray as $index => $check) {
                $pool->as((string) $index)
                    ->timeout($responseTimeout)
                    ->connectTimeout($connectionTimeout)
                    ->withOptions([
                        'verify' => false,
                    ])
                    ->get($check['url']);
            }
        });

        foreach ($batchArray as $index => $check) {
            $key = (string) $index;
            $response = $responses[$key] ?? null;

            if ($response === null) {
                $result = $this->buildUnreachableResult($check, ErrorType::ConnectionFailure);
                $results->push($result);
                $this->appendLogEntry($check['site_name'], $check['url'], 0, ErrorType::ConnectionFailure->value, 0);
                continue;
            }

            $result = $this->buildResultFromResponse($check, $response);
            $results->push($result);
            $this->appendLogEntry($check['site_name'], $check['url'], $result->httpCode, $result->errorType->value, $result->responseTimeMs);
        }

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
