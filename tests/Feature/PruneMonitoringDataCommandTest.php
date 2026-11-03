<?php

use App\Enums\ErrorType;
use App\Enums\TriggerType;
use App\Livewire\Dashboard;
use App\Models\CheckingCycle;
use App\Models\CheckResult;
use App\Models\DailyStat;
use App\Models\DowntimeHistory;
use App\Models\HourlyStat;
use App\Models\Page;
use App\Models\Site;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\DataRetentionService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function pruneTestPage(Site $site, string $path = '/'): Page
{
    return $site->pages()->firstOrCreate(['path' => $path]);
}

function pruneTestCheck(Page $page, int $daysAgo, int $httpCode = 200): CheckResult
{
    $at = Carbon::now()->subDays($daysAgo);

    $cycle = CheckingCycle::create([
        'started_at' => $at,
        'completed_at' => $at,
        'trigger_type' => TriggerType::Automatic,
        'sites_checked' => 1,
        'sites_down' => 0,
    ]);

    return CheckResult::create([
        'cycle_id' => $cycle->id,
        'site_id' => $page->site_id,
        'page_id' => $page->id,
        'http_code' => $httpCode,
        'response_time_ms' => 100,
        'error_type' => ErrorType::None,
        'checked_at' => $at,
    ]);
}

it('deletes results and cycles past the retention period', function () {
    $page = pruneTestPage(Site::factory()->create());

    $expired = pruneTestCheck($page, 40);
    $recent = pruneTestCheck($page, 5);

    $this->artisan('app:prune-monitoring-data')->assertSuccessful();

    expect(CheckResult::find($expired->id))->toBeNull()
        ->and(CheckingCycle::find($expired->cycle_id))->toBeNull()
        ->and(CheckResult::find($recent->id))->not->toBeNull()
        ->and(CheckingCycle::find($recent->cycle_id))->not->toBeNull();
});

it('keeps the newest result and cycle of a page even when expired', function () {
    $page = pruneTestPage(Site::factory()->create(['is_monitored' => false]));

    $older = pruneTestCheck($page, 60);
    $newest = pruneTestCheck($page, 40);

    $this->artisan('app:prune-monitoring-data')->assertSuccessful();

    expect(CheckResult::find($older->id))->toBeNull()
        ->and(CheckResult::find($newest->id))->not->toBeNull()
        ->and(CheckingCycle::find($newest->cycle_id))->not->toBeNull();
});

it('removes results across more than one batch', function () {
    $page = pruneTestPage(Site::factory()->create());
    $cycle = pruneTestCheck($page, 40)->cycle_id;

    $rows = array_fill(0, 5100, [
        'cycle_id' => $cycle,
        'site_id' => $page->site_id,
        'page_id' => $page->id,
        'http_code' => 200,
        'response_time_ms' => 100,
        'error_type' => 'none',
        'checked_at' => Carbon::now()->subDays(40),
    ]);
    foreach (array_chunk($rows, 500) as $chunk) {
        CheckResult::insert($chunk);
    }

    $recent = pruneTestCheck($page, 1);

    $this->artisan('app:prune-monitoring-data')->assertSuccessful();

    expect(CheckResult::pluck('id')->all())->toBe([$recent->id]);
});

it('uses the configured retention period and lets --days override it', function () {
    $page = pruneTestPage(Site::factory()->create());
    $twentyDays = pruneTestCheck($page, 20);
    $tenDays = pruneTestCheck($page, 10);
    pruneTestCheck($page, 1);

    SystemConfig::updateOrCreate(['key' => 'check_results_retention_days'], ['value' => '15', 'updated_at' => now()]);

    $this->artisan('app:prune-monitoring-data')->assertSuccessful();
    expect(CheckResult::find($twentyDays->id))->toBeNull()
        ->and(CheckResult::find($tenDays->id))->not->toBeNull();

    $this->artisan('app:prune-monitoring-data', ['--days' => 7])->assertSuccessful();
    expect(CheckResult::find($tenDays->id))->toBeNull();
});

it('refuses a retention period below the minimum', function () {
    $page = pruneTestPage(Site::factory()->create());
    $old = pruneTestCheck($page, 3);
    pruneTestCheck($page, 0);

    $this->artisan('app:prune-monitoring-data', ['--days' => 1])->assertFailed();

    expect(CheckResult::find($old->id))->not->toBeNull();
});

it('deletes nothing on a dry run and reports what a real run would delete', function () {
    $page = pruneTestPage(Site::factory()->create());
    pruneTestCheck($page, 50);
    pruneTestCheck($page, 40);
    pruneTestCheck($page, 1);

    $service = app(DataRetentionService::class);
    $resultsBefore = CheckResult::count();
    $cyclesBefore = CheckingCycle::count();

    $predicted = $service->prune(30, dryRun: true);

    expect(CheckResult::count())->toBe($resultsBefore)
        ->and(CheckingCycle::count())->toBe($cyclesBefore)
        ->and(SystemConfig::getValue(DataRetentionService::PRUNED_BEFORE_KEY))->toBeNull()
        ->and($predicted)->toBe($service->prune(30));
});

it('prunes old hourly stats but keeps daily stats and downtime history', function () {
    $site = Site::factory()->create();

    $old = HourlyStat::create(['site_id' => $site->id, 'period_start' => Carbon::now()->subDays(400)->startOfHour()]);
    $recent = HourlyStat::create(['site_id' => $site->id, 'period_start' => Carbon::now()->subDays(100)->startOfHour()]);
    $daily = DailyStat::create(['site_id' => $site->id, 'period_start' => Carbon::now()->subDays(800)->toDateString()]);
    $downtime = DowntimeHistory::create([
        'site_id' => $site->id,
        'started_at' => Carbon::now()->subDays(800),
        'ended_at' => Carbon::now()->subDays(799),
        'duration_seconds' => 86400,
        'affected_pages' => ['/'],
        'status' => 'resolved',
    ]);

    $this->artisan('app:prune-monitoring-data')->assertSuccessful();

    expect(HourlyStat::find($old->id))->toBeNull()
        ->and(HourlyStat::find($recent->id))->not->toBeNull()
        ->and(DailyStat::find($daily->id))->not->toBeNull()
        ->and(DowntimeHistory::find($downtime->id))->not->toBeNull();
});

it('refuses to backfill once results have been pruned', function () {
    $site = Site::factory()->create();
    $page = pruneTestPage($site);
    pruneTestCheck($page, 40);
    pruneTestCheck($page, 1);
    $downtime = DowntimeHistory::create([
        'site_id' => $site->id,
        'started_at' => Carbon::now()->subDays(100),
        'ended_at' => Carbon::now()->subDays(99),
        'duration_seconds' => 86400,
        'affected_pages' => ['/'],
        'status' => 'resolved',
    ]);

    $this->artisan('app:prune-monitoring-data')->assertSuccessful();
    $this->artisan('stats:aggregate', ['--backfill' => true])->assertFailed();

    expect(DowntimeHistory::find($downtime->id))->not->toBeNull();
});

it('shows the latest result per site and page after the query rewrite', function () {
    $site = Site::factory()->create();
    $home = pruneTestPage($site);
    $about = pruneTestPage($site, '/about');

    pruneTestCheck($home, 2, 500);
    pruneTestCheck($home, 1, 200);
    pruneTestCheck($about, 2, 200);
    $latest = pruneTestCheck($about, 1, 503);

    $component = Livewire::actingAs(User::factory()->create())
        ->test(Dashboard::class)
        ->call('selectSite', $site->id);

    expect($component->viewData('siteMetrics')[$site->id]['latest_http_code'])->toBe($latest->http_code);

    $pages = collect($component->instance()->selectedSiteData()['pageResults'])->keyBy('path');

    expect($pages['/']['http_code'])->toBe(200)
        ->and($pages['/about']['http_code'])->toBe(503);
});
