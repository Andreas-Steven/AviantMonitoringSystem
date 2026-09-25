<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Master\Models\Employee;

class EmployeeShiftAssignment extends Model
{
    protected $table = 'employee_shift_assignments';
    protected $primaryKey = 'employee_shift_assignment_id';

    protected $fillable = [
        'emp_id',
        'shift_id',
        'assignment_type_code',
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

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'shift_id');
    }

    public function assignmentType(): BelongsTo
    {
        return $this->belongsTo(AssignmentType::class, 'assignment_type_code', 'assignment_type_code');
    }
}