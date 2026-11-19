<?php

namespace App\Models;

/** One row of `flagged_numbers` (paper, Table 18): confirmed prank numbers. */
class FlaggedNumber extends AgosModel
{
    protected $primaryKey = 'phone_number';

    protected $keyType = 'string';

    public $incrementing = false;
}
