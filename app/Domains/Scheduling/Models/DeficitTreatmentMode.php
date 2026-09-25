<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class DeficitTreatmentMode extends Model
{
    protected $table = 'deficit_treatment_modes';
    protected $primaryKey = 'deficit_treatment_mode_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}