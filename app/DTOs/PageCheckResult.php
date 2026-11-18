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
        return $this->errorType === ErrorType::None && self::isSuccessfulCode($this->httpCode);
    }

    /**
     * The one rule for which HTTP codes count as "up"; dashboard status and alerts both use it.
     *
     * 429 counts as up: the server answered and is only asking the checker to slow down,
     * since all of a site's pages are requested at once.
     */
    public static function isSuccessfulCode(int $httpCode): bool
    {
        return ($httpCode >= 200 && $httpCode < 400) || $httpCode === 429;
    }

    /**
     * Determine if the page responded (even with an error HTTP code).
     */
    public function hasResponse(): bool
    {
        return $this->httpCode > 0;
    }
}
