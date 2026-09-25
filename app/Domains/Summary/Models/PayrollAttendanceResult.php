<?php

namespace App\Domains\Summary\Models;

use App\Domains\Master\Models\Branch;
use App\Domains\Master\Models\Employee;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Scheduling\Models\WorkPattern;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAttendanceResult extends Model
{
    protected $table = 'payroll_attendance_results';
    protected $primaryKey = 'payroll_attendance_result_id';

    protected $guarded = [];

    protected $casts = [
        'deduction_day_payable' => 'decimal:2',
        'obligation_unfulfilled_count' => 'decimal:2',
        'obligation_excess_count' => 'decimal:2',
        'calculated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id', 'payroll_period_id');
    }

    public function workPattern(): BelongsTo
    {
        return $this->belongsTo(WorkPattern::class, 'work_pattern_id', 'work_pattern_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}