<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domains\Scheduling\Models\BranchPolicyAssignment;
use App\Domains\Scheduling\Models\BranchCalendar;


class Branch extends Model
{
    protected $table = 'branches';
    protected $primaryKey = 'branch_id';

    protected $fillable = [
        'branch_code',
        'branch_name',
        'branch_type_code',
        'active',
        'opened_date',
        'closed_date',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
        'opened_date' => 'date',
        'closed_date' => 'date',
    ];

    public function policyAssignments(): HasMany
    {
        return $this->hasMany(BranchPolicyAssignment::class, 'branch_id', 'branch_id');
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(BranchCalendar::class, 'branch_id', 'branch_id');
    }
}