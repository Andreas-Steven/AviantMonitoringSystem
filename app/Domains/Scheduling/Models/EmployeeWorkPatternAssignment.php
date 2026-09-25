<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Master\Models\Employee;

class EmployeeWorkPatternAssignment extends Model
{
    protected $table = 'employee_work_pattern_assignments';
    protected $primaryKey = 'employee_work_pattern_assignment_id';

    protected $fillable = [
        'emp_id',
        'work_pattern_id',
        'effective_start_date',
        'effective_end_date',
        'notes',
    ];

    protected $casts = [
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function workPattern(): BelongsTo
    {
        return $this->belongsTo(WorkPattern::class, 'work_pattern_id', 'work_pattern_id');
    }
}