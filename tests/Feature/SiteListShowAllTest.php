<?php

use App\Models\Site;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    foreach (range(1, 15) as $i) {
        Site::factory()->create(['name' => sprintf('Site %02d', $i)]);
    }
});

it('shows 10 websites per page and offers to show all', function () {
    $this->actingAs($this->admin)->get(route('sites.index'))
        ->assertOk()
        ->assertSee('Site 10')
        ->assertDontSee('Site 11')
        ->assertSee('Showing 1–10 of 15 websites')
        ->assertSee('Show all 15')
        ->assertSee('show=all', false);
});

it('lists every website on one page with show=all', function () {
    $this->actingAs($this->admin)->get(route('sites.index', ['show' => 'all']))
        ->assertOk()
        ->assertSee('Site 01')
        ->assertSee('Site 15')
        ->assertSee('Showing all 15 websites')
        ->assertSee('Show 10 per page')
        ->assertDontSee('aria-label="Pagination"', false);
});

it('keeps show=all when filtering', function () {
    $this->actingAs($this->admin)->get(route('sites.index', ['show' => 'all', 'search' => 'Site 1']))
        ->assertOk()
        ->assertSee('<input type="hidden" name="show" value="all">', false)
        ->assertSee('Site 15')
        ->assertDontSee('Site 09')
        ->assertSee('Showing all 6 websites');
});
