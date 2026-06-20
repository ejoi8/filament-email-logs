<?php

namespace Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs;

use BackedEnum;
use Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\Actions\ResendEmailLogAction;
use Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\Pages\ListEmailLogs;
use Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\Pages\ViewEmailLog;
use Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\Schemas\EmailLogInfolist;
use Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\Tables\EmailLogsTable;
use Ejoi8\FilamentEmailLogs\FilamentEmailLogsPlugin;
use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Ejoi8\FilamentEmailLogs\Support\EmailLogAuthorization;
use Ejoi8\FilamentEmailLogs\Support\EmailLogTenancy;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    protected static ?string $recordTitleAttribute = 'subject';

    protected static ?string $tenantOwnershipRelationshipName = EmailLogTenancy::RELATIONSHIP;

    public static function canAccess(): bool
    {
        return EmailLogAuthorization::canAccess(auth()->user());
    }

    /**
     * Only scope the resource to a tenant when multitenancy is explicitly
     * enabled in config. In single-tenant panels this keeps Filament from
     * registering a tenant global scope on EmailLog; in multitenant panels it
     * avoids the LogicException Filament would otherwise throw, because the
     * relationship is only required once the user opts into per-tenant logs.
     */
    public static function isScopedToTenant(): bool
    {
        return EmailLogTenancy::enabled();
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmailLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmailLogsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'originalEmailLog',
            'resentBy',
        ]);

        if (EmailLogTenancy::enabled()) {
            $query->with(EmailLogTenancy::RELATIONSHIP);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailLogs::route('/'),
            'view' => ViewEmailLog::route('/{record}'),
        ];
    }

    public static function resendAction(?EmailLog $emailLog = null): Action
    {
        return ResendEmailLogAction::make($emailLog);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! config('filament-email-logs.navigation.badge', false)) {
            return null;
        }

        $count = static::getModel()::query()
            ->where('sent_at', '>=', now()->startOfDay())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return static::getPlugin()->getNavigationGroup();
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return static::getPlugin()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return static::getPlugin()->getNavigationSort();
    }

    protected static function getPlugin(): FilamentEmailLogsPlugin
    {
        /** @var FilamentEmailLogsPlugin $plugin */
        $plugin = Filament::getCurrentPanel()?->getPlugin(FilamentEmailLogsPlugin::ID)
            ?? filament(FilamentEmailLogsPlugin::ID);

        return $plugin;
    }
}
