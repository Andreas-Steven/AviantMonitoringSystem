<?php

namespace App\Domains\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollDeductionType extends Model
{
    protected $table = 'payroll_deduction_types';
    protected $primaryKey = 'payroll_deduction_type_id';

    protected $fillable = [
        'deduction_code',
        'deduction_name',
        'category_code',
        'debt_forming_default_flag',
        'active',
        'notes',
    ];

    protected $casts = [
        'debt_forming_default_flag' => 'boolean',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function deductions(): HasMany
    {
        return $this->hasMany(
            PayrollDeduction::class,
            'payroll_deduction_type_id',
            'payroll_deduction_type_id'
        );
    }
}