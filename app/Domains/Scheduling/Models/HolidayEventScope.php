<?php

namespace App\Domains\Scheduling\Models;

use App\Domains\Master\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayEventScope extends Model
{
    protected $table = 'holiday_event_scopes';
    protected $primaryKey = 'holiday_event_scope_id';

    public $timestamps = false;

    protected $fillable = [
        'holiday_event_id',
        'branch_id',
        'applies_to_all_branches',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'applies_to_all_branches' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function holidayEvent(): BelongsTo
    {
        return $this->belongsTo(HolidayEvent::class, 'holiday_event_id', 'holiday_event_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}