<?php

// Feature: webstatus-v2, Property 4: Notification cycle threshold validation accepts only whole numbers in [1, 100]

use App\Models\SystemConfig;
use App\Models\User;
use Tests\Helpers\PropertyTestHelpers;

/**
 * **Validates: Requirements 25.2, 25.3**
 *
 * Property 4: Notification cycle threshold validation accepts only whole numbers in [1, 100]
 *
 * For any value submitted as a notification cycle threshold, the system SHALL accept it if and
 * only if it is a whole number in the range [1, 100] inclusive. Non-integer values and values
 * outside this range SHALL be rejected.
 */

beforeEach(function () {
    $this->superAdmin = User::create([
        'username' => 'superadmin_prop4',
        'password' => bcrypt('password123'),
        'role' => 'super_admin',
    ]);

    // Seed default system config values
    SystemConfig::create(['key' => 'cycle_interval_minutes', 'value' => '10']);
    SystemConfig::create(['key' => 'notification_cycle_threshold', 'value' => '6']);
});

describe('Property 4: Notification cycle threshold validation accepts only whole numbers in [1, 100]', function () {

    it('accepts any whole number in [1, 100] (Requirement 25.2)', function () {
        // **Validates: Requirements 25.2**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate a random whole number in the valid range [1, 100]
                $value = $faker->numberBetween(1, 100);

                return ['value' => $value];
            },
            assertion: function ($data) {
                $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
                    'cycle_interval_minutes' => 10,
                    'notification_cycle_threshold' => $data['value'],
                ]);

                $response->assertRedirect(route('system-config.index'));
                $response->assertSessionHasNoErrors();
                expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe((string) $data['value']);

                // Reset for next iteration
                SystemConfig::where('key', 'notification_cycle_threshold')->update(['value' => '6']);
            },
            iterations: 100
        );
    });

    it('rejects integers below 1 (Requirement 25.3)', function () {
        // **Validates: Requirements 25.3**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate random integers below 1 (including negative numbers and zero)
                $value = $faker->numberBetween(-1000, 0);

                return ['value' => $value];
            },
            assertion: function ($data) {
                $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
                    'cycle_interval_minutes' => 10,
                    'notification_cycle_threshold' => $data['value'],
                ]);

                $response->assertSessionHasErrors('notification_cycle_threshold');
                // Value should remain unchanged
                expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe('6');
            },
            iterations: 100
        );
    });

    it('rejects integers above 100 (Requirement 25.3)', function () {
        // **Validates: Requirements 25.3**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate random integers above 100
                $value = $faker->numberBetween(101, 10000);

                return ['value' => $value];
            },
            assertion: function ($data) {
                $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
                    'cycle_interval_minutes' => 10,
                    'notification_cycle_threshold' => $data['value'],
                ]);

                $response->assertSessionHasErrors('notification_cycle_threshold');
                // Value should remain unchanged
                expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe('6');
            },
            iterations: 100
        );
    });

    it('rejects non-integer values (Requirement 25.3)', function () {
        // **Validates: Requirements 25.3**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate various non-integer values: floats, strings, special values
                $type = $faker->randomElement(['float', 'string', 'decimal_string', 'special']);

                $value = match ($type) {
                    'float' => $faker->randomFloat(2, 0.1, 200.0),
                    'string' => $faker->word(),
                    'decimal_string' => $faker->numberBetween(1, 100) . '.' . $faker->numberBetween(1, 99),
                    'special' => $faker->randomElement(['', null, 'abc', '1.5', '50.7', '-3.2', 'true', '0xFF']),
                };

                return ['value' => $value];
            },
            assertion: function ($data) {
                $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
                    'cycle_interval_minutes' => 10,
                    'notification_cycle_threshold' => $data['value'],
                ]);

                $response->assertSessionHasErrors('notification_cycle_threshold');
                // Value should remain unchanged
                expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe('6');
            },
            iterations: 100
        );
    });

    it('validates boundary values exactly at 1 and 100 are accepted (Requirements 25.2, 25.3)', function () {
        // **Validates: Requirements 25.2, 25.3**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Alternate between testing boundary value 1 and 100
                $value = $faker->randomElement([1, 100]);

                return ['value' => $value];
            },
            assertion: function ($data) {
                $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
                    'cycle_interval_minutes' => 10,
                    'notification_cycle_threshold' => $data['value'],
                ]);

                $response->assertRedirect(route('system-config.index'));
                $response->assertSessionHasNoErrors();
                expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe((string) $data['value']);

                // Reset for next iteration
                SystemConfig::where('key', 'notification_cycle_threshold')->update(['value' => '6']);
            },
            iterations: 100
        );
    });

    it('validates mixed random values correctly classify as accepted or rejected (Requirements 25.2, 25.3)', function () {
        // **Validates: Requirements 25.2, 25.3**
        forAll(
            generator: function () {
                $faker = PropertyTestHelpers::faker();
                // Generate random values across a wide range - both valid and invalid
                $type = $faker->randomElement(['valid_int', 'below_range', 'above_range', 'non_integer']);

                return match ($type) {
                    'valid_int' => [
                        'value' => $faker->numberBetween(1, 100),
                        'shouldAccept' => true,
                    ],
                    'below_range' => [
                        'value' => $faker->numberBetween(-500, 0),
                        'shouldAccept' => false,
                    ],
                    'above_range' => [
                        'value' => $faker->numberBetween(101, 5000),
                        'shouldAccept' => false,
                    ],
                    'non_integer' => [
                        'value' => $faker->randomElement([
                            $faker->randomFloat(2, 0.1, 200.0),
                            $faker->word(),
                            '',
                            null,
                            '3.14',
                            '99.9',
                        ]),
                        'shouldAccept' => false,
                    ],
                };
            },
            assertion: function ($data) {
                $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
                    'cycle_interval_minutes' => 10,
                    'notification_cycle_threshold' => $data['value'],
                ]);

                if ($data['shouldAccept']) {
                    $response->assertRedirect(route('system-config.index'));
                    $response->assertSessionHasNoErrors();
                    expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe((string) $data['value']);
                    // Reset for next iteration
                    SystemConfig::where('key', 'notification_cycle_threshold')->update(['value' => '6']);
                } else {
                    $response->assertSessionHasErrors('notification_cycle_threshold');
                    expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe('6');
                }
            },
            iterations: 100
        );
    });
});
