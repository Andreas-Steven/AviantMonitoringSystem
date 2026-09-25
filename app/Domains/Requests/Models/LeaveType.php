<?php

namespace App\Domains\Requests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $table = 'leave_types';
    protected $primaryKey = 'leave_type_id';

    protected $fillable = [
        'leave_type_code',
        'leave_type_name',
        'is_paid',
        'deduct_quota',
        'requires_attachment',
        'active',
        'notes',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'deduct_quota' => 'boolean',
        'requires_attachment' => 'boolean',
        'active' => 'boolean',
    ];

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(EmployeeLeaveRequest::class, 'leave_type_id', 'leave_type_id');
    }
}