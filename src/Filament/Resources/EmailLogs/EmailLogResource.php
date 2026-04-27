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

    public static function canAccess(): bool
    {
        return EmailLogAuthorization::canAccess(auth()->user());
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
        return parent::getEloquentQuery()
            ->with([
                'originalEmailLog',
                'resentBy',
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
