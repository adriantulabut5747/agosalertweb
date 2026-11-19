<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The AgosAlert tables (paper, Chapter 3, Tables 14-22) in Laravel's
 * schema builder, on the shared Supabase database.
 *
 * The real tables were made first by the Flutter repo's supabase/*.sql
 * files (they also hold the triggers, row level security and Realtime
 * that a migration can't express), so every table here is created only
 * if it doesn't exist yet: running `php artisan migrate` against the
 * live database changes nothing. On an empty Postgres database it builds
 * the same columns, keys and foreign keys.
 */
return new class extends Migration
{
    protected $connection = 'supabase';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        // Table 14. Accounts: one row per Supabase Auth user (made by a
        // trigger on sign-up in supabase/01_users.sql).
        if (! $schema->hasTable('users')) {
            $schema->create('users', function (Blueprint $t) {
                $t->uuid('user_id')->primary();
                $t->string('full_name', 100);
                $t->string('email', 255)->unique();
                $t->string('phone_number', 15)->nullable();
                $t->string('barangay', 50);
                $t->string('role', 20)->default('registered');           // registered | admin
                $t->string('account_status', 20)->default('active');     // active | suspended
                $t->timestampTz('created_at')->useCurrent();
            });
        }

        // Table 15. Flood reports, deleted 14 days after the photo (FR-S1).
        if (! $schema->hasTable('flood_reports')) {
            $schema->create('flood_reports', function (Blueprint $t) {
                $t->bigIncrements('report_id');
                $t->foreignUuid('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $t->string('incident_type', 30);
                $t->string('severity', 10);                              // minor | moderate | high
                $t->smallInteger('depth_cm')->nullable();
                $t->string('barangay', 50);
                $t->decimal('latitude', 9, 6);
                $t->decimal('longitude', 9, 6);
                $t->text('note')->nullable();
                $t->text('photo_url')->nullable();
                $t->timestampTz('photo_taken_at');
                $t->timestampTz('expires_at')->index();
                $t->string('status', 10)->default('unverified');         // unverified | verified | removed
                $t->foreignUuid('reviewed_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $t->timestampTz('created_at')->useCurrent();
            });
        }

        // Table 16. One vote per user per report: 1 = up, -1 = down.
        if (! $schema->hasTable('report_votes')) {
            $schema->create('report_votes', function (Blueprint $t) {
                $t->foreignId('report_id')->constrained('flood_reports', 'report_id')->cascadeOnDelete();
                $t->foreignUuid('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $t->smallInteger('vote');
                $t->timestampTz('created_at')->useCurrent();
                $t->primary(['report_id', 'user_id']);
            });
        }

        // Table 19. Alerts posted by admins.
        if (! $schema->hasTable('alerts')) {
            $schema->create('alerts', function (Blueprint $t) {
                $t->bigIncrements('alert_id');
                $t->string('title', 120);
                $t->string('summary', 255);
                $t->text('details');
                $t->string('category', 20);                              // flood | weather | road | shelter
                $t->string('severity', 10);                              // normal | moderate | high
                $t->string('area', 100);
                $t->text('actions')->nullable();                         // text[] in the SQL file
                $t->foreignUuid('posted_by')->constrained('users', 'user_id');
                $t->timestampTz('created_at')->useCurrent()->index();
            });
        }

        // Table 20. Which alerts each user has opened (the bell's count).
        if (! $schema->hasTable('alert_reads')) {
            $schema->create('alert_reads', function (Blueprint $t) {
                $t->foreignUuid('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $t->foreignId('alert_id')->constrained('alerts', 'alert_id')->cascadeOnDelete();
                $t->timestampTz('read_at')->useCurrent();
                $t->primary(['user_id', 'alert_id']);
            });
        }

        // Table 21. Evacuation centers (no capacity / evacuee counts: nobody
        // can know them; is_sample marks made-up demo rows).
        if (! $schema->hasTable('evacuation_centers')) {
            $schema->create('evacuation_centers', function (Blueprint $t) {
                $t->bigIncrements('center_id');
                $t->string('name', 120);
                $t->string('barangay', 50);
                $t->decimal('latitude', 9, 6);
                $t->decimal('longitude', 9, 6);
                $t->string('status', 10)->default('standby');            // open | standby | closed
                $t->text('photo_url')->nullable();
                $t->boolean('is_sample')->default(false);
                $t->foreignUuid('updated_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $t->timestampTz('updated_at')->useCurrent();
            });
        }

        // Table 17. Rescue requests (guests too: user_id null).
        if (! $schema->hasTable('rescue_requests')) {
            $schema->create('rescue_requests', function (Blueprint $t) {
                $t->bigIncrements('request_id');
                $t->foreignUuid('user_id')->nullable()->constrained('users', 'user_id')->cascadeOnDelete();
                $t->string('contact_name', 100);
                $t->string('contact_phone', 15);
                $t->string('barangay', 50);
                $t->decimal('latitude', 9, 6)->nullable();
                $t->decimal('longitude', 9, 6)->nullable();
                $t->smallInteger('people_count');
                $t->text('special_needs')->nullable();                   // text[] in the SQL file
                $t->text('note')->nullable();
                $t->string('status', 20)->default('received');
                $t->foreignUuid('handled_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $t->timestampTz('created_at')->useCurrent()->index();
                $t->timestampTz('updated_at')->useCurrent();
            });
        }

        // Table 18. Numbers confirmed to have sent a false request.
        if (! $schema->hasTable('flagged_numbers')) {
            $schema->create('flagged_numbers', function (Blueprint $t) {
                $t->string('phone_number', 15)->primary();
                $t->foreignId('request_id')->constrained('rescue_requests', 'request_id')->cascadeOnDelete();
                $t->text('reason')->nullable();
                $t->foreignUuid('flagged_by')->constrained('users', 'user_id');
                $t->timestampTz('flagged_at')->useCurrent();
            });
        }

        // Table 22 (device_tokens, push notifications) isn't built yet in
        // either app.
    }

    /** Never drops the shared tables: the Flutter app uses them too. */
    public function down(): void {}
};
