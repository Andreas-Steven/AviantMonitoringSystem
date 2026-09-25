<?php

namespace App\Domains\Payroll\Models;

use App\Domains\Master\Models\Employee;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Scheduling\Models\SourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDeduction extends Model
{
    protected $table = 'payroll_deductions';
    protected $primaryKey = 'payroll_deduction_id';

    protected $fillable = [
        'emp_id',
        'payroll_period_id',
        'payroll_deduction_type_id',
        'source_type_code',
        'source_ref_id',
        'description',
        'qty',
        'rate_amount',
        'amount',
        'debt_forming_flag',
        'employee_debt_id',
        'notes',
        'is_cancelled',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'rate_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'debt_forming_flag' => 'boolean',
        'is_cancelled' => 'boolean',
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

    public function deductionType(): BelongsTo
    {
        return $this->belongsTo(PayrollDeductionType::class, 'payroll_deduction_type_id', 'payroll_deduction_type_id');
    }

    public function employeeDebt(): BelongsTo
    {
        return $this->belongsTo(EmployeeDebt::class, 'employee_debt_id', 'employee_debt_id');
    }

    public function sourceType(): BelongsTo
    {
        return $this->belongsTo(SourceType::class, 'source_type_code', 'source_type_code');
    }
}