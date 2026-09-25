<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationMode extends Model
{
    protected $table = 'evaluation_modes';
    protected $primaryKey = 'evaluation_mode_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}