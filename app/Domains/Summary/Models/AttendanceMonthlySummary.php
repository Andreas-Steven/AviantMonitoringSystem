<?php

namespace App\Domains\Summary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\Branch;

class AttendanceMonthlySummary extends Model
{
    protected $table = 'attendance_monthly_summary';
    protected $primaryKey = 'summary_id';

    protected $guarded = [];

    protected $casts = [
        'present_days' => 'decimal:2',
        'absent_days' => 'decimal:2',
        'leave_days' => 'decimal:2',
        'sick_days' => 'decimal:2',
        'permission_days' => 'decimal:2',
        'calculated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}