<?php

namespace Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\Schemas;

use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Ejoi8\FilamentEmailLogs\Support\EmailLogTenancy;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class EmailLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Message details')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema(array_values(array_filter([
                        TextEntry::make('delivery_type')
                            ->label('Type')
                            ->state(fn (EmailLog $record): string => $record->deliveryTypeLabel())
                            ->badge()
                            ->color(fn (EmailLog $record): string => $record->isResentCopy() ? 'warning' : 'success'),
                        TextEntry::make('sent_at')
                            ->label('Sent at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('subject')
                            ->columnSpanFull()
                            ->placeholder('-'),
                        TextEntry::make('from_summary')
                            ->label('From')
                            ->state(fn (EmailLog $record): string => $record->fromSummary())
                            ->copyable(),
                        TextEntry::make('to_summary')
                            ->label('To')
                            ->state(fn (EmailLog $record): string => $record->toSummary())
                            ->copyable(),
                        TextEntry::make('cc_summary')
                            ->label('Cc')
                            ->state(fn (EmailLog $record): string => $record->ccSummary())
                            ->placeholder('-'),
                        TextEntry::make('bcc_summary')
                            ->label('Bcc')
                            ->state(fn (EmailLog $record): string => $record->bccSummary())
                            ->placeholder('-'),
                        EmailLogTenancy::enabled()
                            ? TextEntry::make(EmailLogTenancy::column())
                                ->label('Tenant')
                                ->badge()
                                ->placeholder('-')
                            : null,
                        TextEntry::make('message_id')
                            ->label('Message ID')
                            ->placeholder('-')
                            ->copyable()
                            ->columnSpanFull(),
                        TextEntry::make('resent_count')
                            ->label('Resent count')
                            ->badge()
                            ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),
                        TextEntry::make('last_resent_at')
                            ->label('Last resent at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('originalEmailLog.id')
                            ->label('Original log ID')
                            ->placeholder('-'),
                        TextEntry::make('resentBy.name')
                            ->label('Resent by')
                            ->placeholder('-'),
                        TextEntry::make('resend_note')
                            ->label('Resend note')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]))),
                Tabs::make('Content')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Preview')
                            ->icon(Heroicon::OutlinedEye)
                            ->schema([
                                TextEntry::make('html_preview')
                                    ->hiddenLabel()
                                    ->state(fn (EmailLog $record): View => view('filament-email-logs::infolists.email-log-preview', [
                                        'html' => $record->html_body,
                                        'text' => $record->text_body,
                                    ]))
                                    ->html()
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Plain text')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                TextEntry::make('text_body')
                                    ->hiddenLabel()
                                    ->placeholder('No plain-text body was stored.')
                                    ->formatStateUsing(fn (?string $state): ?string => filled($state) ? nl2br(e($state)) : null)
                                    ->html()
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('HTML source')
                            ->icon(Heroicon::OutlinedCodeBracket)
                            ->schema([
                                TextEntry::make('html_body')
                                    ->hiddenLabel()
                                    ->placeholder('No HTML body was stored.')
                                    ->formatStateUsing(fn (?string $state): ?string => filled($state)
                                        ? '<pre style="white-space:pre-wrap;word-break:break-word;font-size:12px;line-height:1.5;">'.e($state).'</pre>'
                                        : null)
                                    ->html()
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
