<?php

use App\Jobs\SendTelegramNotificationJob;
use App\Models\NotificationLog;
use App\Models\Site;
use App\Models\Category;
use App\Models\ITStaff;
use App\Services\TelegramBotServiceInterface;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->category = Category::create(['name' => 'Test Category']);
    $this->staff = ITStaff::create(['name' => 'John Doe', 'position' => 'SysAdmin']);
    $this->site = Site::create([
        'name' => 'Test Site',
        'category_id' => $this->category->id,
        'base_url' => 'https://example.com',
        'responsible_person_id' => $this->staff->id,
        'status' => 'up',
        'consecutive_down_count' => 0,
        'notification_sent' => false,
        'notification_cycle_counter' => 0,
        'avg_response_time' => 0,
    ]);
});

describe('SendTelegramNotificationJob', function () {
    it('is dispatched on the database connection', function () {
        $job = new SendTelegramNotificationJob(
            chatId: '12345',
            message: 'Test message',
            siteId: $this->site->id,
            notificationType: 'down',
        );

        expect($job->connection)->toBe('database');
    });

    it('has 3 tries configured', function () {
        $job = new SendTelegramNotificationJob(
            chatId: '12345',
            message: 'Test message',
        );

        expect($job->tries)->toBe(3);
    });

    it('has 5-second backoff configured', function () {
        $job = new SendTelegramNotificationJob(
            chatId: '12345',
            message: 'Test message',
        );

        expect($job->backoff)->toBe(5);
    });

    it('sends message successfully and logs to notification_logs', function () {
        $mockService = Mockery::mock(TelegramBotServiceInterface::class);
        $mockService->shouldReceive('sendMessage')
            ->with('12345', 'Test notification message')
            ->once()
            ->andReturn(true);

        $this->app->instance(TelegramBotServiceInterface::class, $mockService);

        $job = new SendTelegramNotificationJob(
            chatId: '12345',
            message: 'Test notification message',
            siteId: $this->site->id,
            notificationType: 'down',
        );

        $job->handle($mockService);

        $this->assertDatabaseHas('notification_logs', [
            'site_id' => $this->site->id,
            'type' => 'down',
            'message' => 'Test notification message',
            'targets_sent' => 1,
            'targets_failed' => 0,
        ]);
    });

    it('logs delivery result with null site_id', function () {
        $mockService = Mockery::mock(TelegramBotServiceInterface::class);
        $mockService->shouldReceive('sendMessage')
            ->with('12345', 'System health message')
            ->once()
            ->andReturn(true);

        $job = new SendTelegramNotificationJob(
            chatId: '12345',
            message: 'System health message',
            siteId: null,
            notificationType: 'system_health',
        );

        $job->handle($mockService);

        $this->assertDatabaseHas('notification_logs', [
            'site_id' => null,
            'type' => 'system_health',
            'message' => 'System health message',
            'targets_sent' => 1,
            'targets_failed' => 0,
        ]);
    });

    it('splits messages exceeding 4096 characters and sends multiple', function () {
        $longMessage = str_repeat("Line of text content here\n", 200); // > 4096 chars

        $mockService = Mockery::mock(TelegramBotServiceInterface::class);
        $mockService->shouldReceive('sendMessage')
            ->with('12345', Mockery::type('string'))
            ->times(2) // Should be split into at least 2 chunks
            ->andReturn(true);

        $job = new SendTelegramNotificationJob(
            chatId: '12345',
            message: $longMessage,
            siteId: $this->site->id,
            notificationType: 'down',
        );

        $job->handle($mockService);

        $this->assertDatabaseHas('notification_logs', [
            'site_id' => $this->site->id,
            'type' => 'down',
            'targets_sent' => 1,
            'targets_failed' => 0,
        ]);
    });

    it('can be dispatched to the queue', function () {
        Queue::fake();

        SendTelegramNotificationJob::dispatch(
            '12345',
            'Test message',
            $this->site->id,
            'down',
        );

        Queue::assertPushed(SendTelegramNotificationJob::class, function ($job) {
            return $job->chatId === '12345'
                && $job->message === 'Test message'
                && $job->siteId === $this->site->id
                && $job->notificationType === 'down';
        });
    });

    it('logs failure when failed() is called', function () {
        $job = new SendTelegramNotificationJob(
            chatId: '12345',
            message: 'Failed message',
            siteId: $this->site->id,
            notificationType: 'down',
        );

        $job->failed(new \RuntimeException('Connection timeout'));

        $this->assertDatabaseHas('notification_logs', [
            'site_id' => $this->site->id,
            'type' => 'down',
            'message' => 'Failed message',
            'targets_sent' => 0,
            'targets_failed' => 1,
        ]);
    });
});
