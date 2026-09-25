<?php

namespace App\Http\Controllers\Web\Master;

use App\Domains\Master\Models\Grade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreGradeRequest;
use App\Http\Requests\Master\UpdateGradeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Grade::query()
            ->orderBy('level_order')
            ->orderBy('grade_name');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('grade_code', 'ilike', "%{$keyword}%")
                    ->orWhere('grade_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $grades = $query->paginate(15)->withQueryString();

        return view('master.grades.index', compact('grades'));
    }

    public function create(): View
    {
        return view('master.grades.create');
    }

    public function store(StoreGradeRequest $request): RedirectResponse
    {
        Grade::create($request->validated());

        return redirect()
            ->route('master.grades.index')
            ->with('success', 'Grade berhasil ditambahkan.');
    }

    public function edit(int $grade): View
    {
        $grade = Grade::query()->findOrFail($grade);

        return view('master.grades.edit', compact('grade'));
    }

    public function update(UpdateGradeRequest $request, int $grade): RedirectResponse
    {
        $gradeModel = Grade::query()->findOrFail($grade);
        $gradeModel->update($request->validated());

        return redirect()
            ->route('master.grades.index')
            ->with('success', 'Grade berhasil diperbarui.');
    }
}
