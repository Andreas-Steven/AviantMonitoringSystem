<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkPattern extends Model
{
    protected $table = 'work_patterns';
    protected $primaryKey = 'work_pattern_id';

    protected $fillable = [
        'work_pattern_code',
        'work_pattern_name',
        'evaluation_mode_code',
        'active',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function evaluationMode(): BelongsTo
    {
        return $this->belongsTo(EvaluationMode::class, 'evaluation_mode_code', 'evaluation_mode_code');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(WorkPatternRule::class, 'work_pattern_id', 'work_pattern_id');
    }

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeWorkPatternAssignment::class, 'work_pattern_id', 'work_pattern_id');
    }
}