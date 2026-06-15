<?php

namespace App\Providers;

use App\Services\AuthService;
use App\Services\AuthServiceInterface;
use App\Services\HealthCheckService;
use App\Services\HealthCheckServiceInterface;
use App\Services\MonitoringService;
use App\Services\MonitoringServiceInterface;
use App\Services\NotificationService;
use App\Services\NotificationServiceInterface;
use App\Services\StatusDeterminationService;
use App\Services\StatusDeterminationServiceInterface;
use App\Services\TelegramBotService;
use App\Services\TelegramBotServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            StatusDeterminationServiceInterface::class,
            StatusDeterminationService::class
        );

        $this->app->bind(
            HealthCheckServiceInterface::class,
            HealthCheckService::class
        );

        $this->app->bind(
            NotificationServiceInterface::class,
            NotificationService::class
        );

        $this->app->bind(
            MonitoringServiceInterface::class,
            MonitoringService::class
        );

        // TelegramBotService binding
        $this->app->bind(
            TelegramBotServiceInterface::class,
            TelegramBotService::class
        );

        // AuthService binding
        $this->app->bind(
            AuthServiceInterface::class,
            AuthService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
