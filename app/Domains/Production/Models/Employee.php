<?php

namespace App\Domains\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $table = 'employee';
    protected $primaryKey = 'employee_no';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $guarded = [];

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'employee_no', 'employee_no');
    }
}
