<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class ExcessTreatmentMode extends Model
{
    protected $table = 'excess_treatment_modes';
    protected $primaryKey = 'excess_treatment_mode_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}