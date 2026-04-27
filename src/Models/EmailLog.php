<?php

namespace Ejoi8\FilamentEmailLogs\Models;

use Ejoi8\FilamentEmailLogs\Database\Factories\EmailLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;

#[Fillable([
    'subject',
    'from_address',
    'from_name',
    'to_addresses',
    'cc_addresses',
    'bcc_addresses',
    'message_id',
    'original_email_log_id',
    'html_body',
    'text_body',
    'resent_count',
    'sent_at',
    'last_resent_at',
    'resent_by',
    'resend_note',
])]
class EmailLog extends Model
{
    /** @use HasFactory<EmailLogFactory> */
    use HasFactory;

    protected static function newFactory(): EmailLogFactory
    {
        return EmailLogFactory::new();
    }

    public function originalEmailLog(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_email_log_id');
    }

    public function resentEmails(): HasMany
    {
        return $this->hasMany(self::class, 'original_email_log_id');
    }

    public function resentBy(): BelongsTo
    {
        return $this->belongsTo($this->getUserModelClass(), 'resent_by');
    }

    public function fromSummary(): string
    {
        if (blank($this->from_address)) {
            return '-';
        }

        return filled($this->from_name)
            ? "{$this->from_name} <{$this->from_address}>"
            : (string) $this->from_address;
    }

    public function toSummary(): string
    {
        return self::formatAddressList($this->to_addresses);
    }

    public function ccSummary(): string
    {
        return self::formatAddressList($this->cc_addresses);
    }

    public function bccSummary(): string
    {
        return self::formatAddressList($this->bcc_addresses);
    }

    public function hasStoredBody(): bool
    {
        return filled($this->html_body) || filled($this->text_body);
    }

    public function isResentCopy(): bool
    {
        return $this->original_email_log_id !== null;
    }

    public function deliveryTypeLabel(): string
    {
        return $this->isResentCopy() ? 'Resent' : 'Original';
    }

    /**
     * @param  array<int, array{address?: string, name?: string|null}>|null  $addresses
     */
    public static function formatAddressList(?array $addresses): string
    {
        $formatted = collect($addresses ?? [])
            ->map(function (array $address): ?string {
                $email = $address['address'] ?? null;

                if (blank($email)) {
                    return null;
                }

                $name = $address['name'] ?? null;

                return filled($name) ? "{$name} <{$email}>" : $email;
            })
            ->filter()
            ->implode(', ');

        return $formatted !== '' ? $formatted : '-';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'to_addresses' => 'array',
            'cc_addresses' => 'array',
            'bcc_addresses' => 'array',
            'original_email_log_id' => 'integer',
            'resent_count' => 'integer',
            'sent_at' => 'datetime',
            'last_resent_at' => 'datetime',
            'resent_by' => 'integer',
        ];
    }

    protected function getUserModelClass(): string
    {
        return (string) (config('auth.providers.users.model') ?? User::class);
    }
}
