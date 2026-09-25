<?php

namespace App\Domains\Payroll\Models;

use App\Domains\Master\Models\Employee;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Scheduling\Models\SourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDebtTransaction extends Model
{
    protected $table = 'employee_debt_transactions';
    protected $primaryKey = 'employee_debt_transaction_id';

    protected $fillable = [
        'employee_debt_id',
        'emp_id',
        'transaction_type_code',
        'transaction_date',
        'amount',
        'payment_source_code',
        'payroll_period_id',
        'payroll_deduction_id',
        'source_type_code',
        'source_ref_id',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function debt(): BelongsTo
    {
        return $this->belongsTo(EmployeeDebt::class, 'employee_debt_id', 'employee_debt_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeDebtTransactionType::class,
            'transaction_type_code',
            'transaction_type_code'
        );
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id', 'payroll_period_id');
    }

    public function payrollDeduction(): BelongsTo
    {
        return $this->belongsTo(PayrollDeduction::class, 'payroll_deduction_id', 'payroll_deduction_id');
    }

    public function sourceType(): BelongsTo
    {
        return $this->belongsTo(SourceType::class, 'source_type_code', 'source_type_code');
    }
}