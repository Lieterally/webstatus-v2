<?php

namespace App\DTOs;

use App\Enums\ErrorType;

/**
 * Represents the result of a single page health check.
 */
class PageCheckResult
{
    public function __construct(
        public readonly int $pageId,
        public readonly int $siteId,
        public readonly string $url,
        public readonly int $httpCode,
        public readonly float $responseTimeMs,
        public readonly ErrorType $errorType,
    ) {}

    /**
     * Determine if the page is reachable (successful response).
     */
    public function isReachable(): bool
    {
        return $this->errorType === ErrorType::None && $this->httpCode >= 200 && $this->httpCode < 400;
    }

    /**
     * Determine if the page responded (even with an error HTTP code).
     */
    public function hasResponse(): bool
    {
        return $this->httpCode > 0;
    }
}
