<?php

use Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\EmailLogResource;
use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Ejoi8\FilamentEmailLogs\Support\EmailLogTenancy;
use Ejoi8\FilamentEmailLogs\Tests\Fixtures\Team;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

function enableTenancy(): void
{
    config()->set('filament-email-logs.tenancy.enabled', true);
    config()->set('filament-email-logs.tenancy.model', Team::class);
}

it('keeps tenancy disabled by default', function () {
    expect(EmailLogTenancy::enabled())->toBeFalse()
        ->and(EmailLogResource::isScopedToTenant())->toBeFalse();
});

it('enables tenancy only when a model is configured', function () {
    config()->set('filament-email-logs.tenancy.enabled', true);
    expect(EmailLogTenancy::enabled())->toBeFalse(); // no model yet

    config()->set('filament-email-logs.tenancy.model', Team::class);
    expect(EmailLogTenancy::enabled())->toBeTrue()
        ->and(EmailLogTenancy::column())->toBe('tenant_id')
        ->and(EmailLogResource::isScopedToTenant())->toBeTrue();
});

it('adds the tenant column to the email_logs table', function () {
    expect(Schema::hasColumn('email_logs', 'tenant_id'))->toBeTrue();
});

it('does not stamp a tenant when tenancy is disabled', function () {
    Mail::raw('Hello', fn ($message) => $message->to('user@example.com')->subject('No tenant'));

    expect(EmailLog::query()->latest('id')->first()->tenant_id)->toBeNull();
});

it('stamps the tenant from an explicit mail header', function () {
    enableTenancy();

    Mail::raw('Hello', function ($message) {
        $message->to('user@example.com')->subject('With header');
        $message->getSymfonyMessage()->getHeaders()->addTextHeader('X-Email-Log-Tenant-ID', '5');
    });

    expect((int) EmailLog::query()->latest('id')->first()->tenant_id)->toBe(5);
});

it('stamps the tenant from the registered resolver', function () {
    enableTenancy();
    EmailLogTenancy::resolveUsing(fn () => 7);

    Mail::raw('Hello', fn ($message) => $message->to('user@example.com')->subject('With resolver'));

    expect((int) EmailLog::query()->latest('id')->first()->tenant_id)->toBe(7);
});

it('scopes the resource query to a given tenant', function () {
    enableTenancy();
    $tenant = Team::query()->create(['name' => 'Acme']);

    $sql = EmailLogResource::scopeEloquentQueryToTenant(EmailLog::query(), $tenant)->toSql();

    expect($sql)->toContain('tenant_id');
});
