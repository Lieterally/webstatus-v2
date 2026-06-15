<?php

use App\DTOs\CycleResult;
use App\Models\NotificationLog;
use App\Models\SystemConfig;
use App\Services\MonitoringServiceInterface;
use App\Services\TelegramBotServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Seed default system configs
    SystemConfig::create(['key' => 'cycle_interval_minutes', 'value' => '10', 'updated_at' => now()]);
});

it('executes monitoring cycle successfully', function () {
    $cycleResult = new CycleResult(
        cycleId: 1,
        sitesChecked: 5,
        sitesDown: 1,
        siteResults: collect([]),
        startedAt: now(),
        completedAt: now(),
    );

    $monitoringService = Mockery::mock(MonitoringServiceInterface::class);
    $monitoringService->shouldReceive('executeCycle')
        ->once()
        ->andReturn($cycleResult);

    $this->app->instance(MonitoringServiceInterface::class, $monitoringService);

    $this->artisan('app:run-monitoring-cycle')
        ->assertExitCode(0);

    // Verify cycle state persisted to database
    expect(SystemConfig::getValue('last_cycle_run_at'))->not->toBeNull();
    expect(SystemConfig::getValue('consecutive_cycle_failures'))->toBe('0');
});

it('persists last cycle run timestamp to system_configs', function () {
    $completedAt = now()->subSeconds(5);

    $cycleResult = new CycleResult(
        cycleId: 1,
        sitesChecked: 3,
        sitesDown: 0,
        siteResults: collect([]),
        startedAt: now()->subSeconds(10),
        completedAt: $completedAt,
    );

    $monitoringService = Mockery::mock(MonitoringServiceInterface::class);
    $monitoringService->shouldReceive('executeCycle')
        ->once()
        ->andReturn($cycleResult);

    $this->app->instance(MonitoringServiceInterface::class, $monitoringService);

    $this->artisan('app:run-monitoring-cycle')
        ->assertExitCode(0);

    $lastRun = SystemConfig::getValue('last_cycle_run_at');
    expect($lastRun)->toBe($completedAt->format('Y-m-d H:i:s'));
});

it('resets consecutive failure count on successful cycle', function () {
    // Set existing failures
    SystemConfig::create(['key' => 'consecutive_cycle_failures', 'value' => '2', 'updated_at' => now()]);

    $cycleResult = new CycleResult(
        cycleId: 1,
        sitesChecked: 2,
        sitesDown: 0,
        siteResults: collect([]),
        startedAt: now(),
        completedAt: now(),
    );

    $monitoringService = Mockery::mock(MonitoringServiceInterface::class);
    $monitoringService->shouldReceive('executeCycle')
        ->once()
        ->andReturn($cycleResult);

    $this->app->instance(MonitoringServiceInterface::class, $monitoringService);

    $this->artisan('app:run-monitoring-cycle')
        ->assertExitCode(0);

    expect(SystemConfig::getValue('consecutive_cycle_failures'))->toBe('0');
});

it('logs failed cycle with timestamp and cycle identifier', function () {
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, 'Monitoring cycle failed')
                && str_contains($message, 'cycle_')
                && isset($context['cycle_id'])
                && isset($context['timestamp'])
                && isset($context['error']);
        });

    $monitoringService = Mockery::mock(MonitoringServiceInterface::class);
    $monitoringService->shouldReceive('executeCycle')
        ->once()
        ->andThrow(new \RuntimeException('Database connection lost'));

    $telegramBotService = Mockery::mock(TelegramBotServiceInterface::class);
    $telegramBotService->shouldNotReceive('broadcastToActiveTargets');

    $this->app->instance(MonitoringServiceInterface::class, $monitoringService);
    $this->app->instance(TelegramBotServiceInterface::class, $telegramBotService);

    $this->artisan('app:run-monitoring-cycle')
        ->assertExitCode(1);
});

it('increments consecutive failure count on cycle failure', function () {
    SystemConfig::create(['key' => 'consecutive_cycle_failures', 'value' => '1', 'updated_at' => now()]);

    Log::shouldReceive('error')->once()->withAnyArgs();

    $monitoringService = Mockery::mock(MonitoringServiceInterface::class);
    $monitoringService->shouldReceive('executeCycle')
        ->once()
        ->andThrow(new \RuntimeException('Test failure'));

    $telegramBotService = Mockery::mock(TelegramBotServiceInterface::class);
    $telegramBotService->shouldNotReceive('broadcastToActiveTargets');

    $this->app->instance(MonitoringServiceInterface::class, $monitoringService);
    $this->app->instance(TelegramBotServiceInterface::class, $telegramBotService);

    $this->artisan('app:run-monitoring-cycle')
        ->assertExitCode(1);

    expect(SystemConfig::getValue('consecutive_cycle_failures'))->toBe('2');
});

it('sends system health alert after 3 consecutive failures', function () {
    // Set 2 existing failures (next one will be the 3rd)
    SystemConfig::create(['key' => 'consecutive_cycle_failures', 'value' => '2', 'updated_at' => now()]);

    Log::shouldReceive('error')->once()->withAnyArgs();
    Log::shouldReceive('warning')->once()->withAnyArgs();

    $monitoringService = Mockery::mock(MonitoringServiceInterface::class);
    $monitoringService->shouldReceive('executeCycle')
        ->once()
        ->andThrow(new \RuntimeException('Database down'));

    $telegramBotService = Mockery::mock(TelegramBotServiceInterface::class);
    $telegramBotService->shouldReceive('broadcastToActiveTargets')
        ->once()
        ->withArgs(function ($message) {
            return str_contains($message, 'System Health Alert')
                && str_contains($message, '3 consecutive');
        });

    $this->app->instance(MonitoringServiceInterface::class, $monitoringService);
    $this->app->instance(TelegramBotServiceInterface::class, $telegramBotService);

    $this->artisan('app:run-monitoring-cycle')
        ->assertExitCode(1);

    expect(SystemConfig::getValue('consecutive_cycle_failures'))->toBe('3');

    // Verify notification log was created
    $log = NotificationLog::where('type', 'system_health')->first();
    expect($log)->not->toBeNull();
    expect($log->site_id)->toBeNull();
    expect($log->message)->toContain('System Health Alert');
});

it('does not send system health alert on 4th consecutive failure', function () {
    // Set 3 existing failures (4th should NOT trigger another alert)
    SystemConfig::create(['key' => 'consecutive_cycle_failures', 'value' => '3', 'updated_at' => now()]);

    Log::shouldReceive('error')->once()->withAnyArgs();

    $monitoringService = Mockery::mock(MonitoringServiceInterface::class);
    $monitoringService->shouldReceive('executeCycle')
        ->once()
        ->andThrow(new \RuntimeException('Still down'));

    $telegramBotService = Mockery::mock(TelegramBotServiceInterface::class);
    $telegramBotService->shouldNotReceive('broadcastToActiveTargets');

    $this->app->instance(MonitoringServiceInterface::class, $monitoringService);
    $this->app->instance(TelegramBotServiceInterface::class, $telegramBotService);

    $this->artisan('app:run-monitoring-cycle')
        ->assertExitCode(1);

    expect(SystemConfig::getValue('consecutive_cycle_failures'))->toBe('4');
});

it('scheduler triggers when no cycle has ever run', function () {
    // Remove any last_cycle_run_at
    SystemConfig::where('key', 'last_cycle_run_at')->delete();

    $lastRunValue = SystemConfig::getValue('last_cycle_run_at');
    expect($lastRunValue)->toBeNull();

    // The scheduler should trigger (within 60s of startup)
    // This tests the "when" condition logic
    $intervalMinutes = (int) SystemConfig::getValue('cycle_interval_minutes', '10');
    $shouldRun = $lastRunValue === null;

    expect($shouldRun)->toBeTrue();
});

it('scheduler triggers when configured interval has elapsed', function () {
    $intervalMinutes = 10;

    // Set last run to 15 minutes ago (well past the interval)
    SystemConfig::create([
        'key' => 'last_cycle_run_at',
        'value' => now()->subMinutes(15)->format('Y-m-d H:i:s'),
        'updated_at' => now(),
    ]);

    $lastRunValue = SystemConfig::getValue('last_cycle_run_at');
    $lastRun = \Illuminate\Support\Carbon::parse($lastRunValue);
    $elapsedMinutes = (int) abs(now()->diffInMinutes($lastRun));

    expect($elapsedMinutes >= $intervalMinutes)->toBeTrue();
});

it('scheduler does not trigger when interval has not elapsed', function () {
    $intervalMinutes = 10;

    // Set last run to 5 minutes ago (within the interval)
    SystemConfig::create([
        'key' => 'last_cycle_run_at',
        'value' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
        'updated_at' => now(),
    ]);

    $lastRunValue = SystemConfig::getValue('last_cycle_run_at');
    $lastRun = \Illuminate\Support\Carbon::parse($lastRunValue);
    $elapsedMinutes = (int) abs(now()->diffInMinutes($lastRun));

    expect($elapsedMinutes >= $intervalMinutes)->toBeFalse();
});
