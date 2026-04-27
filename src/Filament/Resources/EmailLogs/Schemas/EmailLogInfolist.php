<?php

namespace Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\Schemas;

use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;

class EmailLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Message Details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('delivery_type')
                            ->label('Type')
                            ->state(fn (EmailLog $record): string => $record->deliveryTypeLabel())
                            ->badge()
                            ->color(fn (EmailLog $record): string => $record->isResentCopy() ? 'warning' : 'success'),
                        TextEntry::make('subject')
                            ->placeholder('-'),
                        TextEntry::make('message_id')
                            ->label('Message ID')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('from_summary')
                            ->label('From')
                            ->state(fn (EmailLog $record): string => $record->fromSummary()),
                        TextEntry::make('to_summary')
                            ->label('To')
                            ->state(fn (EmailLog $record): string => $record->toSummary()),
                        TextEntry::make('cc_summary')
                            ->label('Cc')
                            ->state(fn (EmailLog $record): string => $record->ccSummary())
                            ->placeholder('-'),
                        TextEntry::make('bcc_summary')
                            ->label('Bcc')
                            ->state(fn (EmailLog $record): string => $record->bccSummary())
                            ->placeholder('-'),
                        TextEntry::make('sent_at')
                            ->label('Sent At')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('last_resent_at')
                            ->label('Last Resent At')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('resent_count')
                            ->label('Resent Count')
                            ->numeric(),
                        TextEntry::make('originalEmailLog.id')
                            ->label('Original Log ID')
                            ->placeholder('-'),
                        TextEntry::make('resentBy.name')
                            ->label('Resent By')
                            ->placeholder('-'),
                        TextEntry::make('resend_note')
                            ->label('Resend Note')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Preview')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('html_preview')
                            ->label('HTML Preview')
                            ->state(fn (EmailLog $record): View => view('filament-email-logs::infolists.email-log-preview', [
                                'html' => $record->html_body,
                                'text' => $record->text_body,
                            ]))
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('text_body')
                            ->label('Text Body')
                            ->placeholder('-')
                            ->formatStateUsing(fn (?string $state): ?string => filled($state) ? nl2br(e($state)) : null)
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
