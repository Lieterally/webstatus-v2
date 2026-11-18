<?php

use App\Models\Category;
use App\Models\DowntimeHistory;
use App\Models\Site;
use App\Services\PublicStatusService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-13 12:00:00'));
});

it('groups incidents by month and day, newest first, over the last 12 months', function () {
    $site = Site::factory()->create(['is_public' => true, 'name' => 'Portal']);
    DowntimeHistory::create(['site_id' => $site->id, 'started_at' => '2026-11-13 11:50:00', 'status' => 'active', 'affected_pages' => ['/']]);
    DowntimeHistory::create(['site_id' => $site->id, 'started_at' => '2026-11-12 10:00:00', 'ended_at' => '2026-11-12 10:30:00', 'duration_seconds' => 1800, 'status' => 'resolved']);
    DowntimeHistory::create(['site_id' => $site->id, 'started_at' => '2026-09-01 09:00:00', 'ended_at' => '2026-09-01 09:05:00', 'duration_seconds' => 300, 'status' => 'monitoring_stopped']);
    DowntimeHistory::create(['site_id' => $site->id, 'started_at' => '2025-10-31 09:00:00', 'ended_at' => '2025-10-31 09:05:00', 'duration_seconds' => 300, 'status' => 'resolved']);

    $months = app(PublicStatusService::class)->history(null, CarbonImmutable::now())['months'];

    expect($months)->toHaveCount(12)
        ->and($months[0]['label'])->toBe('November 2026')
        ->and($months[0]['count'])->toBe(2)
        ->and($months[0]['has_ongoing'])->toBeTrue()
        ->and(array_column($months[0]['days'], 'date'))->toBe(['2026-11-13', '2026-11-12'])
        ->and($months[2]['days'][0]['incidents'][0]['state'])->toBe('monitoring_stopped')
        ->and($months[11]['label'])->toBe('December 2025')
        ->and(array_sum(array_column($months, 'count')))->toBe(3);
});

it('filters by category and hides private sites', function () {
    $academic = Category::create(['name' => 'Akademik']);
    $other = Category::create(['name' => 'Layanan']);
    $a = Site::factory()->create(['is_public' => true, 'name' => 'LMS', 'category_id' => $academic->id]);
    $b = Site::factory()->create(['is_public' => true, 'name' => 'Mail', 'category_id' => $other->id]);
    $private = Site::factory()->create(['is_public' => false, 'name' => 'Secret', 'category_id' => $academic->id]);
    foreach ([$a, $b, $private] as $site) {
        DowntimeHistory::create(['site_id' => $site->id, 'started_at' => '2026-11-10 10:00:00', 'ended_at' => '2026-11-10 10:30:00', 'duration_seconds' => 1800, 'status' => 'resolved']);
    }

    $history = app(PublicStatusService::class)->history($academic->id, CarbonImmutable::now());
    $sites = collect($history['months'][0]['days'])->flatMap(fn ($d) => array_column($d['incidents'], 'site'));

    expect($sites->all())->toBe(['LMS'])
        ->and($history['category_id'])->toBe($academic->id)
        ->and(array_column($history['categories'], 'name'))->toBe(['Akademik', 'Layanan'])
        ->and(app(PublicStatusService::class)->history(9999, CarbonImmutable::now())['category_id'])->toBeNull();
});

it('renders the history page without logging in', function () {
    $site = Site::factory()->create(['is_public' => true, 'name' => 'Portal']);
    DowntimeHistory::create(['site_id' => $site->id, 'started_at' => '2026-11-12 10:00:00', 'ended_at' => '2026-11-12 10:30:00', 'duration_seconds' => 1800, 'status' => 'resolved']);

    $this->get('/history?category=abc')
        ->assertOk()
        ->assertSee('Incident history')
        ->assertSee('November 2026')
        ->assertSee('Outage on Portal')
        ->assertSee('back up after 30 min', false);

    $this->get('/')->assertOk()->assertSee(route('history'), false)->assertDontSee('Outage on Portal');
});
