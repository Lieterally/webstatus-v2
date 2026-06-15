<?php

use App\Models\Category;
use App\Models\ITStaff;
use App\Models\Page;
use App\Models\Site;
use App\Models\User;

beforeEach(function () {
    $this->user = User::create([
        'username' => 'admin',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);
    $this->category = Category::create(['name' => 'Academic']);
    $this->staff = ITStaff::create(['name' => 'John Doe', 'position' => 'SysAdmin']);
});

it('displays the sites index page with site data', function () {
    $site = Site::create([
        'name' => 'Test Site',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
    ]);
    $site->pages()->create(['path' => '/']);

    $response = $this->actingAs($this->user)->get(route('sites.index'));

    $response->assertStatus(200);
    $response->assertSee('Test Site');
    $response->assertSee('Academic');
    $response->assertSee('https://example.com');
    $response->assertSee('John Doe');
    $response->assertSee('1'); // page count
});

it('displays the create site form with categories and staff', function () {
    $response = $this->actingAs($this->user)->get(route('sites.create'));

    $response->assertStatus(200);
    $response->assertSee('Create Site');
    $response->assertSee('Academic');
    $response->assertSee('John Doe');
});

it('stores a new site with valid data', function () {
    $response = $this->actingAs($this->user)->post(route('sites.store'), [
        'name' => 'ITK Website',
        'category_id' => $this->category->id,
        'base_url' => 'https://itk.ac.id',
        'description' => 'Main ITK website',
        'responsible_person_id' => $this->staff->id,
        'pages' => ['/', '/profile'],
    ]);

    $response->assertRedirect(route('sites.index'));
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('sites', [
        'name' => 'ITK Website',
        'base_url' => 'https://itk.ac.id',
    ]);
    $this->assertDatabaseCount('pages', 2);
    $this->assertDatabaseHas('pages', ['path' => '/']);
    $this->assertDatabaseHas('pages', ['path' => '/profile']);
});

it('validates name is required', function () {
    $response = $this->actingAs($this->user)->post(route('sites.store'), [
        'name' => '',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
        'pages' => ['/'],
    ]);

    $response->assertSessionHasErrors('name');
});

it('validates name max 100 characters', function () {
    $response = $this->actingAs($this->user)->post(route('sites.store'), [
        'name' => str_repeat('a', 101),
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
        'pages' => ['/'],
    ]);

    $response->assertSessionHasErrors('name');
});

it('validates category exists', function () {
    $response = $this->actingAs($this->user)->post(route('sites.store'), [
        'name' => 'Test Site',
        'category_id' => 9999,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
        'pages' => ['/'],
    ]);

    $response->assertSessionHasErrors('category_id');
});

it('validates base_url must start with http or https', function () {
    $response = $this->actingAs($this->user)->post(route('sites.store'), [
        'name' => 'Test Site',
        'category_id' => $this->category->id,
        'base_url' => 'ftp://example.com',
        'responsible_person_id' => $this->staff->id,
        'pages' => ['/'],
    ]);

    $response->assertSessionHasErrors('base_url');
});

it('validates base_url must be unique', function () {
    Site::create([
        'name' => 'Existing',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('sites.store'), [
        'name' => 'New Site',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
        'pages' => ['/'],
    ]);

    $response->assertSessionHasErrors('base_url');
});

it('validates description max 500 characters', function () {
    $response = $this->actingAs($this->user)->post(route('sites.store'), [
        'name' => 'Test Site',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'description' => str_repeat('a', 501),
        'responsible_person_id' => $this->staff->id,
        'pages' => ['/'],
    ]);

    $response->assertSessionHasErrors('description');
});

it('validates at least one page is required', function () {
    $response = $this->actingAs($this->user)->post(route('sites.store'), [
        'name' => 'Test Site',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
        'pages' => [],
    ]);

    $response->assertSessionHasErrors('pages');
});

it('validates max 50 pages', function () {
    $pages = array_fill(0, 51, '/page');

    $response = $this->actingAs($this->user)->post(route('sites.store'), [
        'name' => 'Test Site',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
        'pages' => $pages,
    ]);

    $response->assertSessionHasErrors('pages');
});

it('validates each page must start with /', function () {
    $response = $this->actingAs($this->user)->post(route('sites.store'), [
        'name' => 'Test Site',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
        'pages' => ['/', 'invalid-path'],
    ]);

    $response->assertSessionHasErrors('pages.1');
});

it('validates responsible person exists', function () {
    $response = $this->actingAs($this->user)->post(route('sites.store'), [
        'name' => 'Test Site',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => 9999,
        'pages' => ['/'],
    ]);

    $response->assertSessionHasErrors('responsible_person_id');
});

it('displays the edit site form', function () {
    $site = Site::create([
        'name' => 'Test Site',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
    ]);
    $site->pages()->create(['path' => '/']);

    $response = $this->actingAs($this->user)->get(route('sites.edit', $site));

    $response->assertStatus(200);
    $response->assertSee('Edit Site');
    $response->assertSee('Test Site');
    $response->assertSee('https://example.com');
});

it('updates a site with valid data', function () {
    $site = Site::create([
        'name' => 'Old Name',
        'category_id' => $this->category->id,
        'base_url' => 'https://old.com',
        'responsible_person_id' => $this->staff->id,
    ]);
    $site->pages()->create(['path' => '/']);

    $response = $this->actingAs($this->user)->put(route('sites.update', $site), [
        'name' => 'New Name',
        'category_id' => $this->category->id,
        'base_url' => 'https://new.com',
        'responsible_person_id' => $this->staff->id,
        'pages' => ['/home', '/about'],
    ]);

    $response->assertRedirect(route('sites.index'));
    $this->assertDatabaseHas('sites', ['name' => 'New Name', 'base_url' => 'https://new.com']);
    $this->assertDatabaseMissing('pages', ['path' => '/']);
    $this->assertDatabaseHas('pages', ['path' => '/home']);
    $this->assertDatabaseHas('pages', ['path' => '/about']);
});

it('allows updating site with its own base_url', function () {
    $site = Site::create([
        'name' => 'Test Site',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
    ]);
    $site->pages()->create(['path' => '/']);

    $response = $this->actingAs($this->user)->put(route('sites.update', $site), [
        'name' => 'Test Site Updated',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
        'pages' => ['/'],
    ]);

    $response->assertRedirect(route('sites.index'));
    $response->assertSessionDoesntHaveErrors();
});

it('deletes a site and its pages', function () {
    $site = Site::create([
        'name' => 'To Delete',
        'category_id' => $this->category->id,
        'base_url' => 'https://delete.com',
        'responsible_person_id' => $this->staff->id,
    ]);
    $site->pages()->create(['path' => '/']);
    $site->pages()->create(['path' => '/about']);

    $response = $this->actingAs($this->user)->delete(route('sites.destroy', $site));

    $response->assertRedirect(route('sites.index'));
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('sites', ['id' => $site->id]);
    $this->assertDatabaseCount('pages', 0);
});

it('requires authentication to access sites', function () {
    $response = $this->get(route('sites.index'));

    $response->assertRedirect(route('login'));
});
