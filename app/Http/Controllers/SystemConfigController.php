<?php

namespace App\Http\Controllers;

use App\Models\SystemConfig;
use App\Services\MonitoringServiceInterface;
use App\Services\NotificationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemConfigController extends Controller
{
    public function __construct(
        private readonly MonitoringServiceInterface $monitoringService,
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    /**
     * Display the system configuration form.
     */
    public function index(): View
    {
        $cycleInterval = $this->monitoringService->getCycleInterval();
        $notificationCycleThreshold = $this->notificationService->getNotificationCycleThreshold();
        $connectionTimeout = (int) (SystemConfig::getValue('connection_timeout_seconds') ?? 20);
        $responseTimeout = (int) (SystemConfig::getValue('response_timeout_seconds') ?? 50);
        $concurrencyLimit = (int) (SystemConfig::getValue('concurrency_limit') ?? 50);

        return view('system-config.index', compact(
            'cycleInterval',
            'notificationCycleThreshold',
            'connectionTimeout',
            'responseTimeout',
            'concurrencyLimit',
        ));
    }

    /**
     * Update system configuration values.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cycle_interval_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'notification_cycle_threshold' => ['required', 'integer', 'min:1', 'max:100'],
            'connection_timeout_seconds' => ['required', 'integer', 'min:1', 'max:60'],
            'response_timeout_seconds' => ['required', 'integer', 'min:5', 'max:120'],
            'concurrency_limit' => ['required', 'integer', 'min:5', 'max:100'],
        ], [
            'cycle_interval_minutes.required' => 'The cycle interval is required.',
            'cycle_interval_minutes.integer' => 'The cycle interval must be a whole number.',
            'cycle_interval_minutes.min' => 'The cycle interval must be at least 5 minutes.',
            'cycle_interval_minutes.max' => 'The cycle interval must not exceed 1440 minutes.',
            'notification_cycle_threshold.required' => 'The notification cycle threshold is required.',
            'notification_cycle_threshold.integer' => 'The notification cycle threshold must be a whole number.',
            'notification_cycle_threshold.min' => 'The notification cycle threshold must be at least 1 cycle.',
            'notification_cycle_threshold.max' => 'The notification cycle threshold must not exceed 100 cycles.',
            'connection_timeout_seconds.required' => 'The connection timeout is required.',
            'connection_timeout_seconds.integer' => 'The connection timeout must be a whole number.',
            'connection_timeout_seconds.min' => 'The connection timeout must be at least 1 second.',
            'connection_timeout_seconds.max' => 'The connection timeout must not exceed 60 seconds.',
            'response_timeout_seconds.required' => 'The response timeout is required.',
            'response_timeout_seconds.integer' => 'The response timeout must be a whole number.',
            'response_timeout_seconds.min' => 'The response timeout must be at least 5 seconds.',
            'response_timeout_seconds.max' => 'The response timeout must not exceed 120 seconds.',
            'concurrency_limit.required' => 'The concurrency limit is required.',
            'concurrency_limit.integer' => 'The concurrency limit must be a whole number.',
            'concurrency_limit.min' => 'The concurrency limit must be at least 5.',
            'concurrency_limit.max' => 'The concurrency limit must not exceed 100.',
        ]);

        $this->monitoringService->setCycleInterval((int) $validated['cycle_interval_minutes']);
        $this->notificationService->setNotificationCycleThreshold((int) $validated['notification_cycle_threshold']);

        SystemConfig::updateOrCreate(
            ['key' => 'connection_timeout_seconds'],
            ['value' => (string) $validated['connection_timeout_seconds'], 'updated_at' => now()],
        );

        SystemConfig::updateOrCreate(
            ['key' => 'response_timeout_seconds'],
            ['value' => (string) $validated['response_timeout_seconds'], 'updated_at' => now()],
        );

        SystemConfig::updateOrCreate(
            ['key' => 'concurrency_limit'],
            ['value' => (string) $validated['concurrency_limit'], 'updated_at' => now()],
        );

        return redirect()->route('system-config.index')
            ->with('success', 'System configuration updated successfully.');
    }
}
