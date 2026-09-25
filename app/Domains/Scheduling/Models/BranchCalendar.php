<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Master\Models\Branch;

class BranchCalendar extends Model
{
    protected $table = 'branch_calendars';
    protected $primaryKey = 'branch_calendar_id';

    protected $fillable = [
        'branch_id',
        'work_date',
        'day_type_code',
        'day_name',
        'is_workday',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'is_workday' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function dayType(): BelongsTo
    {
        return $this->belongsTo(DayType::class, 'day_type_code', 'day_type_code');
    }
}