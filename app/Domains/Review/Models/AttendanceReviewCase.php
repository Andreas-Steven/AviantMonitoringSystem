<?php

namespace App\Domains\Review\Models;

use App\Domains\Master\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceReviewCase extends Model
{
    protected $table = 'attendance_review_cases';
    protected $primaryKey = 'review_case_id';

    protected $fillable = [
        'emp_id',
        'work_date',
        'case_type_code',
        'severity_code',
        'detected_at',
        'review_status_code',
        'resolution_type_code',
        'resolved_by',
        'resolved_at',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'resolved_by', 'emp_id');
    }
}