<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domains\Scheduling\Models\EmployeeShiftAssignment;
use App\Domains\Scheduling\Models\EmployeeWorkPatternAssignment;
use App\Domains\Scheduling\Models\EmployeeShiftRoster;

class Employee extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'emp_id';

    protected $fillable = [
        'emp_code',
        'biometric_code',
        'full_name',
        'employment_type_id',
        'active',
        'join_date',
        'resign_date',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
        'join_date' => 'date',
        'resign_date' => 'date',
    ];

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class, 'employment_type_id', 'employment_type_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class, 'emp_id', 'emp_id');
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class, 'emp_id', 'emp_id');
    }

    public function workPatternAssignments(): HasMany
    {
        return $this->hasMany(EmployeeWorkPatternAssignment::class, 'emp_id', 'emp_id');
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(EmployeeShiftRoster::class, 'emp_id', 'emp_id');
    }

    public function appUsers(): HasMany
    {
        return $this->hasMany(\App\Domains\Access\Models\AppUser::class, 'employee_id', 'emp_id');
    }    
}