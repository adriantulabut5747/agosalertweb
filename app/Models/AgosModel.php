<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base for every AgosAlert model: the tables live in the shared Supabase
 * database (config/database.php 'supabase'), made by the SQL files in the
 * Flutter repo's supabase/ folder. The database fills created_at /
 * updated_at itself, so Laravel's timestamps are off.
 *
 * Note: this connection logs in as the database owner, so Supabase's row
 * level security does NOT apply here. The controllers and the 'admin'
 * middleware enforce the same rules instead.
 */
abstract class AgosModel extends Model
{
    protected $connection = 'supabase';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * Runs $work as a Supabase user: inside one transaction, auth.uid()
     * returns $userId, so the database triggers fill in user_id /
     * posted_by / handled_by exactly as they do for the Flutter app.
     * ($userId null = a guest.)
     */
    public static function asUser(?string $userId, callable $work): mixed
    {
        $db = \Illuminate\Support\Facades\DB::connection('supabase');

        return $db->transaction(function () use ($db, $userId, $work) {
            $db->select("select set_config('request.jwt.claims', ?, true)", [
                json_encode($userId ? ['sub' => $userId, 'role' => 'authenticated'] : ['role' => 'anon']),
            ]);

            return $work($db);
        });
    }

    /** Postgres text[] '{"a","b"}' -> ['a', 'b'] (PDO returns arrays as text). */
    public static function pgArray(?string $value): array
    {
        if ($value === null || $value === '{}') {
            return [];
        }

        return str_getcsv(trim($value, '{}'), ',', '"', '\\');
    }

    /** ['a', 'b'] -> '{"a","b"}' for saving into a text[] column. */
    public static function toPgArray(array $items): ?string
    {
        if (! $items) {
            return null;
        }

        return '{'.implode(',', array_map(
            fn ($s) => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $s).'"',
            $items,
        )).'}';
    }
}
