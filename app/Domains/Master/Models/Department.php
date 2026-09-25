<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'departments';
    protected $primaryKey = 'dept_id';

    protected $fillable = [
        'dept_code',
        'dept_name',
        'active',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function roles(): HasMany
    {
        return $this->hasMany(PositionRole::class, 'dept_id', 'dept_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class, 'dept_id', 'dept_id');
    }
}
