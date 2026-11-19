<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft delete (Nov 19 2026), mirroring the Flutter repo's
 * supabase/09_trash.sql, which made these columns first (plus the
 * trash_report / restore_report / request_account_deletion /
 * cancel_account_deletion functions and the hourly purge). Added only if
 * missing, so running it against the live database changes nothing.
 */
return new class extends Migration
{
    protected $connection = 'supabase';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        // Table 15: a report in the trash since (null = not deleted), and who did it.
        if (! $schema->hasColumn('flood_reports', 'deleted_at')) {
            $schema->table('flood_reports', function (Blueprint $t) {
                $t->timestampTz('deleted_at')->nullable();
                $t->uuid('deleted_by')->nullable();
                $t->foreign('deleted_by')->references('user_id')->on('users')->nullOnDelete();
            });
        }

        // Table 14: "Delete account" was asked for at (deleted for good 7 days later).
        if (! $schema->hasColumn('users', 'deletion_requested_at')) {
            $schema->table('users', function (Blueprint $t) {
                $t->timestampTz('deletion_requested_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Shared with the live app: never dropped from here.
    }
};
