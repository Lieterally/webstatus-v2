<?php

use App\Enums\SiteStatus;
use App\Livewire\Dashboard;
use App\Models\Site;
use App\Models\SystemConfig;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('renders dashboard component with summary cards', function () {
    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSee('Total Sites')
        ->assertSee('Sites Down')
        ->assertSee('Sites Up')
        ->assertSee('Last Cycle');
});

it('displays correct total site count', function () {
    Site::factory()->count(5)->create(['status' => SiteStatus::Up]);

    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSet('totalSites', 5)
        ->assertSet('sitesUp', 5)
        ->assertSet('sitesDown', 0);
});

it('displays correct down site count', function () {
    Site::factory()->count(3)->create(['status' => SiteStatus::Up]);
    Site::factory()->count(2)->create(['status' => SiteStatus::TotallyDown]);
    Site::factory()->count(1)->create(['status' => SiteStatus::PartiallyDown]);

    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSet('totalSites', 6)
        ->assertSet('sitesUp', 3)
        ->assertSet('sitesDown', 3);
});

it('displays "No data yet" when no cycle has completed', function () {
    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSet('lastCycleDatetime', null)
        ->assertSee('No data yet');
});

it('displays last cycle datetime when a cycle has completed', function () {
    SystemConfig::updateOrCreate(
        ['key' => 'last_cycle_completed_at'],
        ['value' => '2025-01-15 10:30:45']
    );

    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSet('lastCycleDatetime', '2025-01-15 10:30:45');
});

it('shows countdown seconds from cycle state', function () {
    SystemConfig::updateOrCreate(
        ['key' => 'cycle_interval_minutes'],
        ['value' => '10']
    );

    $component = Livewire::actingAs($this->admin)
        ->test(Dashboard::class);

    // Without a last cycle, countdown should be the full interval (10 * 60 = 600)
    $component->assertSet('countdownSeconds', 600);
});

it('shows zero counts when no sites exist', function () {
    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSet('totalSites', 0)
        ->assertSet('sitesUp', 0)
        ->assertSet('sitesDown', 0);
});

it('refreshes data on poll', function () {
    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->assertSet('totalSites', 0)
        ->call('poll')
        ->assertSet('totalSites', 0);

    // Add a site and poll again
    Site::factory()->create(['status' => SiteStatus::Up]);

    Livewire::actingAs($this->admin)
        ->test(Dashboard::class)
        ->call('poll')
        ->assertSet('totalSites', 1);
});
