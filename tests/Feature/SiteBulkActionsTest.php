<?php

use App\Models\DowntimeHistory;
use App\Models\Site;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->sites = Site::factory()->count(3)->create();
});

function bulkSites(array $ids, string $action)
{
    return test()->actingAs(test()->admin)
        ->from(route('sites.index'))
        ->post(route('sites.bulk'), ['action' => $action, 'ids' => $ids]);
}

it('deletes only the selected websites', function () {
    [$a, $b, $keep] = $this->sites;

    bulkSites([$a->id, $b->id], 'delete')
        ->assertRedirect(route('sites.index'))
        ->assertSessionHas('success', 'Deleted 2 websites. They are left out of future checking cycles.');

    expect(Site::pluck('id')->all())->toBe([$keep->id]);
});

it('turns monitoring off and closes open outages the same way as a single toggle', function () {
    [$a, $b] = $this->sites;
    $outage = DowntimeHistory::create(['site_id' => $a->id, 'started_at' => now()->subHour(), 'status' => 'active']);

    bulkSites([$a->id, $b->id], 'monitor_off')->assertSessionHas('success');

    expect($a->fresh()->is_monitored)->toBeFalse()
        ->and($b->fresh()->is_monitored)->toBeFalse()
        ->and($this->sites[2]->fresh()->is_monitored)->toBeTrue()
        ->and($outage->fresh()->status)->toBe('monitoring_stopped');
});

it('turns monitoring back on', function () {
    $site = $this->sites[0];
    $site->update(['is_monitored' => false]);

    bulkSites([$site->id], 'monitor_on')->assertSessionHas('success', 'Monitoring turned on for 1 website.');

    expect($site->fresh()->is_monitored)->toBeTrue();
});

it('makes websites public and private', function () {
    [$a, $b, $other] = $this->sites;

    bulkSites([$a->id, $b->id], 'make_public')->assertSessionHas('success', '2 websites now show on the public status page.');
    expect([$a->fresh()->is_public, $b->fresh()->is_public, $other->fresh()->is_public])->toBe([true, true, false]);

    bulkSites([$a->id], 'make_private');
    expect($a->fresh()->is_public)->toBeFalse()
        ->and($b->fresh()->is_public)->toBeTrue();
});

it('asks for a selection when none is sent', function () {
    bulkSites([], 'delete')->assertSessionHasErrors(['ids' => 'Select at least one website, then choose an action.']);

    expect(Site::count())->toBe(3);
});

it('rejects unknown actions and websites that no longer exist', function () {
    bulkSites([$this->sites[0]->id], 'archive')->assertSessionHasErrors('action');
    bulkSites([999999], 'delete')->assertSessionHasErrors('ids.0');

    expect(Site::count())->toBe(3);
});

it('is not available to guests', function () {
    $this->post(route('sites.bulk'), ['action' => 'delete', 'ids' => [$this->sites[0]->id]])
        ->assertRedirect(route('login'));

    expect(Site::count())->toBe(3);
});

it('words the message for a single website', function () {
    $site = $this->sites[0];

    bulkSites([$site->id], 'make_public')->assertSessionHas('success', '1 website now shows on the public status page.');
    bulkSites([$site->id], 'make_private')->assertSessionHas('success', '1 website is now hidden from the public status page.');
    bulkSites([$site->id], 'delete')->assertSessionHas('success', 'Deleted 1 website. It is left out of future checking cycles.');
});
