<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class OvertimeRoundingMode extends Model
{
    protected $table = 'overtime_rounding_modes';
    protected $primaryKey = 'overtime_rounding_mode_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}