<?php

use App\DTOs\PageCheckResult;
use App\DTOs\SiteCheckResult;
use App\Enums\ErrorType;
use App\Enums\SiteStatus;
use App\Enums\TriggerType;
use App\Livewire\Dashboard;
use App\Models\CheckingCycle;
use App\Models\CheckResult;
use App\Models\DowntimeHistory;
use App\Models\HourlyStat;
use App\Models\Site;
use App\Models\TelegramTarget;
use App\Models\User;
use App\Services\HealthCheckServiceInterface;
use App\Services\MonitoringService;
use App\Services\MonitoringServiceInterface;
use App\Services\NotificationService;
use App\Services\NotificationServiceInterface;
use App\Services\StatsAggregationService;
use App\Services\TelegramBotServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Livewire;

function downSiteWithOutage(array $attributes = []): Site
{
    $site = Site::factory()->totallyDown()->create(array_merge([
        'consecutive_down_count' => 5,
        'notification_sent' => true,
    ], $attributes));

    DowntimeHistory::create([
        'site_id' => $site->id,
        'started_at' => $site->first_down_at,
        'affected_pages' => ['/'],
        'status' => 'active',
    ]);

    return $site;
}

function checkResultFor(Site $site, int $httpCode): SiteCheckResult
{
    return new SiteCheckResult(
        siteId: $site->id,
        siteName: $site->name,
        pageResults: collect([
            new PageCheckResult(
                pageId: 1,
                siteId: $site->id,
                url: $site->base_url . '/',
                httpCode: $httpCode,
                responseTimeMs: $httpCode === 0 ? 0 : 100.0,
                errorType: $httpCode === 0 ? ErrorType::ConnectionFailure : ErrorType::None,
            ),
        ]),
    );
}

function recordCheck(Site $site, int $httpCode, Carbon $at): void
{
    $page = $site->pages()->firstOrCreate(['path' => '/']);

    $cycle = CheckingCycle::create([
        'started_at' => $at,
        'completed_at' => $at,
        'trigger_type' => TriggerType::Automatic,
        'sites_checked' => 1,
        'sites_down' => $httpCode === 200 ? 0 : 1,
    ]);

    CheckResult::create([
        'cycle_id' => $cycle->id,
        'site_id' => $site->id,
        'page_id' => $page->id,
        'url' => $site->base_url . '/',
        'http_code' => $httpCode,
        'response_time_ms' => 100,
        'error_type' => $httpCode === 200 ? ErrorType::None : ErrorType::ConnectionFailure,
        'checked_at' => $at,
    ]);
}

describe('turning monitoring off', function () {
    it('closes the open outage as monitoring stopped and clears the down state', function () {
        $site = downSiteWithOutage(['first_down_at' => now()->subMinutes(30)]);

        app(MonitoringServiceInterface::class)->setMonitored($site, false);

        $record = DowntimeHistory::where('site_id', $site->id)->sole();
        $site->refresh();

        expect($record->status)->toBe('monitoring_stopped');
        expect($record->ended_at)->not->toBeNull();
        expect($record->duration_seconds)->toBe(1800);
        expect($site->is_monitored)->toBeFalse();
        expect($site->status)->toBe(SiteStatus::Up);
        expect($site->consecutive_down_count)->toBe(0);
        expect($site->notification_sent)->toBeFalse();
        expect($site->first_down_at)->toBeNull();
    });

    it('does nothing when the value has not changed', function () {
        $site = downSiteWithOutage();

        app(MonitoringServiceInterface::class)->setMonitored($site, true);

        expect(DowntimeHistory::where('site_id', $site->id)->sole()->status)->toBe('active');
        expect($site->fresh()->status)->toBe(SiteStatus::TotallyDown);
    });

    it('leaves the outage open when the edit form is saved without changing the toggle', function () {
        $site = downSiteWithOutage();
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->put(route('sites.update', $site), [
            'name' => $site->name,
            'category_id' => $site->category_id,
            'base_url' => $site->base_url,
            'responsible_person_id' => $site->responsible_person_id,
            'pages' => ['/'],
            'is_monitored' => '1',
        ])->assertRedirect(route('sites.index'));

        expect(DowntimeHistory::where('site_id', $site->id)->sole()->status)->toBe('active');
        expect($site->fresh()->status)->toBe(SiteStatus::TotallyDown);
    });

    it('closes the outage when the edit form turns the toggle off', function () {
        $site = downSiteWithOutage();
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->put(route('sites.update', $site), [
            'name' => $site->name,
            'category_id' => $site->category_id,
            'base_url' => $site->base_url,
            'responsible_person_id' => $site->responsible_person_id,
            'pages' => ['/'],
            'is_monitored' => '0',
        ])->assertRedirect(route('sites.index'));

        expect(DowntimeHistory::where('site_id', $site->id)->sole()->status)->toBe('monitoring_stopped');
    });

    it('ignores check results for a site turned off while the cycle was running', function () {
        $site = Site::factory()->create();

        $healthCheckService = Mockery::mock(HealthCheckServiceInterface::class);
        $healthCheckService->shouldReceive('checkAllSites')->andReturnUsing(function () use ($site) {
            $site->update(['is_monitored' => false]);

            return collect([checkResultFor($site, 0)]);
        });

        $notificationService = Mockery::mock(NotificationServiceInterface::class);
        $notificationService->shouldReceive('evaluateAndNotify')
            ->once()
            ->withArgs(fn (Collection $results) => $results->isEmpty());

        $statsService = Mockery::mock(StatsAggregationService::class);
        $statsService->shouldReceive('updateAllCurrentStats')->once();

        $this->app->instance(HealthCheckServiceInterface::class, $healthCheckService);
        $this->app->instance(NotificationServiceInterface::class, $notificationService);
        $this->app->instance(StatsAggregationService::class, $statsService);

        app(MonitoringService::class)->executeCycle();

        expect($site->fresh()->status)->toBe(SiteStatus::Up);
        expect(CheckResult::count())->toBe(0);
    });
});

describe('turning monitoring back on', function () {
    beforeEach(function () {
        TelegramTarget::create(['chat_id' => '123456', 'is_active' => true]);

        $this->telegram = Mockery::mock(TelegramBotServiceInterface::class);
        $this->notifications = new NotificationService($this->telegram);
    });

    it('sends no recovery alert when the site is up', function () {
        $site = downSiteWithOutage();
        $monitoring = app(MonitoringServiceInterface::class);

        $monitoring->setMonitored($site, false);
        $monitoring->setMonitored($site->fresh(), true);

        $this->telegram->shouldNotReceive('sendMessage');

        $this->notifications->evaluateAndNotify(collect([checkResultFor($site, 200)]));

        expect(DowntimeHistory::where('site_id', $site->id)->sole()->status)->toBe('monitoring_stopped');
    });

    it('opens a new outage and alerts at the threshold when the site is still down', function () {
        $site = downSiteWithOutage();
        $monitoring = app(MonitoringServiceInterface::class);

        $monitoring->setMonitored($site, false);
        $monitoring->setMonitored($site->fresh(), true);

        $this->telegram->shouldReceive('sendMessage')->once()->andReturn(true);

        foreach (range(1, 3) as $check) {
            $this->notifications->evaluateAndNotify(collect([checkResultFor($site, 0)]));
        }

        expect($site->fresh()->notification_sent)->toBeTrue();
        expect(DowntimeHistory::where('site_id', $site->id)->count())->toBe(2);
        expect(DowntimeHistory::where('site_id', $site->id)->where('status', 'active')->count())->toBe(1);
    });
});

describe('manual refresh', function () {
    it('does nothing for an unmonitored site', function () {
        $site = Site::factory()->create(['is_monitored' => false]);

        $monitoring = Mockery::mock(MonitoringServiceInterface::class)->shouldIgnoreMissing();
        $monitoring->shouldNotReceive('refreshSite');
        $monitoring->shouldReceive('getCycleState')->andReturn(app(MonitoringService::class)->getCycleState());

        $this->app->instance(MonitoringServiceInterface::class, $monitoring);

        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(Dashboard::class)
            ->call('refreshSite', $site->id);
    });
});

describe('downtime figures', function () {
    afterEach(fn () => Carbon::setTestNow());

    it('does not count time after monitoring stopped as downtime', function () {
        Carbon::setTestNow('2026-11-03 10:55:00');

        $site = Site::factory()->create();
        recordCheck($site, 0, Carbon::parse('2026-11-03 10:05:00'));
        recordCheck($site, 200, Carbon::parse('2026-11-03 10:50:00'));

        DowntimeHistory::create([
            'site_id' => $site->id,
            'started_at' => '2026-11-03 10:05:00',
            'ended_at' => '2026-11-03 10:10:00',
            'duration_seconds' => 300,
            'affected_pages' => ['/'],
            'status' => 'monitoring_stopped',
        ]);

        app(StatsAggregationService::class)->updateAllCurrentStats();

        expect((int) HourlyStat::where('site_id', $site->id)->sole()->downtime_seconds)->toBe(300);
    });

    it('stops counting when monitoring stopped and no later check exists', function () {
        Carbon::setTestNow('2026-11-03 10:55:00');

        $site = Site::factory()->create(['is_monitored' => false]);
        recordCheck($site, 0, Carbon::parse('2026-11-03 10:05:00'));

        DowntimeHistory::create([
            'site_id' => $site->id,
            'started_at' => '2026-11-03 10:05:00',
            'ended_at' => '2026-11-03 10:10:00',
            'duration_seconds' => 300,
            'affected_pages' => ['/'],
            'status' => 'monitoring_stopped',
        ]);

        app(StatsAggregationService::class)->updateAllCurrentStats();

        expect((int) HourlyStat::where('site_id', $site->id)->sole()->downtime_seconds)->toBe(300);
    });

    it('keeps monitoring stopped outages separate when history is rebuilt', function () {
        Carbon::setTestNow('2026-11-03 12:00:00');

        $site = Site::factory()->create();
        recordCheck($site, 0, Carbon::parse('2026-11-03 09:00:00'));
        recordCheck($site, 0, Carbon::parse('2026-11-03 11:00:00'));
        recordCheck($site, 200, Carbon::parse('2026-11-03 11:30:00'));

        DowntimeHistory::create([
            'site_id' => $site->id,
            'started_at' => '2026-11-03 09:00:00',
            'ended_at' => '2026-11-03 09:10:00',
            'duration_seconds' => 600,
            'affected_pages' => ['/'],
            'status' => 'monitoring_stopped',
        ]);

        app(StatsAggregationService::class)->backfillAll();

        $records = DowntimeHistory::where('site_id', $site->id)->orderBy('started_at')->get();

        expect($records)->toHaveCount(2);
        expect($records[0]->status)->toBe('monitoring_stopped');
        expect($records[0]->duration_seconds)->toBe(600);
        expect($records[1]->status)->toBe('resolved');
        expect($records[1]->duration_seconds)->toBe(1800);
    });

    it('shows monitoring stopped outages on the downtime page and includes them in totals', function () {
        $site = Site::factory()->create(['name' => 'Paused Site']);

        DowntimeHistory::create([
            'site_id' => $site->id,
            'started_at' => now()->subHour(),
            'ended_at' => now()->subMinutes(30),
            'duration_seconds' => 1800,
            'affected_pages' => ['/'],
            'status' => 'monitoring_stopped',
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('downtime.index', ['status' => 'monitoring_stopped']))
            ->assertOk()
            ->assertSee('Paused Site')
            ->assertSee('Monitoring stopped');

        expect($response->viewData('totalDowntimeSeconds'))->toBe(1800);
    });
});
