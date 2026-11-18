<?php

use App\DTOs\PageCheckResult;
use App\DTOs\SiteCheckResult;
use App\Enums\ErrorType;
use App\Enums\SiteStatus;
use App\Livewire\Dashboard;
use App\Models\NotificationLog;
use App\Models\Site;
use App\Models\SystemConfig;
use App\Models\TelegramTarget;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TelegramBotService;
use App\Services\TelegramBotServiceInterface;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function sitePayload(Site $site, array $overrides = []): array
{
    return array_merge([
        'name' => $site->name,
        'category_id' => $site->category_id,
        'base_url' => $site->base_url,
        'responsible_person_id' => $site->responsible_person_id,
        'pages' => ['/'],
    ], $overrides);
}

function downResultFor(Site $site): SiteCheckResult
{
    return new SiteCheckResult(
        siteId: $site->id,
        siteName: $site->name,
        pageResults: collect([
            new PageCheckResult(
                pageId: 1,
                siteId: $site->id,
                url: $site->base_url . '/',
                httpCode: 0,
                responseTimeMs: 0,
                errorType: ErrorType::ConnectionFailure,
            ),
        ]),
    );
}

describe('edit form', function () {
    beforeEach(function () {
        $this->user = User::factory()->create(['role' => 'admin']);
    });

    it('shows the monitoring toggle with the current value', function () {
        $site = Site::factory()->create(['is_monitored' => false]);

        $this->actingAs($this->user)
            ->get(route('sites.edit', $site))
            ->assertOk()
            ->assertSee('Monitor this site')
            ->assertSee('id="is_monitored" value="1"', false)
            ->assertDontSee('id="is_monitored" value="1" checked', false);
    });

    it('turns monitoring off', function () {
        $site = Site::factory()->create();

        $this->actingAs($this->user)
            ->put(route('sites.update', $site), sitePayload($site, ['is_monitored' => '0']))
            ->assertRedirect(route('sites.index'));

        expect($site->fresh()->is_monitored)->toBeFalse();
    });

    it('turns monitoring back on', function () {
        $site = Site::factory()->create(['is_monitored' => false]);

        $this->actingAs($this->user)
            ->put(route('sites.update', $site), sitePayload($site, ['is_monitored' => '1']));

        expect($site->fresh()->is_monitored)->toBeTrue();
    });

    it('keeps the current value when the field is not submitted', function () {
        $site = Site::factory()->create(['is_monitored' => false]);

        $this->actingAs($this->user)
            ->put(route('sites.update', $site), sitePayload($site));

        expect($site->fresh()->is_monitored)->toBeFalse();
    });
});

describe('notifications', function () {
    beforeEach(function () {
        TelegramTarget::create(['chat_id' => '123456', 'is_active' => true]);
        SystemConfig::updateOrCreate(['key' => 'notification_cycle_threshold'], ['value' => '1']);

        $this->telegram = Mockery::mock(TelegramBotServiceInterface::class);
        $this->service = new NotificationService($this->telegram);
    });

    it('stops repeat alerts when the only down site is unmonitored', function () {
        Site::factory()->totallyDown()->create([
            'is_monitored' => false,
            'notification_sent' => true,
        ]);

        $this->telegram->shouldNotReceive('sendMessage');

        $this->service->evaluateAndNotify(collect());

        expect(NotificationLog::count())->toBe(0);
    });

    it('leaves unmonitored sites out of alerts for other down sites', function () {
        Site::factory()->totallyDown()->create([
            'name' => 'Paused Site',
            'is_monitored' => false,
            'notification_sent' => true,
        ]);
        Site::factory()->totallyDown()->create([
            'name' => 'Active Site',
            'notification_sent' => true,
        ]);

        $this->telegram->shouldReceive('sendMessage')
            ->once()
            ->withArgs(fn (string $chatId, string $message) => str_contains($message, 'Active Site')
                && !str_contains($message, 'Paused Site'))
            ->andReturn(true);

        $this->service->evaluateAndNotify(collect());
    });

    it('still alerts for monitored sites after their down result', function () {
        $site = Site::factory()->create(['consecutive_down_count' => 2]);

        $this->telegram->shouldReceive('sendMessage')->atLeast()->once()->andReturn(true);

        $this->service->evaluateAndNotify(collect([downResultFor($site)]));

        expect($site->fresh()->notification_sent)->toBeTrue();
    });
});

describe('telegram /down command', function () {
    it('leaves unmonitored sites out of the list', function () {
        Http::fake(['*' => Http::response(['ok' => true])]);
        config(['services.telegram.bot_token' => 'test-token']);

        Site::factory()->totallyDown()->create(['name' => 'Paused Site', 'is_monitored' => false]);
        Site::factory()->totallyDown()->create(['name' => 'Active Site']);

        $target = TelegramTarget::create(['chat_id' => '123456', 'is_active' => true]);

        app(TelegramBotService::class)->handleUpdate([
            'message' => ['chat' => ['id' => $target->chat_id], 'text' => '/down'],
        ]);

        Http::assertSent(fn ($request) => str_contains((string) $request['text'], 'Active Site')
            && !str_contains((string) $request['text'], 'Paused Site'));
    });
});

describe('dashboard', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['role' => 'admin']);
    });

    it('excludes unmonitored sites from up and down counts but not the total', function () {
        Site::factory()->create(['status' => SiteStatus::Up]);
        Site::factory()->totallyDown()->create();
        Site::factory()->totallyDown()->create(['is_monitored' => false]);
        Site::factory()->create(['status' => SiteStatus::Up, 'is_monitored' => false]);

        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->assertSet('totalSites', 4)
            ->assertSet('sitesUp', 1)
            ->assertSet('sitesDown', 1)
            ->assertSet('sitesUnmonitored', 2)
            ->assertSee('2 not monitored');
    });

    it('labels an unmonitored site instead of showing its last status', function () {
        Site::factory()->totallyDown()->create(['name' => 'Paused Site', 'is_monitored' => false]);

        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->assertSee('Paused Site')
            ->assertSee('Not monitored')
            // The status badge says "Not monitored", not the site's last status (totally down)
            ->assertSeeHtml('data-status="not_monitored"')
            ->assertDontSeeHtml('data-status="totally_down"');
    });

    it('hides unmonitored sites when filtering by status', function () {
        Site::factory()->totallyDown()->create(['name' => 'Paused Site', 'is_monitored' => false]);
        Site::factory()->totallyDown()->create(['name' => 'Active Site']);

        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->set('statusFilter', 'totally_down')
            ->assertSee('Active Site')
            ->assertDontSee('Paused Site');
    });
});
