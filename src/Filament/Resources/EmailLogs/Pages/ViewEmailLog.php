<?php

namespace Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\Pages;

use Ejoi8\FilamentEmailLogs\Filament\Resources\EmailLogs\EmailLogResource;
use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailLog extends ViewRecord
{
    protected static string $resource = EmailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EmailLogResource::resendAction($this->getEmailLogRecord()),
            DeleteAction::make(),
        ];
    }

    protected function getEmailLogRecord(): EmailLog
    {
        /** @var EmailLog $record */
        $record = $this->getRecord();

        return $record;
    }
}
