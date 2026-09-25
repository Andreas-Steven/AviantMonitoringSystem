<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePolicy extends Model
{
    protected $table = 'attendance_policies';
    protected $primaryKey = 'policy_id';

    protected $fillable = [
        'policy_code',
        'policy_name',
        'late_grace_in_min',
        'early_out_grace_min',
        'min_work_min_half_day',
        'min_work_min_full_day',
        'overtime_min_before',
        'overtime_rounding_mode_code',
        'overtime_rounding_unit_min',
        'double_tap_window_min',
        'max_pair_gap_hour',
        'missing_out_policy_code',
        'missing_in_policy_code',
        'active',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function branchAssignments(): HasMany
    {
        return $this->hasMany(BranchPolicyAssignment::class, 'policy_id', 'policy_id');
    }
}