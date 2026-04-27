<?php

namespace Ejoi8\FilamentEmailLogs;

use Ejoi8\FilamentEmailLogs\Listeners\LogSentEmail;
use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Ejoi8\FilamentEmailLogs\Policies\EmailLogPolicy;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class FilamentEmailLogsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-email-logs.php', 'filament-email-logs');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-email-logs');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filament-email-logs.php' => config_path('filament-email-logs.php'),
            ], 'filament-email-logs-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/filament-email-logs'),
            ], 'filament-email-logs-views');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ]);
        }

        Gate::policy(EmailLog::class, EmailLogPolicy::class);

        if (config('filament-email-logs.logging.enabled', true)) {
            Event::listen(MessageSent::class, LogSentEmail::class);
        }
    }
}
