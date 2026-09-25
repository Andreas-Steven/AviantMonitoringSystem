<?php

namespace App\Domains\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Master\Models\Employee;
use App\Domains\Attendance\Models\AttendanceLogRaw;

class AttendanceNormalizedLog extends Model
{
    protected $table = 'attendance_logs_normalized';
    protected $primaryKey = 'normalized_log_id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'log_datetime' => 'datetime',
        'is_duplicate_candidate' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function rawLog(): BelongsTo
    {
        return $this->belongsTo(AttendanceLogRaw::class, 'log_id', 'log_id');
    }
}