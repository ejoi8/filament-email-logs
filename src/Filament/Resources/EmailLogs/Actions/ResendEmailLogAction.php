<?php

namespace Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\Actions;

use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Ejoi8\FilamentEmailLogs\Services\EmailLogResender;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ResendEmailLogAction
{
    public static function make(?EmailLog $emailLog = null): Action
    {
        return Action::make('resend')
            ->label('Resend')
            ->icon(Heroicon::ArrowPath)
            ->color('gray')
            ->visible(fn (?EmailLog $record = null): bool => auth()->user()?->can('update', self::resolveRecord($emailLog, $record)) ?? false)
            ->disabled(fn (?EmailLog $record = null): bool => ! self::resolveRecord($emailLog, $record)->hasStoredBody())
            ->schema([
                Toggle::make('send_to_original_recipients')
                    ->label('Send to original recipients')
                    ->default(true)
                    ->live(),
                TagsInput::make('override_to_addresses')
                    ->label('Additional or corrected recipients')
                    ->placeholder('name@example.com')
                    ->splitKeys(['Tab', 'Enter'])
                    ->nestedRecursiveRules(['email:rfc'])
                    ->required(fn (Get $get): bool => ! (bool) $get('send_to_original_recipients'))
                    ->helperText('Leave empty to resend exactly as logged. Disable the original recipients toggle to send only to the corrected addresses.'),
                Textarea::make('resend_note')
                    ->label('Internal note')
                    ->rows(3)
                    ->maxLength(1000)
                    ->helperText('Stored for audit purposes only.'),
            ])
            ->fillForm([
                'send_to_original_recipients' => true,
                'override_to_addresses' => [],
                'resend_note' => null,
            ])
            ->modalDescription('Resend the stored message either to the original recipients or to corrected addresses.')
            ->action(function (array $data, EmailLogResender $emailLogResender, ?EmailLog $record = null) use ($emailLog): void {
                try {
                    $result = $emailLogResender->resend(
                        self::resolveRecord($emailLog, $record),
                        $data['override_to_addresses'] ?? [],
                        (bool) ($data['send_to_original_recipients'] ?? true),
                        auth()->user(),
                        filled($data['resend_note'] ?? null) ? trim((string) $data['resend_note']) : null,
                    );

                    Notification::make()
                        ->title('Email resent')
                        ->body("Sent to {$result->recipientSummary()}.")
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('Resend failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function resolveRecord(?EmailLog $emailLog = null, ?EmailLog $record = null): EmailLog
    {
        if ($record instanceof EmailLog) {
            return $record;
        }

        if ($emailLog instanceof EmailLog) {
            return $emailLog;
        }

        throw new \InvalidArgumentException('Unable to resolve the email log record for the resend action.');
    }
}
