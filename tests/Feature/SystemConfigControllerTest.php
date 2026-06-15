<?php

use App\Models\SystemConfig;
use App\Models\User;

beforeEach(function () {
    $this->superAdmin = User::create([
        'username' => 'superadmin',
        'password' => bcrypt('password123'),
        'role' => 'super_admin',
    ]);

    $this->admin = User::create([
        'username' => 'admin',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);

    // Seed default system config values
    SystemConfig::create(['key' => 'cycle_interval_minutes', 'value' => '10']);
    SystemConfig::create(['key' => 'notification_cycle_threshold', 'value' => '6']);
});

it('displays the system configuration page for super_admin', function () {
    $response = $this->actingAs($this->superAdmin)->get(route('system-config.index'));

    $response->assertStatus(200);
    $response->assertSee('System Configuration');
    $response->assertSee('10');
    $response->assertSee('6');
});

it('denies admin access to system configuration page', function () {
    $response = $this->actingAs($this->admin)->get(route('system-config.index'));

    $response->assertRedirect(route('dashboard'));
});

it('requires authentication to access system configuration', function () {
    $response = $this->get(route('system-config.index'));

    $response->assertRedirect('/login');
});

it('updates cycle interval with valid value', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 30,
        'notification_cycle_threshold' => 6,
    ]);

    $response->assertRedirect(route('system-config.index'));
    $response->assertSessionHas('success');
    expect(SystemConfig::getValue('cycle_interval_minutes'))->toBe('30');
});

it('updates notification cycle threshold with valid value', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 10,
        'notification_cycle_threshold' => 12,
    ]);

    $response->assertRedirect(route('system-config.index'));
    $response->assertSessionHas('success');
    expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe('12');
});

it('rejects cycle interval below minimum (5)', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 4,
        'notification_cycle_threshold' => 6,
    ]);

    $response->assertSessionHasErrors('cycle_interval_minutes');
    expect(SystemConfig::getValue('cycle_interval_minutes'))->toBe('10');
});

it('rejects cycle interval above maximum (1440)', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 1441,
        'notification_cycle_threshold' => 6,
    ]);

    $response->assertSessionHasErrors('cycle_interval_minutes');
    expect(SystemConfig::getValue('cycle_interval_minutes'))->toBe('10');
});

it('rejects notification cycle threshold below minimum (1)', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 10,
        'notification_cycle_threshold' => 0,
    ]);

    $response->assertSessionHasErrors('notification_cycle_threshold');
    expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe('6');
});

it('rejects notification cycle threshold above maximum (100)', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 10,
        'notification_cycle_threshold' => 101,
    ]);

    $response->assertSessionHasErrors('notification_cycle_threshold');
    expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe('6');
});

it('rejects non-integer cycle interval', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 'abc',
        'notification_cycle_threshold' => 6,
    ]);

    $response->assertSessionHasErrors('cycle_interval_minutes');
});

it('rejects non-integer notification cycle threshold', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 10,
        'notification_cycle_threshold' => 'abc',
    ]);

    $response->assertSessionHasErrors('notification_cycle_threshold');
});

it('accepts boundary value 5 for cycle interval', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 5,
        'notification_cycle_threshold' => 6,
    ]);

    $response->assertRedirect(route('system-config.index'));
    expect(SystemConfig::getValue('cycle_interval_minutes'))->toBe('5');
});

it('accepts boundary value 1440 for cycle interval', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 1440,
        'notification_cycle_threshold' => 6,
    ]);

    $response->assertRedirect(route('system-config.index'));
    expect(SystemConfig::getValue('cycle_interval_minutes'))->toBe('1440');
});

it('accepts boundary value 1 for notification cycle threshold', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 10,
        'notification_cycle_threshold' => 1,
    ]);

    $response->assertRedirect(route('system-config.index'));
    expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe('1');
});

it('accepts boundary value 100 for notification cycle threshold', function () {
    $response = $this->actingAs($this->superAdmin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 10,
        'notification_cycle_threshold' => 100,
    ]);

    $response->assertRedirect(route('system-config.index'));
    expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe('100');
});

it('denies admin access to update system configuration', function () {
    $response = $this->actingAs($this->admin)->put(route('system-config.update'), [
        'cycle_interval_minutes' => 30,
        'notification_cycle_threshold' => 12,
    ]);

    $response->assertRedirect(route('dashboard'));
    expect(SystemConfig::getValue('cycle_interval_minutes'))->toBe('10');
});
