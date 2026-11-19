<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row of `alerts` (paper, Table 19). Posted by admins. */
class Alert extends AgosModel
{
    protected $primaryKey = 'alert_id';

    protected $casts = ['created_at' => 'datetime'];

    public function poster(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'posted_by');
    }

    /** The "what to do" steps (a text[] column). */
    public function steps(): array
    {
        return self::pgArray($this->actions);
    }
}
