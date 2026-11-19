<?php

namespace App\Models;

use App\Support\AgosData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;

/**
 * One row of `flood_reports` (paper, Table 15). Public for 14 days after
 * the photo (FR-S1); then, or when deleted, it sits in the trash for
 * TRASH_DAYS before the purge function erases it (supabase/09_trash.sql).
 */
class FloodReport extends AgosModel
{
    /** Same as kTrashDays in the app. */
    public const TRASH_DAYS = 30;

    protected $primaryKey = 'report_id';

    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'photo_taken_at' => 'datetime',
        'deleted_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReportVote::class, 'report_id');
    }

    /**
     * What everyone sees, same filter as the flood_report_feed view: not
     * removed, not expired, not in the trash, poster not being deleted.
     */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', '<>', 'removed')->where('expires_at', '>', now())->whereNull('deleted_at')
            ->whereHas('user', fn ($u) => $u->whereNull('deletion_requested_at'));
    }

    /** Deleted, or past its 14 days. */
    public function scopeTrashed(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->whereNotNull('deleted_at')->orWhere('expires_at', '<=', now()));
    }

    public function expired(): bool
    {
        return $this->expires_at->lte(now());
    }

    public function inTrash(): bool
    {
        return $this->deleted_at !== null || $this->expired();
    }

    /** When it went in the trash (whichever came first). */
    public function trashedAt(): \Carbon\CarbonInterface
    {
        return $this->deleted_at && $this->deleted_at->lt($this->expires_at) ? $this->deleted_at : $this->expires_at;
    }

    public function erasesAt(): \Carbon\CarbonInterface
    {
        return $this->trashedAt()->copy()->addDays(self::TRASH_DAYS);
    }

    /**
     * "Delete now": the photo first (with the user's Supabase token: the
     * bucket's rules check it), then the row. False = the photo couldn't
     * be deleted (old token), so the row stays rather than orphan a
     * public photo.
     */
    public function eraseForGood(string $token): bool
    {
        if ($this->photo_url && str_contains($this->photo_url, '/report-photos/')) {
            $res = Http::withToken($token)->withHeaders(['apikey' => config('services.supabase.key')])
                ->delete(config('services.supabase.url').'/storage/v1/object/report-photos/'
                    .explode('/report-photos/', $this->photo_url)[1]);
            if (! $res->successful() && ! str_contains(strtolower($res->body()), 'not found')) {
                return false;
            }
        }
        $this->delete();

        return true;
    }

    /** Same rule as restore_report(): not expired; owners only get back what they deleted. */
    public function canRestore(AppUser $me): bool
    {
        return $this->deleted_at !== null && ! $this->expired()
            && ($me->isAdmin() || ($this->user_id === $me->user_id && $this->deleted_by === $me->user_id));
    }

    /** normal / moderate / high from the water depth (FR-S2). */
    public function risk(): string
    {
        return $this->depth_cm === null ? 'normal' : AgosData::riskForDepth($this->depth_cm);
    }
}
