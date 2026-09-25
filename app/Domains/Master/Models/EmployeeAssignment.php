<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAssignment extends Model
{
    protected $table = 'employee_assignments';
    protected $primaryKey = 'assignment_id';

    protected $fillable = [
        'emp_id',
        'branch_id',
        'dept_id',
        'role_id',
        'grade_id',
        'effective_start_date',
        'effective_end_date',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
        'is_primary' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id', 'dept_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(PositionRole::class, 'role_id', 'role_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id', 'grade_id');
    }
}