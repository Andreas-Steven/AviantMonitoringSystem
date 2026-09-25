<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolidayEvent extends Model
{
    protected $table = 'holiday_events';
    protected $primaryKey = 'holiday_event_id';

    protected $fillable = [
        'holiday_code',
        'holiday_name',
        'holiday_date',
        'day_type_code',
        'active',
        'notes',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'active' => 'boolean',
    ];

    public function scopes(): HasMany
    {
        return $this->hasMany(HolidayEventScope::class, 'holiday_event_id', 'holiday_event_id');
    }

    /**
     * Lookup reference only.
     * Final workday logic must be written into branch_calendars.
     */
    public function dayType()
    {
        return $this->belongsTo(DayType::class, 'day_type_code', 'day_type_code');
    }
}