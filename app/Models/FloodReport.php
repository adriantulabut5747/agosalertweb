<?php

namespace App\Models;

use App\Support\AgosData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One row of `flood_reports` (paper, Table 15). Deleted 14 days after the photo (FR-S1). */
class FloodReport extends AgosModel
{
    protected $primaryKey = 'report_id';

    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'photo_taken_at' => 'datetime',
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

    /** What everyone sees: not removed, not expired. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', '<>', 'removed')->where('expires_at', '>', now());
    }

    /** normal / moderate / high from the water depth (FR-S2). */
    public function risk(): string
    {
        return $this->depth_cm === null ? 'normal' : AgosData::riskForDepth($this->depth_cm);
    }
}
