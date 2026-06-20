<?php

namespace Ejoi8\FilamentEmailLogs\Listeners;

use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Ejoi8\FilamentEmailLogs\Support\EmailLogHeaders;
use Ejoi8\FilamentEmailLogs\Support\EmailLogTenancy;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        if (! $message instanceof Email) {
            return;
        }

        $from = $message->getFrom()[0] ?? null;
        $attributes = [
            'subject' => $message->getSubject(),
            'from_address' => $from?->getAddress(),
            'from_name' => $from?->getName(),
            'to_addresses' => $this->mapAddresses($message->getTo()),
            'cc_addresses' => $this->mapAddresses($message->getCc()),
            'bcc_addresses' => $this->mapAddresses($message->getBcc()),
            'message_id' => $event->sent->getMessageId(),
            'original_email_log_id' => EmailLogHeaders::getInteger($message, EmailLogHeaders::ORIGINAL_EMAIL_LOG_ID),
            'html_body' => $this->normalizeBody($message->getHtmlBody()),
            'text_body' => $this->normalizeBody($message->getTextBody()),
            'sent_at' => now(),
            'resent_by' => EmailLogHeaders::getInteger($message, EmailLogHeaders::RESENT_BY_ID),
            'resend_note' => EmailLogHeaders::getString($message, EmailLogHeaders::RESEND_NOTE),
        ];

        if (EmailLogTenancy::enabled()) {
            $attributes[EmailLogTenancy::column()] = $this->resolveTenantKey($message);
        }

        $emailLog = filled($attributes['message_id'])
            ? EmailLog::query()->firstOrNew(['message_id' => $attributes['message_id']])
            : new EmailLog;

        // forceFill keeps the package working even when the tenant column has
        // been renamed (and therefore is not in the model's $fillable list).
        $emailLog->forceFill($attributes)->save();
    }

    protected function resolveTenantKey(Email $message): int|string|null
    {
        $fromHeader = EmailLogHeaders::getScalar($message, EmailLogHeaders::TENANT_ID);

        if ($fromHeader !== null) {
            return $fromHeader;
        }

        return EmailLogTenancy::resolveCurrentKey();
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array{address: string, name: string|null}>
     */
    protected function mapAddresses(array $addresses): array
    {
        return collect($addresses)
            ->map(fn (Address $address): array => [
                'address' => $address->getAddress(),
                'name' => $address->getName(),
            ])
            ->values()
            ->all();
    }

    protected function normalizeBody(mixed $body): ?string
    {
        if (is_resource($body)) {
            $contents = stream_get_contents($body);

            return is_string($contents) && $contents !== '' ? $contents : null;
        }

        return is_string($body) && $body !== '' ? $body : null;
    }
}
