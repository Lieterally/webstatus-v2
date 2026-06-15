<?php

use Tests\Helpers\PropertyTestHelpers;

test('forAll helper runs assertions for specified iterations', function () {
    $count = 0;

    forAll(
        generator: fn () => ['value' => rand(1, 100)],
        assertion: function ($data) use (&$count) {
            expect($data['value'])->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(100);
            $count++;
        },
        iterations: 50
    );

    expect($count)->toBe(50);
});

test('PropertyTestHelpers generates valid HTTP codes', function () {
    forAll(
        generator: fn () => ['code' => PropertyTestHelpers::randomHttpCode(successful: true)],
        assertion: function ($data) {
            expect(PropertyTestHelpers::isSuccessfulCode($data['code']))->toBeTrue();
        },
        iterations: 100
    );
});
