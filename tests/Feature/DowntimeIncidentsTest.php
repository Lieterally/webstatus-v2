<?php

use App\Models\DowntimeHistory;
use App\Models\Site;
use App\Models\User;

it('groups overlapping outages into incidents on the downtime page', function () {
    $this->travelTo(now()->setTime(17, 0));
    $admin = User::factory()->create(['role' => 'admin']);
    [$a, $b, $c] = Site::factory()->count(3)->create();

    $outage = fn (Site $site, string $from, string $to) => DowntimeHistory::create([
        'site_id' => $site->id,
        'started_at' => today()->setTimeFromTimeString($from),
        'ended_at' => today()->setTimeFromTimeString($to),
        'duration_seconds' => today()->setTimeFromTimeString($from)->diffInSeconds(today()->setTimeFromTimeString($to)),
        'status' => 'resolved',
    ]);
    $outage($a, '12:04', '12:11');
    $outage($b, '12:05', '12:10');
    $outage($c, '15:00', '15:04');

    $this->actingAs($admin)->get(route('downtime.index'))
        ->assertOk()
        ->assertSee('Incidents (2)')
        ->assertSee('By website (3)')
        ->assertSee('12.04 – 12.11 WITA')
        ->assertSee('2 websites down')
        ->assertSee('Peak: 2 websites.');
});
