<?php

namespace Ejoi8\FilamentEmailLogs;

use BackedEnum;
use Closure;
use Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\EmailLogResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;

class FilamentEmailLogsPlugin implements Plugin
{
    public const ID = 'filament-email-logs';

    protected ?Closure $authorizeUsing = null;

    protected ?string $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected string|BackedEnum|null $navigationIcon = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            EmailLogResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function authorizeUsing(Closure $callback): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function canAccess(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->authorizeUsing instanceof Closure) {
            return (bool) app()->call($this->authorizeUsing, [
                'user' => $user,
            ]);
        }

        $ability = config('filament-email-logs.authorization.ability');

        if (filled($ability)) {
            return $user->can($ability);
        }

        return false;
    }

    public function navigationGroup(?string $navigationGroup): static
    {
        $this->navigationGroup = $navigationGroup;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup ?? config('filament-email-logs.navigation.group');
    }

    public function navigationSort(?int $navigationSort): static
    {
        $this->navigationSort = $navigationSort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort ?? config('filament-email-logs.navigation.sort');
    }

    public function navigationIcon(string|BackedEnum $navigationIcon): static
    {
        $this->navigationIcon = $navigationIcon;

        return $this;
    }

    public function getNavigationIcon(): string|BackedEnum
    {
        $navigationIcon = $this->navigationIcon ?? config('filament-email-logs.navigation.icon');

        if (is_string($navigationIcon) || $navigationIcon instanceof BackedEnum) {
            return $navigationIcon;
        }

        return Heroicon::OutlinedEnvelope;
    }
}
