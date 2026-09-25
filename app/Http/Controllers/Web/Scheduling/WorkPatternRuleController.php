<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreWorkPatternRuleRequest;
use App\Http\Requests\Scheduling\UpdateWorkPatternRuleRequest;
use App\Domains\Scheduling\Models\WorkPatternRule;
use App\Domains\Scheduling\Models\WorkPattern;
use App\Domains\Scheduling\Models\RuleType;
use App\Domains\Scheduling\Models\DayOfWeekCode;
use App\Domains\Scheduling\Models\HolidayScopeMode;
use App\Domains\Scheduling\Models\ExcessTreatmentMode;
use App\Domains\Scheduling\Models\DeficitTreatmentMode;
use App\Domains\Scheduling\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkPatternRuleController extends Controller
{
    public function index(Request $request): View
    {
        $query = WorkPatternRule::with([
            'workPattern',
            'ruleType',
            'dayOfWeek',
            'shift',
        ])->orderBy('work_pattern_id')
          ->orderBy('priority_order');

        if ($request->filled('work_pattern_id')) {
            $query->where('work_pattern_id', (int) $request->work_pattern_id);
        }

        if ($request->filled('rule_type_code')) {
            $query->where('rule_type_code', $request->string('rule_type_code'));
        }

        if ($request->filled('active')) {
            $query->where('active', (bool) $request->active);
        }

        $rules = $query->paginate(15)->withQueryString();

        $workPatterns = WorkPattern::where('active', true)->orderBy('work_pattern_name')->get();
        $ruleTypes = RuleType::orderBy('rule_type_name')->get();

        return view('scheduling.work-pattern-rules.index', compact('rules', 'workPatterns', 'ruleTypes'));
    }

    public function create(): View
    {
        return view('scheduling.work-pattern-rules.create', $this->formData());
    }

    public function store(StoreWorkPatternRuleRequest $request): RedirectResponse
    {
        WorkPatternRule::create($request->validated());

        return redirect()
            ->route('scheduling.work-pattern-rules.index')
            ->with('success', 'Work pattern rule berhasil ditambahkan.');
    }

    public function show(int $work_pattern_rule): View
    {
        $rule = WorkPatternRule::with([
            'workPattern',
            'ruleType',
            'dayOfWeek',
            'shift',
            'holidayScopeMode',
            'excessTreatmentMode',
            'deficitTreatmentMode',
        ])->findOrFail($work_pattern_rule);

        return view('scheduling.work-pattern-rules.show', compact('rule'));
    }

    public function edit(int $work_pattern_rule): View
    {
        $rule = WorkPatternRule::findOrFail($work_pattern_rule);

        return view('scheduling.work-pattern-rules.edit', array_merge(
            ['rule' => $rule],
            $this->formData()
        ));
    }

    public function update(UpdateWorkPatternRuleRequest $request, int $work_pattern_rule): RedirectResponse
    {
        $rule = WorkPatternRule::findOrFail($work_pattern_rule);
        $rule->update($request->validated());

        return redirect()
            ->route('scheduling.work-pattern-rules.index')
            ->with('success', 'Work pattern rule berhasil diperbarui.');
    }

    protected function formData(): array
    {
        return [
            'workPatterns' => WorkPattern::where('active', true)->orderBy('work_pattern_name')->get(),
            'ruleTypes' => RuleType::orderBy('rule_type_name')->get(),
            'dayOfWeeks' => DayOfWeekCode::orderBy('sort_order')->get(),
            'shifts' => Shift::where('active', true)->orderBy('shift_name')->get(),
            'holidayScopeModes' => HolidayScopeMode::orderBy('holiday_scope_mode_name')->get(),
            'excessTreatmentModes' => ExcessTreatmentMode::orderBy('excess_treatment_mode_name')->get(),
            'deficitTreatmentModes' => DeficitTreatmentMode::orderBy('deficit_treatment_mode_name')->get(),
        ];
    }
}