<?php

namespace App\Domains\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceImportBatch extends Model
{
    protected $table = 'attendance_import_batches';
    protected $primaryKey = 'attendance_import_batch_id';

    protected $guarded = [];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];
}