<?php

namespace App\Domains\Requests\Models;

use App\Domains\Master\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends Model
{
    protected $table = 'overtime_requests';
    protected $primaryKey = 'overtime_request_id';

    protected $fillable = [
        'emp_id',
        'work_date',
        'planned_start_datetime',
        'planned_end_datetime',
        'actual_start_datetime',
        'actual_end_datetime',
        'reason',
        'request_status_code',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'planned_start_datetime' => 'datetime',
        'planned_end_datetime' => 'datetime',
        'actual_start_datetime' => 'datetime',
        'actual_end_datetime' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by', 'emp_id');
    }
}