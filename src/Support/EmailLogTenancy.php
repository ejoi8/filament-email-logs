<?php

namespace Ejoi8\FilamentEmailLogs\Support;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EmailLogTenancy
{
    /**
     * The ownership relationship name defined on the EmailLog model and used
     * by the Filament resource for tenant scoping.
     */
    public const RELATIONSHIP = 'tenant';

    protected static ?Closure $resolver = null;

    /**
     * Register a custom resolver for the current tenant key. Useful when mail
     * is sent outside of a Filament request (for example, from a queued job)
     * where Filament::getTenant() is not available. The callback may return a
     * tenant key (int|string) or an Eloquent model.
     */
    public static function resolveUsing(?Closure $callback): void
    {
        static::$resolver = $callback;
    }

    public static function enabled(): bool
    {
        return (bool) config('filament-email-logs.tenancy.enabled', false)
            && filled(static::tenantModel());
    }

    /**
     * @return class-string<Model>|null
     */
    public static function tenantModel(): ?string
    {
        $model = config('filament-email-logs.tenancy.model');

        return (is_string($model) && $model !== '') ? $model : null;
    }

    public static function column(): string
    {
        $column = config('filament-email-logs.tenancy.column', 'tenant_id');

        return (is_string($column) && $column !== '') ? $column : 'tenant_id';
    }

    /**
     * Resolve the key of the tenant that owns a newly logged email.
     */
    public static function resolveCurrentKey(): int|string|null
    {
        if (! static::enabled()) {
            return null;
        }

        if (static::$resolver instanceof Closure) {
            return static::normalizeKey((static::$resolver)());
        }

        return static::normalizeKey(static::currentFilamentTenant());
    }

    protected static function currentFilamentTenant(): ?Model
    {
        try {
            $tenant = Filament::getTenant();
        } catch (Throwable) {
            return null;
        }

        return $tenant instanceof Model ? $tenant : null;
    }

    protected static function normalizeKey(mixed $value): int|string|null
    {
        if ($value instanceof Model) {
            $value = $value->getKey();
        }

        if (is_int($value)) {
            return $value;
        }

        return (is_string($value) && $value !== '') ? $value : null;
    }
}
