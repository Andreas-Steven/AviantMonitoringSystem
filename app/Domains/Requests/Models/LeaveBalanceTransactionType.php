<?php

namespace App\Domains\Requests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveBalanceTransactionType extends Model
{
    protected $table = 'leave_balance_transaction_types';
    protected $primaryKey = 'transaction_type_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'transaction_type_code',
        'transaction_type_name',
        'notes',
    ];

    public function leaveBalanceTransactions(): HasMany
    {
        return $this->hasMany(
            EmployeeLeaveBalanceTransaction::class,
            'transaction_type_code',
            'transaction_type_code'
        );
    }
}