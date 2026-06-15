<?php

use App\Models\TelegramTarget;
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
});

it('displays the telegram targets index page for super_admin', function () {
    TelegramTarget::create(['chat_id' => '123456789', 'is_active' => true]);
    TelegramTarget::create(['chat_id' => '987654321', 'is_active' => false]);

    $response = $this->actingAs($this->superAdmin)->get(route('telegram-targets.index'));

    $response->assertStatus(200);
    $response->assertSee('123456789');
    $response->assertSee('987654321');
});

it('denies admin access to telegram targets index', function () {
    $response = $this->actingAs($this->admin)->get(route('telegram-targets.index'));

    $response->assertRedirect(route('dashboard'));
});

it('displays the create telegram target form', function () {
    $response = $this->actingAs($this->superAdmin)->get(route('telegram-targets.create'));

    $response->assertStatus(200);
    $response->assertSee('Add Telegram Target');
});

it('stores a new telegram target with valid data', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('telegram-targets.store'), [
        'chat_id' => '123456789',
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('telegram-targets.index'));
    $this->assertDatabaseHas('telegram_targets', [
        'chat_id' => '123456789',
        'is_active' => true,
    ]);
});

it('stores a telegram target with is_active defaulting to 1', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('telegram-targets.store'), [
        'chat_id' => '123456789',
    ]);

    $response->assertRedirect(route('telegram-targets.index'));
    $this->assertDatabaseHas('telegram_targets', [
        'chat_id' => '123456789',
        'is_active' => true,
    ]);
});

it('validates chat_id is required', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('telegram-targets.store'), [
        'chat_id' => '',
        'is_active' => '1',
    ]);

    $response->assertSessionHasErrors('chat_id');
    $this->assertDatabaseCount('telegram_targets', 0);
});

it('validates chat_id must be numeric', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('telegram-targets.store'), [
        'chat_id' => 'abc123',
        'is_active' => '1',
    ]);

    $response->assertSessionHasErrors('chat_id');
    $this->assertDatabaseCount('telegram_targets', 0);
});

it('validates chat_id max 32 characters', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('telegram-targets.store'), [
        'chat_id' => str_repeat('1', 33),
        'is_active' => '1',
    ]);

    $response->assertSessionHasErrors('chat_id');
    $this->assertDatabaseCount('telegram_targets', 0);
});

it('validates chat_id must be unique', function () {
    TelegramTarget::create(['chat_id' => '123456789', 'is_active' => true]);

    $response = $this->actingAs($this->superAdmin)->post(route('telegram-targets.store'), [
        'chat_id' => '123456789',
        'is_active' => '1',
    ]);

    $response->assertSessionHasErrors('chat_id');
    $this->assertDatabaseCount('telegram_targets', 1);
});

it('validates is_active must be 0 or 1', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('telegram-targets.store'), [
        'chat_id' => '123456789',
        'is_active' => '2',
    ]);

    $response->assertSessionHasErrors('is_active');
    $this->assertDatabaseCount('telegram_targets', 0);
});

it('displays the edit telegram target form', function () {
    $target = TelegramTarget::create(['chat_id' => '123456789', 'is_active' => true]);

    $response = $this->actingAs($this->superAdmin)->get(route('telegram-targets.edit', $target));

    $response->assertStatus(200);
    $response->assertSee('Edit Telegram Target');
    $response->assertSee('123456789');
});

it('updates a telegram target with valid data', function () {
    $target = TelegramTarget::create(['chat_id' => '123456789', 'is_active' => true]);

    $response = $this->actingAs($this->superAdmin)->put(route('telegram-targets.update', $target), [
        'chat_id' => '987654321',
        'is_active' => '0',
    ]);

    $response->assertRedirect(route('telegram-targets.index'));
    $this->assertDatabaseHas('telegram_targets', [
        'chat_id' => '987654321',
        'is_active' => false,
    ]);
});

it('validates chat_id unique on update excluding self', function () {
    $target1 = TelegramTarget::create(['chat_id' => '123456789', 'is_active' => true]);
    $target2 = TelegramTarget::create(['chat_id' => '987654321', 'is_active' => true]);

    $response = $this->actingAs($this->superAdmin)->put(route('telegram-targets.update', $target2), [
        'chat_id' => '123456789',
        'is_active' => '1',
    ]);

    $response->assertSessionHasErrors('chat_id');
});

it('allows updating telegram target with its own chat_id', function () {
    $target = TelegramTarget::create(['chat_id' => '123456789', 'is_active' => true]);

    $response = $this->actingAs($this->superAdmin)->put(route('telegram-targets.update', $target), [
        'chat_id' => '123456789',
        'is_active' => '0',
    ]);

    $response->assertRedirect(route('telegram-targets.index'));
    $this->assertDatabaseHas('telegram_targets', [
        'chat_id' => '123456789',
        'is_active' => false,
    ]);
});

it('deletes a telegram target', function () {
    $target = TelegramTarget::create(['chat_id' => '123456789', 'is_active' => true]);

    $response = $this->actingAs($this->superAdmin)->delete(route('telegram-targets.destroy', $target));

    $response->assertRedirect(route('telegram-targets.index'));
    $this->assertDatabaseMissing('telegram_targets', ['chat_id' => '123456789']);
});

it('requires authentication to access telegram targets', function () {
    $response = $this->get(route('telegram-targets.index'));

    $response->assertRedirect('/login');
});

it('denies admin access to create telegram target', function () {
    $response = $this->actingAs($this->admin)->get(route('telegram-targets.create'));

    $response->assertRedirect(route('dashboard'));
});

it('denies admin access to store telegram target', function () {
    $response = $this->actingAs($this->admin)->post(route('telegram-targets.store'), [
        'chat_id' => '123456789',
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseCount('telegram_targets', 0);
});
