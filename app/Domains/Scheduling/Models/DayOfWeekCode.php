<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class DayOfWeekCode extends Model
{
    protected $table = 'day_of_week_codes';
    protected $primaryKey = 'day_of_week_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}