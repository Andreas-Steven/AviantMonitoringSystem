<?php

namespace App\Domains\Requests\Models;

use App\Domains\Master\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeaveRequest extends Model
{
    protected $table = 'employee_leave_requests';
    protected $primaryKey = 'leave_request_id';

    protected $fillable = [
        'emp_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'partial_day_flag',
        'partial_start_time',
        'partial_end_time',
        'reason',
        'attachment_url',
        'request_status_code',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'partial_day_flag' => 'boolean',
        'partial_start_time' => 'datetime:H:i',
        'partial_end_time' => 'datetime:H:i',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id', 'leave_type_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by', 'emp_id');
    }
}