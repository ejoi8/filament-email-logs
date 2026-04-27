<?php

namespace Ejoi8\FilamentEmailLogs\Database\Factories;

use Ejoi8\FilamentEmailLogs\Models\EmailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailLog>
 */
class EmailLogFactory extends Factory
{
    protected $model = EmailLog::class;

    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(3),
            'from_address' => 'no-reply@example.com',
            'from_name' => 'Barang Digital',
            'to_addresses' => [
                [
                    'address' => fake()->safeEmail(),
                    'name' => fake()->name(),
                ],
            ],
            'cc_addresses' => [],
            'bcc_addresses' => [],
            'message_id' => fake()->uuid(),
            'original_email_log_id' => null,
            'html_body' => '<h1>Hello</h1><p>This is a logged email.</p>',
            'text_body' => 'Hello'."\n\n".'This is a logged email.',
            'resent_count' => 0,
            'sent_at' => now(),
            'last_resent_at' => null,
            'resent_by' => null,
            'resend_note' => null,
        ];
    }
}
