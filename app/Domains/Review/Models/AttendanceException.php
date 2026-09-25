<?php

namespace App\Domains\Review\Models;

use App\Domains\Master\Models\Employee;
use App\Domains\Scheduling\Models\Shift;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceException extends Model
{
    protected $table = 'attendance_exceptions';
    protected $primaryKey = 'attendance_exception_id';

    public $timestamps = false;

    protected $fillable = [
        'emp_id',
        'work_date',
        'exception_type_code',
        'minutes_value',
        'time_value',
        'shift_id_value',
        'status_value_code',
        'reason',
        'approved_by',
        'approved_at',
        'source_type_code',
        'source_ref_id',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'time_value' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id_value', 'shift_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by', 'emp_id');
    }
}