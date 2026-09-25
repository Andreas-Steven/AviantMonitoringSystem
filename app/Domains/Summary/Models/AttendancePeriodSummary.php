<?php

namespace App\Domains\Summary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Scheduling\Models\WorkPattern;

class AttendancePeriodSummary extends Model
{
    protected $table = 'attendance_period_summaries';
    protected $primaryKey = 'attendance_period_summary_id';

    protected $guarded = [];

    protected $casts = [
        'hek_count' => 'decimal:2',
        'valid_present_count' => 'decimal:2',
        'deficit_count' => 'decimal:2',
        'excess_count' => 'decimal:2',
        'overtime_day_count' => 'decimal:2',
        'leave_quota_used_count' => 'decimal:2',
        'deduction_day_count' => 'decimal:2',
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