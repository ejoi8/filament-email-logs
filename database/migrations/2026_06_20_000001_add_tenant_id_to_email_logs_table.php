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

        $column = $this->tenantColumn();

        if (Schema::hasColumn('email_logs', $column)) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) use ($column): void {
            $table->unsignedBigInteger($column)->nullable()->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        $column = $this->tenantColumn();

        if (! Schema::hasColumn('email_logs', $column)) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) use ($column): void {
            $table->dropColumn($column);
        });
    }

    protected function tenantColumn(): string
    {
        $column = config('filament-email-logs.tenancy.column', 'tenant_id');

        return (is_string($column) && $column !== '') ? $column : 'tenant_id';
    }
};
