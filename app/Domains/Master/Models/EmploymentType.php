<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmploymentType extends Model
{
    protected $table = 'employment_types';
    protected $primaryKey = 'employment_type_id';

    protected $fillable = [
        'employment_type_code',
        'employment_type_name',
        'active',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'employment_type_id', 'employment_type_id');
    }
}
