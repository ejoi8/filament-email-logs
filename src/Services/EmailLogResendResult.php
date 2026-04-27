<?php

namespace Ejoi8\FilamentEmailLogs\Services;

use Ejoi8\FilamentEmailLogs\Models\EmailLog;

class EmailLogResendResult
{
    /**
     * @param  array<int, array{address: string, name?: string|null}>  $toAddresses
     */
    public function __construct(
        public readonly array $toAddresses,
        public readonly ?EmailLog $resentEmailLog,
    ) {}

    public function recipientSummary(): string
    {
        return EmailLog::formatAddressList($this->toAddresses);
    }
}
