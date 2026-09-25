<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domains\Scheduling\Models\EmployeeShiftRoster;

class Shift extends Model
{
    protected $table = 'shifts';
    protected $primaryKey = 'shift_id';

    protected $fillable = [
        'shift_code',
        'shift_name',
        'start_time',
        'end_time',
        'break_min',
        'default_work_min',
        'cross_day_flag',
        'active',
        'notes',
    ];

    protected $casts = [
        'cross_day_flag' => 'boolean',
        'active' => 'boolean',
    ];

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class, 'shift_id', 'shift_id');
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(EmployeeShiftRoster::class, 'shift_id', 'shift_id');
    }
}