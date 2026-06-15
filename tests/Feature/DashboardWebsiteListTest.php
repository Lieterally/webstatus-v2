<?php

use App\Enums\SiteStatus;
use App\Livewire\Dashboard;
use App\Models\Category;
use App\Models\CheckResult;
use App\Models\CheckingCycle;
use App\Models\ITStaff;
use App\Models\Page;
use App\Models\Site;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('displays website list in card view by default', function () {
    $site = Site::factory()->create(['name' => 'Test Website', 'status' => SiteStatus::Up]);

    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSee('Test Website')
        ->assertSee('Card')
        ->assertSee('Table');
});

it('sorts sites by status: totally_down first, then partially_down, then up', function () {
    $category = Category::factory()->create();
    $staff = ITStaff::factory()->create();

    $upSite = Site::factory()->create([
        'name' => 'Alpha Up',
        'status' => SiteStatus::Up,
        'category_id' => $category->id,
        'responsible_person_id' => $staff->id,
    ]);
    $partialSite = Site::factory()->create([
        'name' => 'Beta Partial',
        'status' => SiteStatus::PartiallyDown,
        'category_id' => $category->id,
        'responsible_person_id' => $staff->id,
    ]);
    $downSite = Site::factory()->create([
        'name' => 'Gamma Down',
        'status' => SiteStatus::TotallyDown,
        'category_id' => $category->id,
        'responsible_person_id' => $staff->id,
    ]);

    $component = Livewire::actingAs($this->admin)->test(Dashboard::class);
    $sites = $component->viewData('sites');

    expect($sites[0]->name)->toBe('Gamma Down');
    expect($sites[1]->name)->toBe('Beta Partial');
    expect($sites[2]->name)->toBe('Alpha Up');
});

it('sorts alphabetically within the same status group', function () {
    $category = Category::factory()->create();
    $staff = ITStaff::factory()->create();

    Site::factory()->create([
        'name' => 'Zebra',
        'status' => SiteStatus::Up,
        'category_id' => $category->id,
        'responsible_person_id' => $staff->id,
    ]);
    Site::factory()->create([
        'name' => 'Apple',
        'status' => SiteStatus::Up,
        'category_id' => $category->id,
        'responsible_person_id' => $staff->id,
    ]);
    Site::factory()->create([
        'name' => 'Mango',
        'status' => SiteStatus::Up,
        'category_id' => $category->id,
        'responsible_person_id' => $staff->id,
    ]);

    $component = Livewire::actingAs($this->admin)->test(Dashboard::class);
    $sites = $component->viewData('sites');

    expect($sites[0]->name)->toBe('Apple');
    expect($sites[1]->name)->toBe('Mango');
    expect($sites[2]->name)->toBe('Zebra');
});

it('filters sites by category', function () {
    $category1 = Category::factory()->create(['name' => 'Academic']);
    $category2 = Category::factory()->create(['name' => 'Administrative']);
    $staff = ITStaff::factory()->create();

    Site::factory()->create([
        'name' => 'Academic Site',
        'status' => SiteStatus::Up,
        'category_id' => $category1->id,
        'responsible_person_id' => $staff->id,
    ]);
    Site::factory()->create([
        'name' => 'Admin Site',
        'status' => SiteStatus::Up,
        'category_id' => $category2->id,
        'responsible_person_id' => $staff->id,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->set('categoryFilter', $category1->id);

    $sites = $component->viewData('sites');

    expect($sites)->toHaveCount(1);
    expect($sites[0]->name)->toBe('Academic Site');
});

it('shows all sites when no category filter is applied', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();
    $staff = ITStaff::factory()->create();

    Site::factory()->create(['category_id' => $category1->id, 'responsible_person_id' => $staff->id]);
    Site::factory()->create(['category_id' => $category2->id, 'responsible_person_id' => $staff->id]);

    $component = Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSet('categoryFilter', null);

    $sites = $component->viewData('sites');
    expect($sites)->toHaveCount(2);
});

it('displays "No websites available" when no sites exist', function () {
    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSee('No websites available');
});

it('displays "No websites available" when filter yields no results', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();
    $staff = ITStaff::factory()->create();

    Site::factory()->create(['category_id' => $category1->id, 'responsible_person_id' => $staff->id]);

    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->set('categoryFilter', $category2->id)
        ->assertSee('No websites available');
});

it('displays status badges with correct colors in table view', function () {
    Site::factory()->create(['name' => 'Down Site', 'status' => SiteStatus::TotallyDown]);
    Site::factory()->create(['name' => 'Partial Site', 'status' => SiteStatus::PartiallyDown]);
    Site::factory()->create(['name' => 'Up Site', 'status' => SiteStatus::Up]);

    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSee('Totally Down')
        ->assertSee('Partially Down')
        ->assertSeeHtml('bg-[#DC2626]')
        ->assertSeeHtml('bg-[#F59E0B]')
        ->assertSeeHtml('bg-[#16A34A]');
});

it('shows responsible person in table view', function () {
    $staff = ITStaff::factory()->create(['name' => 'John Doe']);
    Site::factory()->create(['responsible_person_id' => $staff->id]);

    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSee('John Doe');
});

it('displays categories in filter dropdown', function () {
    Category::factory()->create(['name' => 'Academic']);
    Category::factory()->create(['name' => 'Administrative']);

    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSee('Academic')
        ->assertSee('Administrative')
        ->assertSee('All Categories');
});

it('defaults category filter to null (no filter)', function () {
    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSet('categoryFilter', null);
});
