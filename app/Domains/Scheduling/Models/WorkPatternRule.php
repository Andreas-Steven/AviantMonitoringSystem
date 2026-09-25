<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkPatternRule extends Model
{
    protected $table = 'work_pattern_rules';
    protected $primaryKey = 'work_pattern_rule_id';

    protected $fillable = [
        'work_pattern_id',
        'rule_code',
        'rule_name',
        'rule_type_code',
        'day_of_week_code',
        'shift_id',
        'target_count_per_period',
        'holiday_wins_flag',
        'holiday_scope_mode_code',
        'substitution_allowed_flag',
        'excess_treatment_mode_code',
        'deficit_treatment_mode_code',
        'priority_order',
        'active',
        'notes',
    ];

    protected $casts = [
        'holiday_wins_flag' => 'boolean',
        'substitution_allowed_flag' => 'boolean',
        'active' => 'boolean',
    ];

    public function workPattern(): BelongsTo
    {
        return $this->belongsTo(WorkPattern::class, 'work_pattern_id', 'work_pattern_id');
    }

    public function ruleType(): BelongsTo
    {
        return $this->belongsTo(RuleType::class, 'rule_type_code', 'rule_type_code');
    }

    public function dayOfWeek(): BelongsTo
    {
        return $this->belongsTo(DayOfWeekCode::class, 'day_of_week_code', 'day_of_week_code');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'shift_id');
    }

    public function holidayScopeMode(): BelongsTo
    {
        return $this->belongsTo(HolidayScopeMode::class, 'holiday_scope_mode_code', 'holiday_scope_mode_code');
    }

    public function excessTreatmentMode(): BelongsTo
    {
        return $this->belongsTo(ExcessTreatmentMode::class, 'excess_treatment_mode_code', 'excess_treatment_mode_code');
    }

    public function deficitTreatmentMode(): BelongsTo
    {
        return $this->belongsTo(DeficitTreatmentMode::class, 'deficit_treatment_mode_code', 'deficit_treatment_mode_code');
    }
}