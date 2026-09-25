<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PositionRole extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'role_id';

    protected $fillable = [
        'role_code',
        'role_name',
        'dept_id',
        'active',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id', 'dept_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class, 'role_id', 'role_id');
    }
}
