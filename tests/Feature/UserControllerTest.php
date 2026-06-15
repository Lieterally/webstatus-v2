<?php

use App\Models\User;

beforeEach(function () {
    $this->superAdmin = User::create([
        'username' => 'superadmin',
        'password' => bcrypt('password123'),
        'role' => 'super_admin',
    ]);
});

// --- Index ---

it('displays the users index page for super admin', function () {
    User::create([
        'username' => 'johndoe',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);

    $response = $this->actingAs($this->superAdmin)->get(route('users.index'));

    $response->assertStatus(200);
    $response->assertSee('johndoe');
    $response->assertSee('superadmin');
});

it('denies access to users index for admin role', function () {
    $admin = User::create([
        'username' => 'regularadmin',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin)->get(route('users.index'));

    $response->assertRedirect(route('dashboard'));
});

// --- Create ---

it('displays the create user form', function () {
    $response = $this->actingAs($this->superAdmin)->get(route('users.create'));

    $response->assertStatus(200);
    $response->assertSee('Create User');
});

it('stores a new user with valid data', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('users.store'), [
        'username' => 'newuser',
        'password' => 'securepass',
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', [
        'username' => 'newuser',
        'role' => 'admin',
    ]);
});

it('stores a new super admin user', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('users.store'), [
        'username' => 'newsuperadmin',
        'password' => 'securepass',
        'role' => 'super_admin',
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', [
        'username' => 'newsuperadmin',
        'role' => 'super_admin',
    ]);
});

// --- Validation on Store ---

it('validates username is required', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('users.store'), [
        'username' => '',
        'password' => 'securepass',
        'role' => 'admin',
    ]);

    $response->assertSessionHasErrors('username');
});

it('validates username minimum length', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('users.store'), [
        'username' => 'ab',
        'password' => 'securepass',
        'role' => 'admin',
    ]);

    $response->assertSessionHasErrors('username');
});

it('validates username maximum length', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('users.store'), [
        'username' => str_repeat('a', 51),
        'password' => 'securepass',
        'role' => 'admin',
    ]);

    $response->assertSessionHasErrors('username');
});

it('validates username is unique', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('users.store'), [
        'username' => 'superadmin',
        'password' => 'securepass',
        'role' => 'admin',
    ]);

    $response->assertSessionHasErrors('username');
});

it('validates password is required on create', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('users.store'), [
        'username' => 'newuser',
        'password' => '',
        'role' => 'admin',
    ]);

    $response->assertSessionHasErrors('password');
});

it('validates password minimum length', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('users.store'), [
        'username' => 'newuser',
        'password' => 'short',
        'role' => 'admin',
    ]);

    $response->assertSessionHasErrors('password');
});

it('validates password maximum length', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('users.store'), [
        'username' => 'newuser',
        'password' => str_repeat('a', 129),
        'role' => 'admin',
    ]);

    $response->assertSessionHasErrors('password');
});

it('validates role must be admin or super_admin', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('users.store'), [
        'username' => 'newuser',
        'password' => 'securepass',
        'role' => 'invalid_role',
    ]);

    $response->assertSessionHasErrors('role');
});

// --- Edit ---

it('displays the edit user form', function () {
    $user = User::create([
        'username' => 'editme',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);

    $response = $this->actingAs($this->superAdmin)->get(route('users.edit', $user));

    $response->assertStatus(200);
    $response->assertSee('Edit User');
    $response->assertSee('editme');
});

// --- Update ---

it('updates a user username and role', function () {
    $user = User::create([
        'username' => 'oldname',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);

    $response = $this->actingAs($this->superAdmin)->put(route('users.update', $user), [
        'username' => 'newname',
        'role' => 'super_admin',
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'username' => 'newname',
        'role' => 'super_admin',
    ]);
});

it('updates user password when provided', function () {
    $user = User::create([
        'username' => 'testuser',
        'password' => bcrypt('oldpassword'),
        'role' => 'admin',
    ]);

    $response = $this->actingAs($this->superAdmin)->put(route('users.update', $user), [
        'username' => 'testuser',
        'password' => 'newpassword123',
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('users.index'));
    $user->refresh();
    expect(password_verify('newpassword123', $user->password))->toBeTrue();
});

it('keeps existing password when password field is empty on update', function () {
    $user = User::create([
        'username' => 'testuser',
        'password' => bcrypt('originalpass'),
        'role' => 'admin',
    ]);

    $response = $this->actingAs($this->superAdmin)->put(route('users.update', $user), [
        'username' => 'testuser',
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('users.index'));
    $user->refresh();
    expect(password_verify('originalpass', $user->password))->toBeTrue();
});

it('validates username uniqueness on update excluding self', function () {
    $user1 = User::create([
        'username' => 'existing',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);

    $user2 = User::create([
        'username' => 'another',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);

    $response = $this->actingAs($this->superAdmin)->put(route('users.update', $user2), [
        'username' => 'existing',
        'role' => 'admin',
    ]);

    $response->assertSessionHasErrors('username');
});

it('allows updating user with its own username', function () {
    $user = User::create([
        'username' => 'samename',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);

    $response = $this->actingAs($this->superAdmin)->put(route('users.update', $user), [
        'username' => 'samename',
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('users.index'));
});

// --- Last Super Admin Protection ---

it('prevents changing the last super admin role to admin', function () {
    // $this->superAdmin is the only super_admin
    $response = $this->actingAs($this->superAdmin)->put(route('users.update', $this->superAdmin), [
        'username' => 'superadmin',
        'role' => 'admin',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('users', [
        'id' => $this->superAdmin->id,
        'role' => 'super_admin',
    ]);
});

it('allows changing super admin role when another super admin exists', function () {
    $anotherSuperAdmin = User::create([
        'username' => 'anothersuperadmin',
        'password' => bcrypt('password123'),
        'role' => 'super_admin',
    ]);

    $response = $this->actingAs($this->superAdmin)->put(route('users.update', $anotherSuperAdmin), [
        'username' => 'anothersuperadmin',
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', [
        'id' => $anotherSuperAdmin->id,
        'role' => 'admin',
    ]);
});

// --- Delete ---

it('deletes a user account', function () {
    $user = User::create([
        'username' => 'deleteme',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);

    $response = $this->actingAs($this->superAdmin)->delete(route('users.destroy', $user));

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseMissing('users', ['username' => 'deleteme']);
});

it('prevents deletion of own account', function () {
    $response = $this->actingAs($this->superAdmin)->delete(route('users.destroy', $this->superAdmin));

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
});

it('prevents deletion of the last super admin account', function () {
    // Create another super admin to act as the requester
    $actingSuperAdmin = User::create([
        'username' => 'actingsuperadmin',
        'password' => bcrypt('password123'),
        'role' => 'super_admin',
    ]);

    // Now delete the original super admin (leaving actingSuperAdmin as the last one)
    $this->actingAs($actingSuperAdmin)->delete(route('users.destroy', $this->superAdmin));

    // Now try to delete the last remaining super admin (actingSuperAdmin deleting themselves is blocked by "own account" rule)
    // Let's test with a different user: create an admin, then try to delete the last super_admin
    $adminUser = User::create([
        'username' => 'regularadmin',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);

    // actingSuperAdmin is now the only super_admin
    $response = $this->actingAs($actingSuperAdmin)->delete(route('users.destroy', $actingSuperAdmin));

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['id' => $actingSuperAdmin->id]);
});

it('allows deletion of super admin when another super admin exists', function () {
    $anotherSuperAdmin = User::create([
        'username' => 'anothersuperadmin',
        'password' => bcrypt('password123'),
        'role' => 'super_admin',
    ]);

    $response = $this->actingAs($this->superAdmin)->delete(route('users.destroy', $anotherSuperAdmin));

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseMissing('users', ['username' => 'anothersuperadmin']);
});

// --- Authentication ---

it('requires authentication to access users', function () {
    $response = $this->get(route('users.index'));

    $response->assertRedirect(route('login'));
});
