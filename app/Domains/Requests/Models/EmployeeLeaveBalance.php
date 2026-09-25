<?php

namespace App\Domains\Requests\Models;

use App\Domains\Master\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeLeaveBalance extends Model
{
    protected $table = 'employee_leave_balances';
    protected $primaryKey = 'employee_leave_balance_id';

    protected $fillable = [
        'emp_id',
        'leave_type_id',
        'period_start_date',
        'period_end_date',
        'opening_balance',
        'granted_amount',
        'used_amount',
        'adjustment_amount',
        'expired_amount',
        'closing_balance',
        'active',
        'notes',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'opening_balance' => 'decimal:2',
        'granted_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'expired_amount' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id', 'leave_type_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            EmployeeLeaveBalanceTransaction::class,
            'employee_leave_balance_id',
            'employee_leave_balance_id'
        );
    }

    public function getAvailableBalanceAttribute(): float
    {
        return max(
            0,
            (float) $this->opening_balance
            + (float) $this->granted_amount
            + (float) $this->adjustment_amount
            - (float) $this->used_amount
            - (float) $this->expired_amount
        );
    }
}