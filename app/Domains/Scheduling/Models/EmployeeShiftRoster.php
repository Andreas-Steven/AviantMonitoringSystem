<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Master\Models\Employee;

class EmployeeShiftRoster extends Model
{
    protected $table = 'employee_shift_rosters';
    protected $primaryKey = 'roster_id';

    protected $fillable = [
        'emp_id',
        'work_date',
        'shift_id',
        'source_type_code',
        'source_ref_id',
        'published_at',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'published_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'shift_id');
    }

    public function sourceType(): BelongsTo
    {
        return $this->belongsTo(SourceType::class, 'source_type_code', 'source_type_code');
    }
}