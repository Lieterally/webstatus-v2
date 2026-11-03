<?php

use App\Models\Site;
use App\Services\HealthCheckServiceInterface;
use App\Services\MonitoringService;
use App\Services\NotificationServiceInterface;
use App\Services\StatsAggregationService;
use Illuminate\Support\Collection;

it('only checks monitored sites during a cycle', function () {
    $monitored = Site::factory()->create();
    $unmonitored = Site::factory()->create(['is_monitored' => false]);

    $healthCheckService = Mockery::mock(HealthCheckServiceInterface::class);
    $healthCheckService->shouldReceive('checkAllSites')
        ->once()
        ->withArgs(fn (Collection $sites) => $sites->pluck('id')->all() === [$monitored->id])
        ->andReturn(collect());

    $notificationService = Mockery::mock(NotificationServiceInterface::class);
    $notificationService->shouldReceive('evaluateAndNotify')->once();

    $statsService = Mockery::mock(StatsAggregationService::class);
    $statsService->shouldReceive('updateAllCurrentStats')->once();

    $this->app->instance(HealthCheckServiceInterface::class, $healthCheckService);
    $this->app->instance(NotificationServiceInterface::class, $notificationService);
    $this->app->instance(StatsAggregationService::class, $statsService);

    $result = app(MonitoringService::class)->executeCycle();

    expect($result->sitesChecked)->toBe(1);
    expect($unmonitored->fresh()->is_monitored)->toBeFalse();
});

it('monitors new sites by default', function () {
    $site = Site::factory()->create();

    expect($site->fresh()->is_monitored)->toBeTrue();
});
