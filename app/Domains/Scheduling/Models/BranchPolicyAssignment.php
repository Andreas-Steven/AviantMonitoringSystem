<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Master\Models\Branch;

class BranchPolicyAssignment extends Model
{
    protected $table = 'branch_policy_assignments';
    protected $primaryKey = 'branch_policy_assignment_id';

    protected $fillable = [
        'branch_id',
        'policy_id',
        'effective_start_date',
        'effective_end_date',
        'notes',
    ];

    protected $casts = [
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(AttendancePolicy::class, 'policy_id', 'policy_id');
    }
}