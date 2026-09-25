<?php

namespace App\Domains\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionResult extends Model
{
    protected $table = 'production_result';
    protected $primaryKey = 'id';

    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'actual_start' => 'datetime',
        'actual_finish' => 'datetime',
        'achievement' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'wo_number', 'wo_number');
    }
}
