<?php

namespace Ejoi8\FilamentEmailLogs\Support;

use Symfony\Component\Mime\Email;

class EmailLogHeaders
{
    public const ORIGINAL_EMAIL_LOG_ID = 'X-Email-Log-Original-ID';

    public const RESENT_BY_ID = 'X-Email-Log-Resent-By';

    public const RESEND_NOTE = 'X-Email-Log-Resend-Note';

    public static function getInteger(Email $message, string $header): ?int
    {
        $value = self::getString($message, $header);

        if (! is_numeric($value)) {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }

    public static function getString(Email $message, string $header): ?string
    {
        $value = $message->getHeaders()->getHeaderBody($header);

        return filled($value) ? trim((string) $value) : null;
    }
}
