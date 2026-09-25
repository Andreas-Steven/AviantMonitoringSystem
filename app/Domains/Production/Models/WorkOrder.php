<?php

namespace App\Domains\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    protected $table = 'work_order';
    protected $primaryKey = 'wo_number';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_no', 'employee_no');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_code', 'machine_code');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'product_code');
    }

    public function productionResults(): HasMany
    {
        return $this->hasMany(ProductionResult::class, 'wo_number', 'wo_number');
    }

    public function downtimes(): HasMany
    {
        return $this->hasMany(Downtime::class, 'wo_number', 'wo_number');
    }
}
