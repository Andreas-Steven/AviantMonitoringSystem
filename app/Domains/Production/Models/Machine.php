<?php

namespace App\Domains\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $table = 'machine';
    protected $primaryKey = 'machine_code';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'machine_code', 'machine_code');
    }
}
