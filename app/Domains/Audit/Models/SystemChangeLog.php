<?php

namespace App\Domains\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Master\Models\Employee;

class SystemChangeLog extends Model
{
    protected $table = 'system_change_logs';
    protected $primaryKey = 'change_log_id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'changed_at' => 'datetime',
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function changer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'changed_by', 'emp_id');
    }
}