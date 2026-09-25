<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DayType extends Model
{
    protected $table = 'day_types';
    protected $primaryKey = 'day_type_code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'day_type_code',
        'day_type_name',
        'is_workday_default',
        'notes',
    ];

    protected $casts = [
        'is_workday_default' => 'boolean',
    ];

    public function branchCalendars(): HasMany
    {
        return $this->hasMany(BranchCalendar::class, 'day_type_code', 'day_type_code');
    }

    public function holidayEvents(): HasMany
    {
        return $this->hasMany(HolidayEvent::class, 'day_type_code', 'day_type_code');
    }
}