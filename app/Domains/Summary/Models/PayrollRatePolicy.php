<?php

namespace App\Domains\Summary\Models;

use App\Domains\Master\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRatePolicy extends Model
{
    protected $table = 'payroll_rate_policies';
    protected $primaryKey = 'payroll_rate_policy_id';

    protected $guarded = [];

    protected $casts = [
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
        'overtime_rate_per_min' => 'decimal:2',
        'deduction_rate_per_day' => 'decimal:2',
        'workday_ot_multiplier' => 'decimal:2',
        'holiday_ot_multiplier' => 'decimal:2',
        'offday_ot_multiplier' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}