<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    protected $table = 'grades';
    protected $primaryKey = 'grade_id';

    protected $fillable = [
        'grade_code',
        'grade_name',
        'level_order',
        'active',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class, 'grade_id', 'grade_id');
    }
}
