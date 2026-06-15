<?php

namespace Tests\Helpers;

use Faker\Factory as Faker;

/**
 * Property test helper utilities for generating random test data.
 *
 * Used by property-based tests to generate inputs for verifying
 * correctness properties across many randomized iterations.
 */
class PropertyTestHelpers
{
    protected static ?\Faker\Generator $faker = null;

    /**
     * Get a shared Faker instance.
     */
    public static function faker(): \Faker\Generator
    {
        if (static::$faker === null) {
            static::$faker = Faker::create();
        }

        return static::$faker;
    }

    /**
     * Generate a random HTTP status code.
     *
     * @param bool $successful If true, generates only 2xx/3xx codes.
     */
    public static function randomHttpCode(bool $successful = false): int
    {
        $faker = static::faker();

        if ($successful) {
            return $faker->randomElement([200, 201, 204, 301, 302, 304]);
        }

        return $faker->randomElement([
            200, 201, 204, 301, 302, 304,  // Success codes
            400, 401, 403, 404, 500, 502, 503, 504, 0,  // Failure codes
        ]);
    }

    /**
     * Generate a random response time in milliseconds.
     */
    public static function randomResponseTime(): float
    {
        return static::faker()->randomFloat(2, 10, 15000);
    }

    /**
     * Generate a random error type.
     */
    public static function randomErrorType(): string
    {
        return static::faker()->randomElement([
            'none', 'timeout', 'connection_failure', 'dns_failure',
        ]);
    }

    /**
     * Generate a random site status.
     */
    public static function randomSiteStatus(): string
    {
        return static::faker()->randomElement([
            'up', 'partially_down', 'totally_down',
        ]);
    }

    /**
     * Determine if an HTTP code is considered successful (2xx or 3xx).
     */
    public static function isSuccessfulCode(int $code): bool
    {
        return $code >= 200 && $code < 400;
    }

    /**
     * Generate a random valid URL.
     */
    public static function randomValidUrl(): string
    {
        $faker = static::faker();
        $protocol = $faker->randomElement(['http://', 'https://']);

        return $protocol . $faker->domainName();
    }

    /**
     * Generate a random invalid URL (not starting with http:// or https://).
     */
    public static function randomInvalidUrl(): string
    {
        $faker = static::faker();

        return $faker->randomElement([
            'ftp://' . $faker->domainName(),
            'www.' . $faker->domainName(),
            $faker->domainName(),
            '',
            'htp://' . $faker->domainName(),
            '/' . $faker->word(),
        ]);
    }

    /**
     * Generate a random valid page path (starts with /).
     */
    public static function randomValidPath(): string
    {
        $faker = static::faker();
        $segments = $faker->numberBetween(1, 3);
        $path = '';

        for ($i = 0; $i < $segments; $i++) {
            $path .= '/' . $faker->slug(2);
        }

        return $path;
    }

    /**
     * Generate a random invalid page path (does not start with /).
     */
    public static function randomInvalidPath(): string
    {
        $faker = static::faker();

        return $faker->randomElement([
            $faker->word(),
            'page/' . $faker->word(),
            '../' . $faker->word(),
            '',
            $faker->url(),
        ]);
    }
}
