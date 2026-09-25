<?php

namespace App\Domains\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Models\AttendancePolicy;
use App\Domains\Scheduling\Models\Shift;

class AttendanceDaily extends Model
{
    protected $table = 'attendance_daily';
    protected $primaryKey = 'attendance_daily_id';

    protected $guarded = [];

    protected $casts = [
        'work_date' => 'date',
        'scheduled_in_datetime' => 'datetime',
        'scheduled_out_datetime' => 'datetime',
        'actual_in_datetime' => 'datetime',
        'actual_out_datetime' => 'datetime',
        'anomaly_flag' => 'boolean',
        'exception_flag' => 'boolean',
        'leave_flag' => 'boolean',
        'pattern_flag' => 'boolean',
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

    public function policy(): BelongsTo
    {
        return $this->belongsTo(AttendancePolicy::class, 'policy_id', 'policy_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'shift_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(AttendanceDailyDetail::class, 'attendance_daily_id', 'attendance_daily_id');
    }
}