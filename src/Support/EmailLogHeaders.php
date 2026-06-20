<?php

namespace Ejoi8\FilamentEmailLogs\Support;

use Symfony\Component\Mime\Email;

class EmailLogHeaders
{
    public const ORIGINAL_EMAIL_LOG_ID = 'X-Email-Log-Original-ID';

    public const RESENT_BY_ID = 'X-Email-Log-Resent-By';

    public const RESEND_NOTE = 'X-Email-Log-Resend-Note';

    public const TENANT_ID = 'X-Email-Log-Tenant-ID';

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

    /**
     * Return a header value as an int when numeric, otherwise the trimmed
     * string. Tenant keys may be integers or UUIDs.
     */
    public static function getScalar(Email $message, string $header): int|string|null
    {
        $value = self::getString($message, $header);

        if ($value === null) {
            return null;
        }

        return is_numeric($value) ? (int) $value : $value;
    }
}
