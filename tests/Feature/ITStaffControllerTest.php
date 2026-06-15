<?php

use App\Models\Category;
use App\Models\ITStaff;
use App\Models\Site;
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

it('displays the IT staff index page for super_admin', function () {
    ITStaff::create(['name' => 'John Doe', 'position' => 'Network Admin']);
    ITStaff::create(['name' => 'Jane Smith', 'position' => 'System Admin']);

    $response = $this->actingAs($this->superAdmin)->get(route('it-staff.index'));

    $response->assertStatus(200);
    $response->assertSee('John Doe');
    $response->assertSee('Jane Smith');
    $response->assertSee('Network Admin');
    $response->assertSee('System Admin');
});

it('denies access to IT staff index for admin role', function () {
    $response = $this->actingAs($this->admin)->get(route('it-staff.index'));

    $response->assertRedirect(route('dashboard'));
});

it('displays the create IT staff form', function () {
    $response = $this->actingAs($this->superAdmin)->get(route('it-staff.create'));

    $response->assertStatus(200);
    $response->assertSee('Add IT Staff');
});

it('stores a new IT staff member with valid data', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('it-staff.store'), [
        'name' => 'John Doe',
        'position' => 'Network Administrator',
    ]);

    $response->assertRedirect(route('it-staff.index'));
    $this->assertDatabaseHas('it_staffs', [
        'name' => 'John Doe',
        'position' => 'Network Administrator',
    ]);
});

it('validates name is required when storing', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('it-staff.store'), [
        'name' => '',
        'position' => 'Admin',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseCount('it_staffs', 0);
});

it('validates position is required when storing', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('it-staff.store'), [
        'name' => 'John Doe',
        'position' => '',
    ]);

    $response->assertSessionHasErrors('position');
    $this->assertDatabaseCount('it_staffs', 0);
});

it('validates name max length is 100 characters', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('it-staff.store'), [
        'name' => str_repeat('a', 101),
        'position' => 'Admin',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseCount('it_staffs', 0);
});

it('validates position max length is 100 characters', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('it-staff.store'), [
        'name' => 'John Doe',
        'position' => str_repeat('a', 101),
    ]);

    $response->assertSessionHasErrors('position');
    $this->assertDatabaseCount('it_staffs', 0);
});

it('accepts name and position at exactly 100 characters', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('it-staff.store'), [
        'name' => str_repeat('a', 100),
        'position' => str_repeat('b', 100),
    ]);

    $response->assertRedirect(route('it-staff.index'));
    $this->assertDatabaseCount('it_staffs', 1);
});

it('displays the edit IT staff form', function () {
    $staff = ITStaff::create(['name' => 'John Doe', 'position' => 'Network Admin']);

    $response = $this->actingAs($this->superAdmin)->get(route('it-staff.edit', $staff));

    $response->assertStatus(200);
    $response->assertSee('Edit IT Staff');
    $response->assertSee('John Doe');
    $response->assertSee('Network Admin');
});

it('updates an IT staff member with valid data', function () {
    $staff = ITStaff::create(['name' => 'John Doe', 'position' => 'Network Admin']);

    $response = $this->actingAs($this->superAdmin)->put(route('it-staff.update', $staff), [
        'name' => 'Jane Smith',
        'position' => 'System Admin',
    ]);

    $response->assertRedirect(route('it-staff.index'));
    $this->assertDatabaseHas('it_staffs', [
        'name' => 'Jane Smith',
        'position' => 'System Admin',
    ]);
    $this->assertDatabaseMissing('it_staffs', ['name' => 'John Doe']);
});

it('validates name is required when updating', function () {
    $staff = ITStaff::create(['name' => 'John Doe', 'position' => 'Network Admin']);

    $response = $this->actingAs($this->superAdmin)->put(route('it-staff.update', $staff), [
        'name' => '',
        'position' => 'Admin',
    ]);

    $response->assertSessionHasErrors('name');
});

it('validates position is required when updating', function () {
    $staff = ITStaff::create(['name' => 'John Doe', 'position' => 'Network Admin']);

    $response = $this->actingAs($this->superAdmin)->put(route('it-staff.update', $staff), [
        'name' => 'John Doe',
        'position' => '',
    ]);

    $response->assertSessionHasErrors('position');
});

it('deletes an IT staff member not assigned to any sites', function () {
    $staff = ITStaff::create(['name' => 'John Doe', 'position' => 'Network Admin']);

    $response = $this->actingAs($this->superAdmin)->delete(route('it-staff.destroy', $staff));

    $response->assertRedirect(route('it-staff.index'));
    $this->assertDatabaseMissing('it_staffs', ['name' => 'John Doe']);
});

it('prevents deletion of IT staff assigned to sites', function () {
    $staff = ITStaff::create(['name' => 'John Doe', 'position' => 'Network Admin']);
    $category = Category::create(['name' => 'Academic']);

    Site::create([
        'name' => 'Test Site',
        'category_id' => $category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $staff->id,
        'status' => 'up',
        'consecutive_down_count' => 0,
        'notification_sent' => false,
        'notification_cycle_counter' => 0,
        'avg_response_time' => 0,
    ]);

    $response = $this->actingAs($this->superAdmin)->delete(route('it-staff.destroy', $staff));

    $response->assertRedirect(route('it-staff.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('it_staffs', ['name' => 'John Doe']);
});

it('requires authentication to access IT staff', function () {
    $response = $this->get(route('it-staff.index'));

    $response->assertRedirect(route('login'));
});
