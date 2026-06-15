<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Property-Based Testing Helper
|--------------------------------------------------------------------------
|
| The forAll() helper enables property-based testing by running assertions
| against randomly generated inputs for a specified number of iterations.
| This helps verify that properties hold across all valid inputs.
|
*/

/**
 * Run a property-based test with random inputs.
 *
 * @param callable $generator A callable that returns generated test data for a single iteration.
 * @param callable $assertion A callable that receives the generated data and performs assertions.
 * @param int $iterations The number of random iterations to run (default: 100).
 */
function forAll(callable $generator, callable $assertion, int $iterations = 100): void
{
    for ($i = 0; $i < $iterations; $i++) {
        $data = $generator();
        $assertion($data);
    }
}
