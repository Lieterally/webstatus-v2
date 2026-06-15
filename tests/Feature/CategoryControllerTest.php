<?php

use App\Models\Category;
use App\Models\Site;
use App\Models\ITStaff;
use App\Models\User;

beforeEach(function () {
    $this->user = User::create([
        'username' => 'admin',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);
});

it('displays the categories index page', function () {
    Category::create(['name' => 'Academic']);
    Category::create(['name' => 'Administrative']);

    $response = $this->actingAs($this->user)->get(route('categories.index'));

    $response->assertStatus(200);
    $response->assertSee('Academic');
    $response->assertSee('Administrative');
});

it('displays the create category form', function () {
    $response = $this->actingAs($this->user)->get(route('categories.create'));

    $response->assertStatus(200);
    $response->assertSee('Create Category');
});

it('stores a new category with valid name', function () {
    $response = $this->actingAs($this->user)->post(route('categories.store'), [
        'name' => 'Academic',
    ]);

    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseHas('categories', ['name' => 'Academic']);
});

it('validates name is required when storing', function () {
    $response = $this->actingAs($this->user)->post(route('categories.store'), [
        'name' => '',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseCount('categories', 0);
});

it('validates name is unique when storing', function () {
    Category::create(['name' => 'Academic']);

    $response = $this->actingAs($this->user)->post(route('categories.store'), [
        'name' => 'Academic',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseCount('categories', 1);
});

it('displays the edit category form', function () {
    $category = Category::create(['name' => 'Academic']);

    $response = $this->actingAs($this->user)->get(route('categories.edit', $category));

    $response->assertStatus(200);
    $response->assertSee('Edit Category');
    $response->assertSee('Academic');
});

it('updates a category with valid name', function () {
    $category = Category::create(['name' => 'Academic']);

    $response = $this->actingAs($this->user)->put(route('categories.update', $category), [
        'name' => 'Research',
    ]);

    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseHas('categories', ['name' => 'Research']);
    $this->assertDatabaseMissing('categories', ['name' => 'Academic']);
});

it('validates name is unique when updating (excluding self)', function () {
    $category1 = Category::create(['name' => 'Academic']);
    $category2 = Category::create(['name' => 'Research']);

    $response = $this->actingAs($this->user)->put(route('categories.update', $category2), [
        'name' => 'Academic',
    ]);

    $response->assertSessionHasErrors('name');
});

it('allows updating category with its own name', function () {
    $category = Category::create(['name' => 'Academic']);

    $response = $this->actingAs($this->user)->put(route('categories.update', $category), [
        'name' => 'Academic',
    ]);

    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseHas('categories', ['name' => 'Academic']);
});

it('deletes a category without associated sites', function () {
    $category = Category::create(['name' => 'Academic']);

    $response = $this->actingAs($this->user)->delete(route('categories.destroy', $category));

    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseMissing('categories', ['name' => 'Academic']);
});

it('prevents deletion of a category with associated sites', function () {
    $category = Category::create(['name' => 'Academic']);
    $staff = ITStaff::create(['name' => 'John', 'position' => 'Admin']);

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

    $response = $this->actingAs($this->user)->delete(route('categories.destroy', $category));

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('categories', ['name' => 'Academic']);
});

it('returns categories as JSON for dropdown list', function () {
    Category::create(['name' => 'Zebra']);
    Category::create(['name' => 'Academic']);

    $response = $this->actingAs($this->user)->getJson(route('categories.list'));

    $response->assertStatus(200);
    $response->assertJsonCount(2);
    // Should be ordered alphabetically
    $response->assertJsonPath('0.name', 'Academic');
    $response->assertJsonPath('1.name', 'Zebra');
});

it('requires authentication to access categories', function () {
    $response = $this->get(route('categories.index'));

    $response->assertRedirect(route('login'));
});
