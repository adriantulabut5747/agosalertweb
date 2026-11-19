<?php

namespace App\Models;

use App\Support\AgosData;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row of `rescue_requests` (paper, Table 17). Deleted after 30 days. */
class RescueRequest extends AgosModel
{
    protected $primaryKey = 'request_id';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function needs(): array
    {
        return array_map(fn ($n) => AgosData::RESCUE_NEEDS[$n] ?? $n, self::pgArray($this->special_needs));
    }

    public function statusLabel(): string
    {
        return AgosData::RESCUE_STATUSES[$this->status] ?? $this->status;
    }
}
