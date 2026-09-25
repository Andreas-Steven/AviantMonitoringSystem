<?php

namespace App\Domains\Payroll\Models;

use App\Domains\Master\Models\Employee;
use App\Domains\Scheduling\Models\SourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeDebt extends Model
{
    protected $table = 'employee_debts';
    protected $primaryKey = 'employee_debt_id';

    protected $fillable = [
        'emp_id',
        'debt_code',
        'debt_name',
        'debt_category_code',
        'origin_date',
        'original_amount',
        'outstanding_amount',
        'status_code',
        'source_type_code',
        'source_ref_id',
        'notes',
    ];

    protected $casts = [
        'origin_date' => 'date',
        'original_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function sourceType(): BelongsTo
    {
        return $this->belongsTo(SourceType::class, 'source_type_code', 'source_type_code');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            EmployeeDebtTransaction::class,
            'employee_debt_id',
            'employee_debt_id'
        );
    }

    public function payrollDeductions(): HasMany
    {
        return $this->hasMany(
            PayrollDeduction::class,
            'employee_debt_id',
            'employee_debt_id'
        );
    }
}