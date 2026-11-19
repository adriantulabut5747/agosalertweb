<?php

namespace App\Models;

/** One row of `evacuation_centers` (paper, Table 21, minus capacity / evacuees). */
class EvacuationCenter extends AgosModel
{
    protected $primaryKey = 'center_id';

    protected $casts = [
        'updated_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_sample' => 'boolean',
    ];
}
