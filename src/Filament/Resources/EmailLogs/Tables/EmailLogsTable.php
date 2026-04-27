<?php

namespace Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\Tables;

use Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\EmailLogResource;
use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmailLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sent_at', 'desc')
            ->columns([
                TextColumn::make('delivery_type')
                    ->label('Type')
                    ->state(fn (EmailLog $record): string => $record->deliveryTypeLabel())
                    ->badge()
                    ->color(fn (EmailLog $record): string => $record->isResentCopy() ? 'warning' : 'success')
                    ->sortable(false),
                TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('to_summary')
                    ->label('To')
                    ->state(fn (EmailLog $record): string => $record->toSummary())
                    ->searchable(),
                TextColumn::make('from_summary')
                    ->label('From')
                    ->state(fn (EmailLog $record): string => $record->fromSummary())
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('originalEmailLog.id')
                    ->label('Original Log')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('resentBy.name')
                    ->label('Resent By')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('resent_count')
                    ->label('Resent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_resent_at')
                    ->label('Last Resent')
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('resent_emails')
                    ->label('Resent emails only')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('original_email_log_id')),
                Filter::make('resent_originals')
                    ->label('Original emails that were resent')
                    ->query(fn (Builder $query): Builder => $query->where('resent_count', '>', 0)),
            ])
            ->recordActions([
                ViewAction::make(),
                EmailLogResource::resendAction(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
