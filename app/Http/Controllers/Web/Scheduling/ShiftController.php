<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreShiftRequest;
use App\Http\Requests\Scheduling\UpdateShiftRequest;
use App\Domains\Scheduling\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $query = Shift::query()->orderBy('shift_name');

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('shift_code', 'ilike', "%{$keyword}%")
                  ->orWhere('shift_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', (bool) $request->active);
        }

        $shifts = $query->paginate(15)->withQueryString();

        return view('scheduling.shifts.index', compact('shifts'));
    }

    public function create(): View
    {
        return view('scheduling.shifts.create');
    }

    public function store(StoreShiftRequest $request): RedirectResponse
    {
        Shift::create($request->validated());

        return redirect()
            ->route('scheduling.shifts.index')
            ->with('success', 'Shift berhasil ditambahkan.');
    }

    public function show(int $shift): View
    {
        $shift = Shift::findOrFail($shift);

        return view('scheduling.shifts.show', compact('shift'));
    }

    public function edit(int $shift): View
    {
        $shift = Shift::findOrFail($shift);

        return view('scheduling.shifts.edit', compact('shift'));
    }

    public function update(UpdateShiftRequest $request, int $shift): RedirectResponse
    {
        $shiftModel = Shift::findOrFail($shift);
        $shiftModel->update($request->validated());

        return redirect()
            ->route('scheduling.shifts.index')
            ->with('success', 'Shift berhasil diperbarui.');
    }
}