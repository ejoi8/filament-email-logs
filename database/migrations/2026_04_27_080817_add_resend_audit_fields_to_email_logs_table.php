<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->foreignId('original_email_log_id')
                ->nullable()
                ->after('message_id')
                ->constrained('email_logs')
                ->nullOnDelete();
            $table->foreignId('resent_by')
                ->nullable()
                ->after('last_resent_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('resend_note')
                ->nullable()
                ->after('resent_by');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropForeign(['original_email_log_id']);
            $table->dropForeign(['resent_by']);
            $table->dropColumn([
                'original_email_log_id',
                'resent_by',
                'resend_note',
            ]);
        });
    }
};
