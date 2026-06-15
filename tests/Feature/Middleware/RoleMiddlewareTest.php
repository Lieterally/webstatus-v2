<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| RoleMiddleware Tests
|--------------------------------------------------------------------------
|
| Tests for role-based access control middleware enforcement.
| Validates: Requirements 22.1, 22.2, 22.3, 22.4, 22.5, 22.6, 27.3, 27.4, 27.5
|
*/

// --- Admin Role Access Tests ---

it('allows admin to access dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertStatus(200);
});

it('allows admin to access website manager', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/sites');

    $response->assertStatus(200);
});

it('allows admin to access profile settings', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/profile');

    $response->assertStatus(200);
});

it('allows admin to access password change', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/profile/password');

    $response->assertStatus(200);
});

it('denies admin access to user manager and redirects to dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/users');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

it('denies admin access to IT staff manager and redirects to dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/it-staff');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

it('denies admin access to Telegram target manager and redirects to dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/telegram-targets');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

it('denies admin access to system configuration and redirects to dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/system-config');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

// --- Super_Admin Role Access Tests ---

it('allows super_admin to access dashboard', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin)->get('/dashboard');

    $response->assertStatus(200);
});

it('allows super_admin to access website manager', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin)->get('/sites');

    $response->assertStatus(200);
});

it('allows super_admin to access profile settings', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin)->get('/profile');

    $response->assertStatus(200);
});

it('allows super_admin to access user manager', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin)->get('/users');

    $response->assertStatus(200);
});

it('allows super_admin to access IT staff manager', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin)->get('/it-staff');

    $response->assertStatus(200);
});

it('allows super_admin to access Telegram target manager', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin)->get('/telegram-targets');

    $response->assertStatus(200);
});

it('allows super_admin to access system configuration', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin)->get('/system-config');

    $response->assertStatus(200);
});

// --- Unauthenticated Access Tests ---

it('redirects unauthenticated users to login for dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

it('redirects unauthenticated users to login for super_admin routes', function () {
    $response = $this->get('/users');

    $response->assertRedirect('/login');
});

it('redirects unauthenticated users to login for website manager', function () {
    $response = $this->get('/sites');

    $response->assertRedirect('/login');
});

// --- Role Update Enforcement Tests ---

it('enforces updated role permissions on next request', function () {
    $user = User::factory()->superAdmin()->create();

    // First access as super_admin - should succeed
    $response = $this->actingAs($user)->get('/users');
    $response->assertStatus(200);

    // Change role to admin
    $user->update(['role' => 'admin']);
    $user->refresh();

    // Next request should be denied
    $response = $this->actingAs($user)->get('/users');
    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

// --- Error Message Tests ---

it('shows unauthorized access error message when admin is denied', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/users');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'You do not have permission to access this resource.');
});
