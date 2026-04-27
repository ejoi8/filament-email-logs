<?php

namespace Ejoi8\FilamentEmailLogs\Services;

use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Ejoi8\FilamentEmailLogs\Support\EmailLogHeaders;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Mail\Message;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

class EmailLogResender
{
    /**
     * @param  array<int, string>  $overrideToAddresses
     */
    public function resend(
        EmailLog $emailLog,
        array $overrideToAddresses = [],
        bool $sendToOriginalRecipients = true,
        ?Authenticatable $resentBy = null,
        ?string $resendNote = null,
    ): EmailLogResendResult {
        if (! $emailLog->hasStoredBody()) {
            throw new InvalidArgumentException('No stored email body is available for this email log.');
        }

        $toAddresses = $this->resolveToAddresses(
            $emailLog,
            $overrideToAddresses,
            $sendToOriginalRecipients,
        );

        if ($toAddresses === []) {
            throw new InvalidArgumentException('No recipients are stored for this email log.');
        }

        $view = [];

        if (filled($emailLog->html_body)) {
            $view['html'] = new HtmlString($emailLog->html_body);
        }

        if (filled($emailLog->text_body)) {
            $view['raw'] = $emailLog->text_body;
        }

        $sentMessage = Mail::send($view, [], function (Message $message) use ($emailLog, $toAddresses, $sendToOriginalRecipients, $resentBy, $resendNote): void {
            $this->applyAddresses($message, $toAddresses, 'to');

            $ccAddresses = $sendToOriginalRecipients
                ? $this->prepareAddresses($emailLog->cc_addresses)
                : [];

            if ($ccAddresses !== []) {
                $this->applyAddresses($message, $ccAddresses, 'cc');
            }

            $bccAddresses = $sendToOriginalRecipients
                ? $this->prepareAddresses($emailLog->bcc_addresses)
                : [];

            if ($bccAddresses !== []) {
                $this->applyAddresses($message, $bccAddresses, 'bcc');
            }

            if (filled($emailLog->from_address)) {
                $message->from($emailLog->from_address, $emailLog->from_name);
            }

            $message->subject((string) ($emailLog->subject ?: 'Resent Email'));

            $headers = $message->getHeaders();
            $headers->addTextHeader(EmailLogHeaders::ORIGINAL_EMAIL_LOG_ID, (string) $emailLog->getKey());

            if ($resentBy !== null) {
                $headers->addTextHeader(EmailLogHeaders::RESENT_BY_ID, (string) $resentBy->getKey());
            }

            if (filled($resendNote)) {
                $headers->addTextHeader(EmailLogHeaders::RESEND_NOTE, trim((string) $resendNote));
            }
        });

        $emailLog->forceFill([
            'resent_count' => $emailLog->resent_count + 1,
            'last_resent_at' => now(),
        ])->save();

        return new EmailLogResendResult(
            $toAddresses,
            $this->findResentEmailLog($sentMessage),
        );
    }

    /**
     * @param  array<int, array{address?: string, name?: string|null}>|null  $addresses
     * @return array<int, array{address: string, name?: string|null}>
     */
    protected function prepareAddresses(?array $addresses): array
    {
        return collect($addresses ?? [])
            ->map(function (array $address): ?array {
                $email = $address['address'] ?? null;

                if (blank($email)) {
                    return null;
                }

                $formattedAddress = ['address' => $email];

                if (filled($address['name'] ?? null)) {
                    $formattedAddress['name'] = $address['name'];
                }

                return $formattedAddress;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $overrideToAddresses
     * @return array<int, array{address: string, name?: string|null}>
     */
    protected function resolveToAddresses(
        EmailLog $emailLog,
        array $overrideToAddresses,
        bool $sendToOriginalRecipients,
    ): array {
        $originalToAddresses = $sendToOriginalRecipients
            ? $this->prepareAddresses($emailLog->to_addresses)
            : [];

        $manualToAddresses = collect($overrideToAddresses)
            ->map(function (string $address): ?array {
                $normalizedAddress = trim($address);

                if ($normalizedAddress === '') {
                    return null;
                }

                return ['address' => $normalizedAddress];
            })
            ->filter()
            ->values()
            ->all();

        return $this->deduplicateAddresses([
            ...$originalToAddresses,
            ...$manualToAddresses,
        ]);
    }

    /**
     * @param  array<int, array{address: string, name?: string|null}>  $addresses
     * @return array<int, array{address: string, name?: string|null}>
     */
    protected function deduplicateAddresses(array $addresses): array
    {
        return collect($addresses)
            ->unique(fn (array $address): string => mb_strtolower($address['address']))
            ->values()
            ->all();
    }

    protected function findResentEmailLog(?SentMessage $sentMessage): ?EmailLog
    {
        $messageId = $sentMessage?->getMessageId();

        if (blank($messageId)) {
            return null;
        }

        return EmailLog::query()
            ->where('message_id', $messageId)
            ->first();
    }

    /**
     * @param  array<int, array{address: string, name?: string|null}>  $addresses
     */
    protected function applyAddresses(Message $message, array $addresses, string $type): void
    {
        foreach ($addresses as $address) {
            $message->{$type}($address['address'], $address['name'] ?? null);
        }
    }
}
