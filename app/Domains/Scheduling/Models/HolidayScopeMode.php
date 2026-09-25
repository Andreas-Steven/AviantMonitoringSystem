<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayScopeMode extends Model
{
    protected $table = 'holiday_scope_modes';
    protected $primaryKey = 'holiday_scope_mode_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}