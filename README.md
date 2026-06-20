# Filament Email Logs

Filament Email Logs is a Filament v5 plugin for Laravel 13 that records sent emails, shows them in a Filament resource, and lets administrators resend them to the original or corrected recipients.

- Source: `https://github.com/ejoi8/filament-email-logs`
- Issues: `https://github.com/ejoi8/filament-email-logs/issues`

## Features

- Logs outgoing emails from Laravel's mail system
- Lists email logs inside a Filament panel
- Shows HTML and plain-text email content in preview, plain-text, and raw-source tabs
- Resends a logged email to the original recipients
- Resends to corrected recipients when an email address was wrong
- Tracks resend lineage, resend count, resend note, and resent-by user
- Optional multitenancy — scope logs per tenant and stamp the owning tenant automatically
- Filterable, searchable table with delivery-type, date-range filters and copyable recipients

## Requirements

- PHP 8.3+
- Laravel 13+
- Filament 5.4+

## Installation

### Composer

If you publish this package to a VCS repository or Packagist:

```bash
composer require ejoi8/filament-email-logs
```

For local development, you can also install it through a Composer path repository.

### Migrations

Run the package migrations:

```bash
php artisan migrate
```

### Optional: publish config

```bash
php artisan vendor:publish --tag=filament-email-logs-config
```

### Optional: publish views

```bash
php artisan vendor:publish --tag=filament-email-logs-views
```

## Registering the plugin

Register the plugin in your Filament panel provider:

```php
use App\Models\User;
use Ejoi8\FilamentEmailLogs\FilamentEmailLogsPlugin;

->plugin(
    FilamentEmailLogsPlugin::make()
        ->authorizeUsing(fn (?User $user): bool => $user?->canManageSystem() ?? false)
)
```

This is a good fit when your application already has its own admin-access logic.

## Configuration

The package config file is `config/filament-email-logs.php`.

```php
<?php

use Filament\Support\Icons\Heroicon;

return [
    'logging' => [
        'enabled' => true,
    ],

    'authorization' => [
        'ability' => null,
    ],

    'tenancy' => [
        'enabled' => false,
        'model' => null,        // e.g. App\Models\Team::class
        'column' => 'tenant_id',
    ],

    'navigation' => [
        'group' => 'System',
        'sort' => 1,
        'icon' => Heroicon::OutlinedEnvelope,
        'badge' => false,       // show a "logged today" count badge
    ],
];
```

### `logging.enabled`

Controls whether the package registers the `MessageSent` listener and records sent mail.

### `authorization.ability`

Optional fallback authorization when you do not want to use `->authorizeUsing(...)`.

Example:

```php
'authorization' => [
    'ability' => 'view email logs',
],
```

If `->authorizeUsing(...)` is defined on the plugin, that callback takes priority.

### `navigation.group`

Controls the Filament navigation group label.

### `navigation.sort`

Controls the navigation order.

### `navigation.icon`

Controls the resource navigation icon.

### `navigation.badge`

When `true`, the resource shows a navigation badge with the number of emails logged today.

You can also override navigation directly in the plugin registration:

```php
use Filament\Support\Icons\Heroicon;

->plugin(
    FilamentEmailLogsPlugin::make()
        ->navigationGroup('System')
        ->navigationSort(1)
        ->navigationIcon(Heroicon::OutlinedEnvelope)
        ->authorizeUsing(fn (?User $user): bool => $user?->canManageSystem() ?? false)
)
```

Plugin method overrides take priority over the config file.

## Multitenancy

This package supports Filament multitenancy and is safe to use whether or not your panel is multitenant.

By default (`tenancy.enabled => false`) the Email Logs resource is **not** scoped to a tenant, so it renders correctly in any panel — including a multitenant one — and simply shows every log. (Filament's default resource tenant-scoping is intentionally disabled for this resource to avoid a `LogicException`, since the log model has no tenant relationship until you opt in.)

### Enabling per-tenant logs

1. Configure `config/filament-email-logs.php`:

```php
'tenancy' => [
    'enabled' => true,
    'model' => App\Models\Team::class, // your panel's ->tenant() model
    'column' => 'tenant_id',
],
```

2. Add the tenant column by running the migration:

```bash
php artisan migrate
```

Once enabled, the resource scopes every query to `Filament::getTenant()`, so each tenant only sees their own logs.

### How the owning tenant is recorded

Emails are logged from Laravel's `MessageSent` listener, which often runs in a **queue worker** where `Filament::getTenant()` is not available. The package therefore resolves the tenant in this order:

1. An `X-Email-Log-Tenant-ID` mail header, if present (it travels with queued mail).
2. A resolver you register (see below).
3. `Filament::getTenant()` — works for mail sent synchronously during a tenant request.

For queued mail, register a resolver in a service provider so the tenant is captured reliably:

```php
use Ejoi8\FilamentEmailLogs\Support\EmailLogTenancy;
use Filament\Facades\Filament;

EmailLogTenancy::resolveUsing(fn () => Filament::getTenant()?->getKey());
```

Or stamp the header yourself on a Mailable:

```php
public function headers(): \Illuminate\Mail\Mailables\Headers
{
    return new \Illuminate\Mail\Mailables\Headers(text: [
        'X-Email-Log-Tenant-ID' => (string) $this->team->getKey(),
    ]);
}
```

Resent emails automatically carry the original log's tenant via this header.

> The migration adds an `unsignedBigInteger` tenant column. If your tenants use UUID keys, publish the migration (`--tag` is set up via `publishesMigrations`) and change the column type to match.

## How it works

The package listens for Laravel's `Illuminate\Mail\Events\MessageSent` event and stores the rendered email data in the `email_logs` table.

Each log stores:

- subject
- from address and name
- to, cc, and bcc addresses
- HTML body
- plain-text body
- mail message ID
- sent timestamp
- resend count
- last resent timestamp
- original email log ID for resent copies
- resent-by user ID
- resend note

When a resend happens, the package sends the stored message body again and the new outgoing message is logged as a new email log row. The original row is also updated with resend counters.

## Resending behavior

The resend action supports two common cases:

### Resend to original recipients

Use the default modal values and resend the email exactly as logged.

### Resend to corrected recipients

Disable `Send to original recipients`, then enter one or more corrected addresses in `Additional or corrected recipients`.

This is useful when:

- a user registered the wrong email address
- the original recipient mailbox is no longer valid
- you need to resend only to a replacement address

The resent copy stores audit metadata so administrators can understand:

- which log was the source
- who triggered the resend
- what note was attached
- where the resent copy was delivered

## Authorization patterns

This package is intentionally flexible because each project handles admin access differently.

### Option 1: custom callback

Best when your app already has its own user methods or role logic.

```php
->plugin(
    FilamentEmailLogsPlugin::make()
        ->authorizeUsing(fn (?User $user): bool => $user?->canManageSystem() ?? false)
)
```

### Option 2: ability-based fallback

Best when your app uses Laravel policies, gates, or a permission package.

```php
// config/filament-email-logs.php
'authorization' => [
    'ability' => 'view email logs',
],
```

Then register the plugin without a callback:

```php
->plugin(FilamentEmailLogsPlugin::make())
```

## Relationship to `.env` mail settings

This package does not manage your mail transport settings.

It uses whatever Laravel mail configuration is already active in your application, which usually comes from:

- `.env`
- `config/mail.php`
- any runtime mail configuration overrides your application applies

That means:

- the package logs sent mail
- the package resends through the currently active mailer configuration
- the package does not edit `.env`

## Future admin-managed email settings

If you later build a dedicated admin page for SMTP or mailer settings, the recommended approach is:

1. Keep `.env` as the server-level fallback
2. Store admin-editable mail settings in the database
3. Build the active Laravel mail configuration at runtime from database settings
4. Let this package continue logging and resending through that active configuration

In that setup, this package does not need special changes to benefit from the admin-managed mail settings. It will automatically use the currently configured mailer at send time.

## Package structure

- `src/FilamentEmailLogsServiceProvider.php`
  Registers config, migrations, views, policy, and the mail listener.
- `src/FilamentEmailLogsPlugin.php`
  Registers the Filament resource and package-level plugin options.
- `src/Filament/Resources/EmailLogs`
  Filament resource, pages, infolist, table, and resend action.
- `src/Services/EmailLogResender.php`
  Resend orchestration and recipient resolution.
- `src/Listeners/LogSentEmail.php`
  Persists sent mail to the database.
- `src/Models/EmailLog.php`
  The email log model with resend lineage helpers.

## Development notes

This repository currently consumes the package through a local Composer path repository. That makes it easy to continue development here and later move the package into its own repository without changing the package namespace or structure.

## Testing

The package ships with a [Pest](https://pestphp.com/) test suite that runs against [Orchestra Testbench](https://github.com/orchestral/testbench). Install the dev dependencies and run:

```bash
composer install
./vendor/bin/pest
```

`tests/Feature/TenancyTest.php` covers the multitenancy behaviour end to end: tenancy stays disabled by default, only activates once a tenant model is configured, adds the `tenant_id` column, and stamps the owning tenant from a mail header, a registered resolver, or the active Filament tenant.
