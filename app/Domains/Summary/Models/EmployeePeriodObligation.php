<?php

namespace App\Domains\Summary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Master\Models\Employee;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Scheduling\Models\WorkPatternRule;

class EmployeePeriodObligation extends Model
{
    protected $table = 'employee_period_obligations';
    protected $primaryKey = 'employee_period_obligation_id';

    protected $guarded = [];

    protected $casts = [
        'required_count' => 'decimal:2',
        'actual_count' => 'decimal:2',
        'fulfilled_flag' => 'boolean',
        'excess_count' => 'decimal:2',
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

    public function workPatternRule(): BelongsTo
    {
        return $this->belongsTo(WorkPatternRule::class, 'work_pattern_rule_id', 'work_pattern_rule_id');
    }
}