<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreWorkPatternRequest;
use App\Http\Requests\Scheduling\UpdateWorkPatternRequest;
use App\Domains\Scheduling\Models\WorkPattern;
use App\Domains\Scheduling\Models\EvaluationMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkPatternController extends Controller
{
    public function index(Request $request): View
    {
        $query = WorkPattern::with('evaluationMode')
            ->orderBy('work_pattern_name');

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('work_pattern_code', 'ilike', "%{$keyword}%")
                  ->orWhere('work_pattern_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('evaluation_mode_code')) {
            $query->where('evaluation_mode_code', $request->string('evaluation_mode_code'));
        }

        if ($request->filled('active')) {
            $query->where('active', (bool) $request->active);
        }

        $workPatterns = $query->paginate(15)->withQueryString();

        $evaluationModes = EvaluationMode::orderBy('evaluation_mode_name')->get();

        return view('scheduling.work-patterns.index', compact('workPatterns', 'evaluationModes'));
    }

    public function create(): View
    {
        $evaluationModes = EvaluationMode::orderBy('evaluation_mode_name')->get();

        return view('scheduling.work-patterns.create', compact('evaluationModes'));
    }

    public function store(StoreWorkPatternRequest $request): RedirectResponse
    {
        WorkPattern::create($request->validated());

        return redirect()
            ->route('scheduling.work-patterns.index')
            ->with('success', 'Work pattern berhasil ditambahkan.');
    }

    public function show(int $work_pattern): View
    {
        $workPattern = WorkPattern::with('evaluationMode')->findOrFail($work_pattern);

        return view('scheduling.work-patterns.show', compact('workPattern'));
    }

    public function edit(int $work_pattern): View
    {
        $workPattern = WorkPattern::findOrFail($work_pattern);
        $evaluationModes = EvaluationMode::orderBy('evaluation_mode_name')->get();

        return view('scheduling.work-patterns.edit', compact('workPattern', 'evaluationModes'));
    }

    public function update(UpdateWorkPatternRequest $request, int $work_pattern): RedirectResponse
    {
        $workPattern = WorkPattern::findOrFail($work_pattern);
        $workPattern->update($request->validated());

        return redirect()
            ->route('scheduling.work-patterns.index')
            ->with('success', 'Work pattern berhasil diperbarui.');
    }
}