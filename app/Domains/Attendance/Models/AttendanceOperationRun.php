<?php

namespace App\Domains\Attendance\Models;

use App\Domains\Access\Models\AppUser;
use App\Domains\Scheduling\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceOperationRun extends Model
{
    protected $table = 'attendance_operation_runs';
    protected $primaryKey = 'attendance_operation_run_id';

    protected $fillable = [
        'operation_type_code',
        'payroll_period_id',
        'date_from',
        'date_to',
        'triggered_by',
        'operation_status_code',
        'result_json',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'result_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id', 'payroll_period_id');
    }

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'triggered_by', 'user_id');
    }
}