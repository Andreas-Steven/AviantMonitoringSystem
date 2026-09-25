<?php

namespace App\Domains\Summary\Models;

use App\Domains\Master\Models\Employee;
use App\Domains\Scheduling\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAttendanceAmount extends Model
{
    protected $table = 'payroll_attendance_amounts';
    protected $primaryKey = 'payroll_attendance_amount_id';

    protected $guarded = [];

    protected $casts = [
        'deduction_day_payable' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'net_attendance_amount' => 'decimal:2',
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
}