<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class MissingAttendancePolicy extends Model
{
    protected $table = 'missing_attendance_policies';
    protected $primaryKey = 'missing_attendance_policy_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}