<?php

use App\Models\Category;
use App\Models\DailyStat;
use App\Models\DowntimeHistory;
use App\Models\ITStaff;
use App\Models\Site;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\PublicStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

function setPublicConfig(string $key, string $value): void
{
    SystemConfig::updateOrCreate(['key' => $key], ['value' => $value]);
}

function publicSnapshot(): array
{
    return app(PublicStatusService::class)->build(CarbonImmutable::now());
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-13 12:00:00'));
    setPublicConfig('is_monitoring', '1');
    setPublicConfig('cycle_interval_minutes', '10');
    setPublicConfig('last_cycle_completed_at', now()->subMinutes(5)->format('Y-m-d H:i:s'));
});

describe('which sites appear', function () {
    it('lists only sites that are public and monitored', function () {
        Site::factory()->create(['name' => 'Public site', 'is_public' => true]);
        Site::factory()->create(['name' => 'Private site', 'is_public' => false]);
        Site::factory()->create(['name' => 'Paused public site', 'is_public' => true, 'is_monitored' => false]);

        $names = collect(publicSnapshot()['categories'])->flatMap(fn ($c) => collect($c['sites'])->pluck('name'));

        expect($names->all())->toBe(['Public site']);
    });

    it('uses the public name when set and groups sites by category', function () {
        $academic = Category::factory()->create(['name' => 'Akademik']);
        Site::factory()->create(['name' => 'siakad-prod', 'public_name' => 'SIAKAD', 'is_public' => true, 'category_id' => $academic->id]);

        $category = publicSnapshot()['categories'][0];

        expect($category['name'])->toBe('Akademik')
            ->and($category['sites'][0]['name'])->toBe('SIAKAD');
    });

    it('never includes private details', function () {
        $staff = ITStaff::factory()->create(['name' => 'Responsible Person Name']);
        $site = Site::factory()->create(['is_public' => true, 'responsible_person_id' => $staff->id, 'avg_response_time' => 1234.5]);
        $site->pages()->create(['path' => '/secret-admin-path']);
        DowntimeHistory::create([
            'site_id' => $site->id, 'started_at' => now()->subHour(), 'status' => 'active',
            'affected_pages' => ['/secret-admin-path'],
        ]);

        $json = json_encode(publicSnapshot());

        expect($json)->not->toContain('Responsible Person Name')
            ->and($json)->not->toContain('secret-admin-path')
            ->and($json)->not->toContain('1234.5')
            ->and($json)->not->toContain('http_code');
    });
});

describe('uptime', function () {
    it('works out each day from downtime seconds and marks days without checks', function () {
        $site = Site::factory()->create(['is_public' => true]);
        $stat = fn (string $date, int $down) => DailyStat::create([
            'site_id' => $site->id, 'period_start' => $date, 'downtime_seconds' => $down,
            'checks_count' => 100, 'avg_response_time_ms' => 0,
        ]);
        $stat('2026-11-10', 0);      // 100%
        $stat('2026-11-11', 864);    // 99%
        $stat('2026-11-12', 8640);   // 90%

        $row = publicSnapshot()['categories'][0]['sites'][0];
        $days = collect($row['days'])->keyBy('date');

        expect($row['days'])->toHaveCount(PublicStatusService::HISTORY_DAYS)
            ->and($days['2026-11-10'])->toMatchArray(['uptime' => 100.0, 'tone' => 'ok'])
            ->and($days['2026-11-11'])->toMatchArray(['uptime' => 99.0, 'tone' => 'minor'])
            ->and($days['2026-11-12'])->toMatchArray(['uptime' => 90.0, 'tone' => 'major'])
            ->and($days['2026-11-09'])->toMatchArray(['uptime' => null, 'tone' => 'none'])
            ->and($row['uptime'])->toBe(round(100 * (1 - 9504 / (3 * 86400)), 2));
    });

    it('measures today against the hours elapsed so far', function () {
        $site = Site::factory()->create(['is_public' => true]);
        DailyStat::create([
            'site_id' => $site->id, 'period_start' => '2026-11-13', 'downtime_seconds' => 4320,
            'checks_count' => 10, 'avg_response_time_ms' => 0,
        ]);

        $today = collect(publicSnapshot()['categories'][0]['sites'][0]['days'])->last();

        // 12 hours elapsed, 1.2 hours down = 90%
        expect($today['uptime'])->toBe(90.0);
    });

    it('counts outages that started on each day', function () {
        $site = Site::factory()->create(['is_public' => true]);
        foreach (['2026-11-12 08:00:00', '2026-11-12 15:00:00'] as $start) {
            DowntimeHistory::create(['site_id' => $site->id, 'started_at' => $start, 'ended_at' => $start, 'status' => 'resolved', 'duration_seconds' => 60]);
        }

        $day = collect(publicSnapshot()['categories'][0]['sites'][0]['days'])->firstWhere('date', '2026-11-12');

        expect($day['outages'])->toBe(2);
    });
});

describe('overall state', function () {
    it('is operational when every public site is up', function () {
        Site::factory()->up()->create(['is_public' => true]);

        expect(publicSnapshot()['state'])->toBe('operational');
    });

    it('is degraded when a public site is partially down', function () {
        Site::factory()->up()->create(['is_public' => true]);
        Site::factory()->partiallyDown()->create(['is_public' => true]);

        expect(publicSnapshot()['state'])->toBe('degraded');
    });

    it('is down when a public site is totally down, ignoring private sites', function () {
        Site::factory()->partiallyDown()->create(['is_public' => true]);
        Site::factory()->totallyDown()->create(['is_public' => true]);
        Site::factory()->totallyDown()->create(['is_public' => false]);

        $snapshot = publicSnapshot();

        expect($snapshot['state'])->toBe('down')
            ->and($snapshot['counts'])->toBe(['sites' => 2, 'down' => 1, 'degraded' => 1]);
    });

    it('is paused when monitoring is off, and site statuses become unknown', function () {
        Site::factory()->totallyDown()->create(['is_public' => true]);
        setPublicConfig('is_monitoring', '0');

        $snapshot = publicSnapshot();

        expect($snapshot['state'])->toBe('paused')
            ->and($snapshot['categories'][0]['sites'][0]['status'])->toBe('unknown');
    });

    it('is stale when the last cycle is older than three intervals', function () {
        Site::factory()->up()->create(['is_public' => true]);
        setPublicConfig('last_cycle_completed_at', now()->subMinutes(31)->format('Y-m-d H:i:s'));

        $snapshot = publicSnapshot();

        expect($snapshot['state'])->toBe('stale')
            ->and($snapshot['categories'][0]['sites'][0]['status'])->toBe('unknown');
    });

    it('is empty when no site is public', function () {
        Site::factory()->create(['is_public' => false]);

        expect(publicSnapshot()['state'])->toBe('empty');
    });
});

describe('incidents', function () {
    it('lists ongoing outages first, then the last 14 days, with page counts only', function () {
        $site = Site::factory()->create(['is_public' => true, 'name' => 'Portal']);
        DowntimeHistory::create(['site_id' => $site->id, 'started_at' => '2026-11-12 10:00:00', 'ended_at' => '2026-11-12 10:30:00', 'duration_seconds' => 1800, 'status' => 'resolved', 'affected_pages' => ['/a', '/b']]);
        DowntimeHistory::create(['site_id' => $site->id, 'started_at' => '2026-11-11 09:00:00', 'ended_at' => '2026-11-11 09:05:00', 'duration_seconds' => 300, 'status' => 'monitoring_stopped']);
        DowntimeHistory::create(['site_id' => $site->id, 'started_at' => '2026-10-01 09:00:00', 'ended_at' => '2026-10-01 09:05:00', 'duration_seconds' => 300, 'status' => 'resolved']);
        DowntimeHistory::create(['site_id' => $site->id, 'started_at' => '2026-11-13 11:50:00', 'status' => 'active', 'affected_pages' => ['/']]);

        $incidents = publicSnapshot()['incidents'];

        expect(collect($incidents)->pluck('state')->all())->toBe(['ongoing', 'resolved', 'monitoring_stopped'])
            ->and($incidents[0]['site'])->toBe('Portal')
            ->and($incidents[1]['pages_count'])->toBe(2)
            ->and($incidents[1]['duration_seconds'])->toBe(1800);
    });
});

describe('cache', function () {
    it('caches the snapshot and forget() clears it', function () {
        Site::factory()->up()->create(['is_public' => true]);
        app(PublicStatusService::class)->snapshot();

        expect(Cache::has(PublicStatusService::CACHE_KEY))->toBeTrue();

        PublicStatusService::forget();

        expect(Cache::has(PublicStatusService::CACHE_KEY))->toBeFalse();
    });
});

describe('site form', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['role' => 'admin']);
    });

    it('saves the public fields when a site is created', function () {
        $this->actingAs($this->admin)->post(route('sites.store'), [
            'name' => 'Library', 'category_id' => Category::factory()->create()->id,
            'base_url' => 'https://library.example.test', 'responsible_person_id' => ITStaff::factory()->create()->id,
            'pages' => ['/'], 'is_public' => '1', 'public_name' => 'Digital library',
        ])->assertRedirect(route('sites.index'));

        expect(Site::firstWhere('name', 'Library'))
            ->is_public->toBeTrue()
            ->public_name->toBe('Digital library');
    });

    it('keeps new sites private unless the box is checked', function () {
        $this->actingAs($this->admin)->post(route('sites.store'), [
            'name' => 'Intranet', 'category_id' => Category::factory()->create()->id,
            'base_url' => 'https://intranet.example.test', 'responsible_person_id' => ITStaff::factory()->create()->id,
            'pages' => ['/'],
        ]);

        expect(Site::firstWhere('name', 'Intranet')->is_public)->toBeFalse();
    });

    it('turns a site public and back off when it is edited', function () {
        $site = Site::factory()->create();
        $site->pages()->create(['path' => '/']);
        $payload = fn (string $public) => [
            'name' => $site->name, 'category_id' => $site->category_id, 'base_url' => $site->base_url,
            'responsible_person_id' => $site->responsible_person_id, 'pages' => ['/'], 'is_public' => $public,
        ];

        $this->actingAs($this->admin)->put(route('sites.update', $site), $payload('1'));
        expect($site->fresh()->is_public)->toBeTrue();

        $this->actingAs($this->admin)->put(route('sites.update', $site), $payload('0'));
        expect($site->fresh()->is_public)->toBeFalse();
    });
});

describe('page and json', function () {
    it('holds only plain values, so the cached snapshot can be read back', function () {
        $site = Site::factory()->create(['is_public' => true]);
        DowntimeHistory::create(['site_id' => $site->id, 'started_at' => now()->subHour(), 'ended_at' => now(), 'status' => 'resolved', 'duration_seconds' => 3600]);

        $snapshot = publicSnapshot();

        expect(json_decode(json_encode($snapshot), true))->toEqual($snapshot);
    });

    it('shows the public page twice in a row, the second time from the cache', function () {
        Site::factory()->totallyDown()->create(['is_public' => true, 'name' => 'Portal']);

        $this->get('/')->assertOk()->assertSee('Some services are down')->assertSee('Portal');
        $this->get('/')->assertOk()->assertSee('Some services are down');
    });

    it('serves status.json with a cache header', function () {
        Site::factory()->create(['is_public' => true]);

        $response = $this->getJson(route('status.json'))->assertOk()->assertJsonPath('state', 'operational');

        expect($response->headers->get('Cache-Control'))->toContain('max-age=60');
    });
});
