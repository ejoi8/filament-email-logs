<?php

namespace Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\Tables;

use Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\EmailLogResource;
use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Ejoi8\FilamentEmailLogs\Support\EmailLogTenancy;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmailLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sent_at', 'desc')
            ->striped()
            ->columns(array_values(array_filter([
                TextColumn::make('delivery_type')
                    ->label('Type')
                    ->state(fn (EmailLog $record): string => $record->deliveryTypeLabel())
                    ->badge()
                    ->icon(fn (EmailLog $record): Heroicon => $record->isResentCopy()
                        ? Heroicon::ArrowPath
                        : Heroicon::PaperAirplane)
                    ->color(fn (EmailLog $record): string => $record->isResentCopy() ? 'warning' : 'success')
                    ->sortable(false),
                TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->tooltip(fn (EmailLog $record): ?string => $record->subject)
                    ->placeholder('-'),
                EmailLogTenancy::enabled()
                    ? TextColumn::make(EmailLogTenancy::column())
                        ->label('Tenant')
                        ->badge()
                        ->placeholder('-')
                        ->toggleable()
                    : null,
                TextColumn::make('to_summary')
                    ->label('To')
                    ->state(fn (EmailLog $record): string => $record->toSummary())
                    ->copyable()
                    ->wrap()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('to_addresses', 'like', "%{$search}%")),
                TextColumn::make('from_summary')
                    ->label('From')
                    ->state(fn (EmailLog $record): string => $record->fromSummary())
                    ->copyable()
                    ->placeholder('-')
                    ->toggleable()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('from_address', 'like', "%{$search}%")
                        ->orWhere('from_name', 'like', "%{$search}%")),
                TextColumn::make('originalEmailLog.id')
                    ->label('Original Log')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resentBy.name')
                    ->label('Resent By')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->description(fn (EmailLog $record): ?string => $record->sent_at?->diffForHumans())
                    ->sortable(),
                TextColumn::make('resent_count')
                    ->label('Resent')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
                TextColumn::make('last_resent_at')
                    ->label('Last Resent')
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])))
            ->filters([
                TernaryFilter::make('delivery_type')
                    ->label('Delivery type')
                    ->placeholder('All emails')
                    ->trueLabel('Resent copies only')
                    ->falseLabel('Original emails only')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('original_email_log_id'),
                        false: fn (Builder $query): Builder => $query->whereNull('original_email_log_id'),
                    ),
                Filter::make('resent_originals')
                    ->label('Originals that were resent')
                    ->query(fn (Builder $query): Builder => $query->where('resent_count', '>', 0)),
                Filter::make('sent_at')
                    ->schema([
                        DatePicker::make('sent_from')->label('Sent from'),
                        DatePicker::make('sent_until')->label('Sent until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['sent_from'] ?? null,
                            fn (Builder $q, string $date): Builder => $q->whereDate('sent_at', '>=', $date),
                        )
                        ->when(
                            $data['sent_until'] ?? null,
                            fn (Builder $q, string $date): Builder => $q->whereDate('sent_at', '<=', $date),
                        ))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['sent_from'] ?? null)) {
                            $indicators[] = 'Sent from '.$data['sent_from'];
                        }

                        if (filled($data['sent_until'] ?? null)) {
                            $indicators[] = 'Sent until '.$data['sent_until'];
                        }

                        return $indicators;
                    }),
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
            ])
            ->emptyStateIcon(Heroicon::OutlinedInbox)
            ->emptyStateHeading('No emails logged yet')
            ->emptyStateDescription('Outgoing emails are recorded here automatically once your application sends mail.');
    }
}
