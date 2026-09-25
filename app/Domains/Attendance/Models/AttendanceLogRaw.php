<?php

namespace App\Domains\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Domains\Master\Models\Employee;
use App\Domains\Master\Models\Branch;

class AttendanceLogRaw extends Model
{
    protected $table = 'attendance_logs_raw';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'log_datetime' => 'datetime',
        'log_date' => 'date',
        'raw_payload' => 'array',
        'ingested_at' => 'datetime',
    ];

    public function normalizedLog(): HasOne
    {
        return $this->hasOne(AttendanceNormalizedLog::class, 'log_id', 'log_id');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(AttendanceImportBatch::class, 'attendance_import_batch_id', 'attendance_import_batch_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'location_branch_id', 'branch_id');
    }
}