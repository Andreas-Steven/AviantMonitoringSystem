<?php

namespace App\Domains\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'product';
    protected $primaryKey = 'product_code';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $guarded = [];

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'product_code', 'product_code');
    }
}
