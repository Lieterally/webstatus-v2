<?php

use App\Models\Site;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    Site::factory()->create(['name' => 'Alpha monitored public', 'is_monitored' => true, 'is_public' => true]);
    Site::factory()->create(['name' => 'Bravo monitored private', 'is_monitored' => true, 'is_public' => false]);
    Site::factory()->create(['name' => 'Charlie paused public', 'is_monitored' => false, 'is_public' => true]);
    Site::factory()->create(['name' => 'Delta paused private', 'is_monitored' => false, 'is_public' => false]);
});

function listSites(array $query = [])
{
    return test()->actingAs(test()->admin)->get(route('sites.index', $query))->assertOk();
}

it('filters by monitoring', function () {
    listSites(['monitoring' => 'monitored'])
        ->assertSee('Alpha monitored public')->assertSee('Bravo monitored private')
        ->assertDontSee('Charlie paused public')->assertDontSee('Delta paused private');

    listSites(['monitoring' => 'not_monitored'])
        ->assertSee('Charlie paused public')->assertSee('Delta paused private')
        ->assertDontSee('Alpha monitored public');
});

it('filters by public status page', function () {
    listSites(['visibility' => 'public'])
        ->assertSee('Alpha monitored public')->assertSee('Charlie paused public')
        ->assertDontSee('Bravo monitored private')->assertDontSee('Delta paused private');

    listSites(['visibility' => 'private'])
        ->assertSee('Bravo monitored private')->assertSee('Delta paused private')
        ->assertDontSee('Alpha monitored public');
});

it('combines the filters with each other and with search', function () {
    listSites(['monitoring' => 'monitored', 'visibility' => 'private'])
        ->assertSee('Bravo monitored private')
        ->assertSee('Showing all 1 website')
        ->assertDontSee('Alpha monitored public')->assertDontSee('Delta paused private');

    listSites(['visibility' => 'public', 'search' => 'Charlie'])
        ->assertSee('Charlie paused public')->assertDontSee('Alpha monitored public');
});

it('ignores unknown values and shows the selected options', function () {
    listSites(['monitoring' => 'sometimes', 'visibility' => 'secret'])
        ->assertSee('Showing all 4 websites');

    listSites(['monitoring' => 'not_monitored'])
        ->assertSee('<option value="not_monitored" selected', false)
        ->assertSee('Clear filters');
});
