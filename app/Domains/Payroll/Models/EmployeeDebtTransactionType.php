<?php

namespace App\Domains\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeDebtTransactionType extends Model
{
    protected $table = 'employee_debt_transaction_types';
    protected $primaryKey = 'transaction_type_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'transaction_type_code',
        'transaction_type_name',
        'direction_sign',
        'notes',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(
            EmployeeDebtTransaction::class,
            'transaction_type_code',
            'transaction_type_code'
        );
    }
}