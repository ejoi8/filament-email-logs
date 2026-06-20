<?php

namespace Ejoi8\FilamentEmailLogs\Tests;

use Ejoi8\FilamentEmailLogs\FilamentEmailLogsServiceProvider;
use Ejoi8\FilamentEmailLogs\Support\EmailLogTenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // The resolver is a static, so reset it between tests.
        EmailLogTenancy::resolveUsing(null);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            FilamentEmailLogsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Collect mail in memory; MessageSent still fires, so the listener runs.
        $app['config']->set('mail.default', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
