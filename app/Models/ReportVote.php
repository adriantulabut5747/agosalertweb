<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row of `report_votes` (paper, Table 16): vote = 1 (thumbs up) or -1. */
class ReportVote extends AgosModel
{
    protected $primaryKey = null;

    public $incrementing = false;

    public function report(): BelongsTo
    {
        return $this->belongsTo(FloodReport::class, 'report_id');
    }
}
