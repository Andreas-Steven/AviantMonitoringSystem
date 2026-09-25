<?php

namespace App\Domains\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Downtime extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'downtime';
    
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'wo_number', 'wo_number');
    }
}
