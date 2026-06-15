<?php

use App\DTOs\PageCheckResult;
use App\DTOs\SiteCheckResult;
use App\Enums\ErrorType;
use App\Enums\SiteStatus;
use App\Models\Category;
use App\Models\ITStaff;
use App\Models\NotificationLog;
use App\Models\Site;
use App\Models\SystemConfig;
use App\Models\TelegramTarget;
use App\Services\NotificationService;
use App\Services\TelegramBotServiceInterface;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->telegramMock = Mockery::mock(TelegramBotServiceInterface::class);
    $this->service = new NotificationService($this->telegramMock);

    // Create a category and IT staff for sites
    $this->category = Category::create(['name' => 'Test Category']);
    $this->staff = ITStaff::create(['name' => 'Test Staff', 'position' => 'Engineer']);

    // Create an active Telegram target
    $this->target = TelegramTarget::create(['chat_id' => '123456', 'is_active' => true]);
});

function createSite(array $attributes = []): Site
{
    return Site::create(array_merge([
        'name' => 'Test Site',
        'category_id' => Category::first()->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => ITStaff::first()->id,
        'status' => SiteStatus::Up,
        'consecutive_down_count' => 0,
        'notification_sent' => false,
        'notification_cycle_counter' => 0,
        'avg_response_time' => 0,
    ], $attributes));
}

function createDownSiteResult(int $siteId, string $siteName = 'Test Site', bool $allDown = true): SiteCheckResult
{
    $pages = collect([
        new PageCheckResult(
            pageId: 1,
            siteId: $siteId,
            url: 'https://example.com/page1',
            httpCode: 0,
            responseTimeMs: 0,
            errorType: ErrorType::ConnectionFailure,
        ),
    ]);

    if (!$allDown) {
        $pages->push(new PageCheckResult(
            pageId: 2,
            siteId: $siteId,
            url: 'https://example.com/page2',
            httpCode: 200,
            responseTimeMs: 150.0,
            errorType: ErrorType::None,
        ));
    }

    return new SiteCheckResult(
        siteId: $siteId,
        siteName: $siteName,
        pageResults: $pages,
    );
}

function createUpSiteResult(int $siteId, string $siteName = 'Test Site'): SiteCheckResult
{
    return new SiteCheckResult(
        siteId: $siteId,
        siteName: $siteName,
        pageResults: collect([
            new PageCheckResult(
                pageId: 1,
                siteId: $siteId,
                url: 'https://example.com/page1',
                httpCode: 200,
                responseTimeMs: 100.0,
                errorType: ErrorType::None,
            ),
        ]),
    );
}

// Req 13.1: Send notification when consecutive_down_count reaches exactly 3
test('sends initial down notification when consecutive_down_count reaches false positive threshold', function () {
    $site = createSite([
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 2, // Will be incremented to 3 in evaluateSite
        'notification_sent' => false,
        'notification_cycle_counter' => 0,
        'first_down_at' => now()->subMinutes(30),
    ]);

    $this->telegramMock->shouldReceive('sendMessage')
        ->once()
        ->with('123456', Mockery::type('string'))
        ->andReturn(true);

    $siteResult = createDownSiteResult($site->id);
    $this->service->evaluateAndNotify(collect([$siteResult]));

    $site->refresh();
    expect($site->notification_sent)->toBeTrue();
    expect($site->consecutive_down_count)->toBe(3);
    expect(NotificationLog::where('site_id', $site->id)->where('type', 'down')->exists())->toBeTrue();
});

// Req 13.2: No notification when count < 3
test('does not send notification when consecutive_down_count is below threshold', function () {
    $site = createSite([
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 1, // Will be incremented to 2
        'notification_sent' => false,
        'notification_cycle_counter' => 0,
        'first_down_at' => now()->subMinutes(10),
    ]);

    $this->telegramMock->shouldNotReceive('sendMessage');

    $siteResult = createDownSiteResult($site->id);
    $this->service->evaluateAndNotify(collect([$siteResult]));

    $site->refresh();
    expect($site->notification_sent)->toBeFalse();
    expect($site->consecutive_down_count)->toBe(2);
});

// Req 13.4/13.5: Repeat notification every threshold cycles from initial notification
test('sends repeated notification at exact multiples of notification_cycle_threshold', function () {
    SystemConfig::create(['key' => 'notification_cycle_threshold', 'value' => '6']);

    $site = createSite([
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 8, // Well above threshold
        'notification_sent' => true,
        'notification_cycle_counter' => 5, // Will be incremented to 6 (a multiple of threshold)
        'first_down_at' => now()->subHours(1),
    ]);

    $this->telegramMock->shouldReceive('sendMessage')
        ->once()
        ->with('123456', Mockery::type('string'))
        ->andReturn(true);

    $siteResult = createDownSiteResult($site->id);
    $this->service->evaluateAndNotify(collect([$siteResult]));

    $site->refresh();
    expect($site->notification_cycle_counter)->toBe(6);
    expect(NotificationLog::where('site_id', $site->id)->where('type', 'down')->exists())->toBeTrue();
});

// Req 13.4/13.5: No repeated notification between thresholds
test('does not send repeated notification between threshold multiples', function () {
    SystemConfig::create(['key' => 'notification_cycle_threshold', 'value' => '6']);

    $site = createSite([
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 7,
        'notification_sent' => true,
        'notification_cycle_counter' => 3, // Will become 4, not a multiple of 6
        'first_down_at' => now()->subHours(1),
    ]);

    $this->telegramMock->shouldNotReceive('sendMessage');

    $siteResult = createDownSiteResult($site->id);
    $this->service->evaluateAndNotify(collect([$siteResult]));

    $site->refresh();
    expect($site->notification_cycle_counter)->toBe(4);
});

// Req 13.6: Status change notification between partially_down and totally_down
test('sends status change notification when status changes between down states above threshold', function () {
    $site = createSite([
        'status' => SiteStatus::PartiallyDown, // Previous status
        'consecutive_down_count' => 5, // Above threshold
        'notification_sent' => true,
        'notification_cycle_counter' => 2,
        'first_down_at' => now()->subHours(1),
    ]);

    // Create a result that will determine totally_down (all pages down)
    $this->telegramMock->shouldReceive('sendMessage')
        ->once()
        ->with('123456', Mockery::type('string'))
        ->andReturn(true);

    $siteResult = createDownSiteResult($site->id, 'Test Site', true); // all pages down = totally_down
    $this->service->evaluateAndNotify(collect([$siteResult]));

    $site->refresh();
    expect($site->status)->toBe(SiteStatus::TotallyDown);
    expect(NotificationLog::where('site_id', $site->id)->where('type', 'status_change')->exists())->toBeTrue();
});

// Req 13.6: No notification when status remains the same
test('does not send notification when status remains the same down state', function () {
    $site = createSite([
        'status' => SiteStatus::TotallyDown, // Same status as result
        'consecutive_down_count' => 5,
        'notification_sent' => true,
        'notification_cycle_counter' => 2, // Not a multiple of threshold
        'first_down_at' => now()->subHours(1),
    ]);

    $this->telegramMock->shouldNotReceive('sendMessage');

    $siteResult = createDownSiteResult($site->id, 'Test Site', true); // totally_down, same as previous
    $this->service->evaluateAndNotify(collect([$siteResult]));
});

// Req 14.1: Recovery notification when transitioning to "up" AND notification_sent was true
test('sends recovery notification when site recovers and notification was previously sent', function () {
    $site = createSite([
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 5,
        'notification_sent' => true,
        'notification_cycle_counter' => 3,
        'first_down_at' => now()->subHours(2),
    ]);

    $this->telegramMock->shouldReceive('sendMessage')
        ->once()
        ->with('123456', Mockery::type('string'))
        ->andReturn(true);

    $siteResult = createUpSiteResult($site->id);
    $this->service->evaluateAndNotify(collect([$siteResult]));

    $site->refresh();
    expect($site->status)->toBe(SiteStatus::Up);
    expect($site->consecutive_down_count)->toBe(0);
    expect($site->notification_sent)->toBeFalse();
    expect($site->notification_cycle_counter)->toBe(0);
    expect(NotificationLog::where('site_id', $site->id)->where('type', 'recovery')->exists())->toBeTrue();
});

// Req 14.3: No recovery notification if site recovered before threshold
test('does not send recovery notification when notification was not previously sent', function () {
    $site = createSite([
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 2, // Below threshold, notification wasn't sent
        'notification_sent' => false,
        'notification_cycle_counter' => 0,
        'first_down_at' => now()->subMinutes(20),
    ]);

    $this->telegramMock->shouldNotReceive('sendMessage');

    $siteResult = createUpSiteResult($site->id);
    $this->service->evaluateAndNotify(collect([$siteResult]));

    $site->refresh();
    expect($site->status)->toBe(SiteStatus::Up);
    expect($site->consecutive_down_count)->toBe(0);
    expect($site->notification_sent)->toBeFalse();
});

// Req 14.5: No recovery notification when transitioning from totally_down to partially_down
test('does not send recovery notification when transitioning between down states', function () {
    $site = createSite([
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 5,
        'notification_sent' => true,
        'notification_cycle_counter' => 2,
        'first_down_at' => now()->subHours(1),
    ]);

    // This should trigger a status change notification, NOT a recovery notification
    $this->telegramMock->shouldReceive('sendMessage')
        ->once()
        ->with('123456', Mockery::type('string'))
        ->andReturn(true);

    // partially_down result (some pages up, some down)
    $siteResult = createDownSiteResult($site->id, 'Test Site', false);
    $this->service->evaluateAndNotify(collect([$siteResult]));

    $site->refresh();
    expect($site->status)->toBe(SiteStatus::PartiallyDown);
    // Should be status_change, not recovery
    expect(NotificationLog::where('site_id', $site->id)->where('type', 'recovery')->exists())->toBeFalse();
    expect(NotificationLog::where('site_id', $site->id)->where('type', 'status_change')->exists())->toBeTrue();
});

// Req 13.7: Retry logic - 3 attempts on failure
test('retries notification delivery up to 3 times on failure', function () {
    $site = createSite([
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 2,
        'notification_sent' => false,
        'notification_cycle_counter' => 0,
        'first_down_at' => now()->subMinutes(30),
    ]);

    // First two attempts fail, third succeeds
    $this->telegramMock->shouldReceive('sendMessage')
        ->times(3)
        ->with('123456', Mockery::type('string'))
        ->andReturn(false, false, true);

    $siteResult = createDownSiteResult($site->id);
    $this->service->evaluateAndNotify(collect([$siteResult]));

    $log = NotificationLog::where('site_id', $site->id)->first();
    expect($log->targets_sent)->toBe(1);
    expect($log->targets_failed)->toBe(0);
});

// Req 13.7: All retries fail
test('records failed target when all retry attempts fail', function () {
    $site = createSite([
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 2,
        'notification_sent' => false,
        'notification_cycle_counter' => 0,
        'first_down_at' => now()->subMinutes(30),
    ]);

    $this->telegramMock->shouldReceive('sendMessage')
        ->times(3)
        ->with('123456', Mockery::type('string'))
        ->andReturn(false);

    $siteResult = createDownSiteResult($site->id);
    $this->service->evaluateAndNotify(collect([$siteResult]));

    $log = NotificationLog::where('site_id', $site->id)->first();
    expect($log->targets_sent)->toBe(0);
    expect($log->targets_failed)->toBe(1);
});

// Req 14.2: Recovery notification includes site name and down duration
test('recovery notification includes site name and down duration', function () {
    $site = createSite([
        'name' => 'ITK Portal',
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 5,
        'notification_sent' => true,
        'notification_cycle_counter' => 3,
        'first_down_at' => now()->subHours(2)->subMinutes(30),
    ]);

    $capturedMessage = null;
    $this->telegramMock->shouldReceive('sendMessage')
        ->once()
        ->withArgs(function ($chatId, $message) use (&$capturedMessage) {
            $capturedMessage = $message;
            return true;
        })
        ->andReturn(true);

    $siteResult = createUpSiteResult($site->id, 'ITK Portal');
    $this->service->evaluateAndNotify(collect([$siteResult]));

    expect($capturedMessage)->toContain('ITK Portal');
    expect($capturedMessage)->toContain('2h 30m');
});

// Req 13.3: Down notification includes site name, status, and down page URLs
test('down notification includes site name status and down page URLs', function () {
    $site = createSite([
        'name' => 'ITK Portal',
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 2,
        'notification_sent' => false,
        'notification_cycle_counter' => 0,
        'first_down_at' => now()->subMinutes(30),
    ]);

    $capturedMessage = null;
    $this->telegramMock->shouldReceive('sendMessage')
        ->once()
        ->withArgs(function ($chatId, $message) use (&$capturedMessage) {
            $capturedMessage = $message;
            return true;
        })
        ->andReturn(true);

    $siteResult = createDownSiteResult($site->id, 'ITK Portal', true);
    $this->service->evaluateAndNotify(collect([$siteResult]));

    expect($capturedMessage)->toContain('ITK Portal');
    expect($capturedMessage)->toContain('TOTALLY DOWN');
    expect($capturedMessage)->toContain('https://example.com/page1');
});

// Test getNotificationCycleThreshold returns configured value
test('getNotificationCycleThreshold returns configured value', function () {
    SystemConfig::create(['key' => 'notification_cycle_threshold', 'value' => '10']);

    expect($this->service->getNotificationCycleThreshold())->toBe(10);
});

// Test getNotificationCycleThreshold returns default when not configured
test('getNotificationCycleThreshold returns default when not configured', function () {
    expect($this->service->getNotificationCycleThreshold())->toBe(6);
});

// Test setNotificationCycleThreshold persists value
test('setNotificationCycleThreshold persists value', function () {
    $this->service->setNotificationCycleThreshold(12);

    expect(SystemConfig::getValue('notification_cycle_threshold'))->toBe('12');
});

// Test notification counter is reset to 0 on initial notification (for correct repeated timing)
test('notification cycle counter is reset to 0 on initial notification for correct repeated timing', function () {
    SystemConfig::create(['key' => 'notification_cycle_threshold', 'value' => '6']);

    $site = createSite([
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 2,
        'notification_sent' => false,
        'notification_cycle_counter' => 2, // Pre-existing counter
        'first_down_at' => now()->subMinutes(30),
    ]);

    $this->telegramMock->shouldReceive('sendMessage')
        ->once()
        ->andReturn(true);

    $siteResult = createDownSiteResult($site->id);
    $this->service->evaluateAndNotify(collect([$siteResult]));

    $site->refresh();
    // Counter should be 0 (reset on initial notification), so that repeated fires at exactly threshold cycles later
    expect($site->notification_cycle_counter)->toBe(0);
});

// Full scenario: verify repeated notification fires exactly threshold cycles after initial
test('repeated notification fires exactly notification_cycle_threshold cycles after initial notification', function () {
    SystemConfig::create(['key' => 'notification_cycle_threshold', 'value' => '3']);

    $site = createSite([
        'status' => SiteStatus::TotallyDown,
        'consecutive_down_count' => 2,
        'notification_sent' => false,
        'notification_cycle_counter' => 0,
        'first_down_at' => now()->subMinutes(30),
    ]);

    // Cycle 1: consecutive_down_count reaches 3 - initial notification sent
    $this->telegramMock->shouldReceive('sendMessage')->once()->andReturn(true);
    $siteResult = createDownSiteResult($site->id);
    $this->service->evaluateAndNotify(collect([$siteResult]));
    $site->refresh();
    expect($site->notification_sent)->toBeTrue();
    expect($site->notification_cycle_counter)->toBe(0);
    expect($site->consecutive_down_count)->toBe(3);
    NotificationLog::truncate();

    // Cycles 2, 3: no notification (counter at 1, 2)
    for ($i = 0; $i < 2; $i++) {
        $this->telegramMock->shouldNotReceive('sendMessage');
        $siteResult = createDownSiteResult($site->id);
        $this->service->evaluateAndNotify(collect([$siteResult]));
        $site->refresh();
    }
    expect($site->notification_cycle_counter)->toBe(2);
    expect(NotificationLog::count())->toBe(0);

    // Cycle 4: counter reaches 3 (threshold) - repeated notification!
    $this->telegramMock->shouldReceive('sendMessage')->once()->andReturn(true);
    $siteResult = createDownSiteResult($site->id);
    $this->service->evaluateAndNotify(collect([$siteResult]));
    $site->refresh();
    expect($site->notification_cycle_counter)->toBe(3);
    expect(NotificationLog::where('type', 'down')->count())->toBe(1);
});
