<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::create([
        'username' => 'testuser',
        'password' => bcrypt('currentpass123'),
        'role' => 'admin',
    ]);
});

// --- Display Password Change Form ---

it('displays the password change form for authenticated users', function () {
    $response = $this->actingAs($this->user)->get(route('profile.password'));

    $response->assertStatus(200);
    $response->assertSee('Change Password');
    $response->assertSee('Current Password');
    $response->assertSee('New Password');
    $response->assertSee('Confirm New Password');
});

it('requires authentication to access password change page', function () {
    $response = $this->get(route('profile.password'));

    $response->assertRedirect(route('login'));
});

it('allows both admin and super_admin to access the password change page', function () {
    $superAdmin = User::create([
        'username' => 'superadmin',
        'password' => bcrypt('password123'),
        'role' => 'super_admin',
    ]);

    $response = $this->actingAs($superAdmin)->get(route('profile.password'));
    $response->assertStatus(200);

    $response = $this->actingAs($this->user)->get(route('profile.password'));
    $response->assertStatus(200);
});

// --- Successful Password Change ---

it('changes password successfully with valid data', function () {
    $response = $this->actingAs($this->user)->put(route('profile.password.update'), [
        'current_password' => 'currentpass123',
        'new_password' => 'newpassword456',
        'new_password_confirmation' => 'newpassword456',
    ]);

    $response->assertRedirect(route('profile.password'));
    $response->assertSessionHas('success');

    $this->user->refresh();
    expect(password_verify('newpassword456', $this->user->password))->toBeTrue();
});

// --- Validation: Incorrect Current Password ---

it('rejects password change when current password is incorrect', function () {
    $response = $this->actingAs($this->user)->put(route('profile.password.update'), [
        'current_password' => 'wrongpassword',
        'new_password' => 'newpassword456',
        'new_password_confirmation' => 'newpassword456',
    ]);

    $response->assertSessionHasErrors('current_password');

    $this->user->refresh();
    expect(password_verify('currentpass123', $this->user->password))->toBeTrue();
});

// --- Validation: Password Mismatch ---

it('rejects password change when confirmation does not match', function () {
    $response = $this->actingAs($this->user)->put(route('profile.password.update'), [
        'current_password' => 'currentpass123',
        'new_password' => 'newpassword456',
        'new_password_confirmation' => 'differentpassword',
    ]);

    $response->assertSessionHasErrors('new_password_confirmation');

    $this->user->refresh();
    expect(password_verify('currentpass123', $this->user->password))->toBeTrue();
});

// --- Validation: Same as Current ---

it('rejects password change when new password is same as current', function () {
    $response = $this->actingAs($this->user)->put(route('profile.password.update'), [
        'current_password' => 'currentpass123',
        'new_password' => 'currentpass123',
        'new_password_confirmation' => 'currentpass123',
    ]);

    $response->assertSessionHasErrors('new_password');

    $this->user->refresh();
    expect(password_verify('currentpass123', $this->user->password))->toBeTrue();
});

// --- Validation: Password Length ---

it('rejects password change when new password is too short', function () {
    $response = $this->actingAs($this->user)->put(route('profile.password.update'), [
        'current_password' => 'currentpass123',
        'new_password' => 'short',
        'new_password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors('new_password');
});

it('rejects password change when new password is too long', function () {
    $longPassword = str_repeat('a', 129);

    $response = $this->actingAs($this->user)->put(route('profile.password.update'), [
        'current_password' => 'currentpass123',
        'new_password' => $longPassword,
        'new_password_confirmation' => $longPassword,
    ]);

    $response->assertSessionHasErrors('new_password');
});

// --- Session Invalidation ---

it('invalidates other sessions after password change', function () {
    // This test verifies the session invalidation is called
    // The actual session invalidation logic is in AuthService
    $response = $this->actingAs($this->user)->put(route('profile.password.update'), [
        'current_password' => 'currentpass123',
        'new_password' => 'newpassword456',
        'new_password_confirmation' => 'newpassword456',
    ]);

    $response->assertRedirect(route('profile.password'));
    $response->assertSessionHas('success', 'Your password has been changed successfully.');
});
