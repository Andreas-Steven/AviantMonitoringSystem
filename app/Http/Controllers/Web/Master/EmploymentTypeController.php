<?php

namespace App\Http\Controllers\Web\Master;

use App\Domains\Master\Models\EmploymentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreEmploymentTypeRequest;
use App\Http\Requests\Master\UpdateEmploymentTypeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmploymentTypeController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmploymentType::query()->orderBy('employment_type_name');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('employment_type_code', 'ilike', "%{$keyword}%")
                    ->orWhere('employment_type_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $employmentTypes = $query->paginate(15)->withQueryString();

        return view('master.employment-types.index', compact('employmentTypes'));
    }

    public function create(): View
    {
        return view('master.employment-types.create');
    }

    public function store(StoreEmploymentTypeRequest $request): RedirectResponse
    {
        EmploymentType::create($request->validated());

        return redirect()
            ->route('master.employment-types.index')
            ->with('success', 'Employment type berhasil ditambahkan.');
    }

    public function edit(int $employment_type): View
    {
        $employmentType = EmploymentType::query()->findOrFail($employment_type);

        return view('master.employment-types.edit', compact('employmentType'));
    }

    public function update(UpdateEmploymentTypeRequest $request, int $employment_type): RedirectResponse
    {
        $employmentTypeModel = EmploymentType::query()->findOrFail($employment_type);
        $employmentTypeModel->update($request->validated());

        return redirect()
            ->route('master.employment-types.index')
            ->with('success', 'Employment type berhasil diperbarui.');
    }
}
