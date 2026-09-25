<?php

namespace App\Domains\Requests\Models;

use App\Domains\Master\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeaveBalanceTransaction extends Model
{
    protected $table = 'employee_leave_balance_transactions';
    protected $primaryKey = 'employee_leave_balance_transaction_id';

    protected $fillable = [
        'employee_leave_balance_id',
        'emp_id',
        'leave_type_id',
        'transaction_type_code',
        'transaction_date',
        'qty',
        'source_type_code',
        'source_ref_id',
        'reverses_transaction_id',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'qty' => 'decimal:2',
    ];

    public function balance(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeLeaveBalance::class,
            'employee_leave_balance_id',
            'employee_leave_balance_id'
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id', 'leave_type_id');
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(
            LeaveBalanceTransactionType::class,
            'transaction_type_code',
            'transaction_type_code'
        );
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'reverses_transaction_id',
            'employee_leave_balance_transaction_id'
        );
    }
}