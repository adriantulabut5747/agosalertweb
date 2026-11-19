<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/** One row of `users` (paper, Table 14). Made by Supabase's sign-up trigger. */
class AppUser extends AgosModel
{
    /** Days between "Delete account" and the purge deleting it (kAccountDeleteDays). */
    public const DELETE_DAYS = 7;

    protected $table = 'users';

    protected $primaryKey = 'user_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = ['created_at' => 'datetime', 'deletion_requested_at' => 'datetime'];

    public function floodReports(): HasMany
    {
        return $this->hasMany(FloodReport::class, 'user_id');
    }

    public function rescueRequests(): HasMany
    {
        return $this->hasMany(RescueRequest::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' && $this->account_status === 'active';
    }

    /** When the account goes for good, if deletion was asked for. */
    public function deletesAt(): ?\Carbon\CarbonInterface
    {
        return $this->deletion_requested_at?->copy()->addDays(self::DELETE_DAYS);
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->full_name));
        $first = mb_substr($words[0], 0, 1);

        return mb_strtoupper(count($words) > 1 ? $first.mb_substr(end($words), 0, 1) : $first);
    }
}
