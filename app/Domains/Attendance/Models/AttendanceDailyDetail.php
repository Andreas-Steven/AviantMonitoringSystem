<?php

namespace App\Domains\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDailyDetail extends Model
{
    protected $table = 'attendance_daily_details';
    protected $primaryKey = 'attendance_daily_detail_id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function attendanceDaily(): BelongsTo
    {
        return $this->belongsTo(AttendanceDaily::class, 'attendance_daily_id', 'attendance_daily_id');
    }
}