<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        $addedMessageIdColumn = false;
        $addedSentAtColumn = false;

        Schema::table('email_logs', function (Blueprint $table) use (&$addedMessageIdColumn, &$addedSentAtColumn): void {
            if (! Schema::hasColumn('email_logs', 'subject')) {
                $table->string('subject')->nullable()->after('id');
            }

            if (! Schema::hasColumn('email_logs', 'from_address')) {
                $table->string('from_address')->nullable()->after('subject');
            }

            if (! Schema::hasColumn('email_logs', 'from_name')) {
                $table->string('from_name')->nullable()->after('from_address');
            }

            if (! Schema::hasColumn('email_logs', 'to_addresses')) {
                $table->json('to_addresses')->nullable()->after('from_name');
            }

            if (! Schema::hasColumn('email_logs', 'cc_addresses')) {
                $table->json('cc_addresses')->nullable()->after('to_addresses');
            }

            if (! Schema::hasColumn('email_logs', 'bcc_addresses')) {
                $table->json('bcc_addresses')->nullable()->after('cc_addresses');
            }

            if (! Schema::hasColumn('email_logs', 'message_id')) {
                $table->string('message_id')->nullable()->after('bcc_addresses');
                $addedMessageIdColumn = true;
            }

            if (! Schema::hasColumn('email_logs', 'html_body')) {
                $table->longText('html_body')->nullable()->after('message_id');
            }

            if (! Schema::hasColumn('email_logs', 'text_body')) {
                $table->longText('text_body')->nullable()->after('html_body');
            }

            if (! Schema::hasColumn('email_logs', 'resent_count')) {
                $table->unsignedInteger('resent_count')->default(0)->after('text_body');
            }

            if (! Schema::hasColumn('email_logs', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('resent_count');
                $addedSentAtColumn = true;
            }

            if (! Schema::hasColumn('email_logs', 'last_resent_at')) {
                $table->timestamp('last_resent_at')->nullable()->after('sent_at');
            }
        });

        if ($addedMessageIdColumn || $addedSentAtColumn) {
            Schema::table('email_logs', function (Blueprint $table) use ($addedMessageIdColumn, $addedSentAtColumn): void {
                if ($addedMessageIdColumn) {
                    $table->index('message_id');
                }

                if ($addedSentAtColumn) {
                    $table->index('sent_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('email_logs', 'message_id')) {
                $table->dropIndex(['message_id']);
            }

            if (Schema::hasColumn('email_logs', 'sent_at')) {
                $table->dropIndex(['sent_at']);
            }

            $columns = [
                'subject',
                'from_address',
                'from_name',
                'to_addresses',
                'cc_addresses',
                'bcc_addresses',
                'message_id',
                'html_body',
                'text_body',
                'resent_count',
                'sent_at',
                'last_resent_at',
            ];

            $existingColumns = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('email_logs', $column),
            ));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
