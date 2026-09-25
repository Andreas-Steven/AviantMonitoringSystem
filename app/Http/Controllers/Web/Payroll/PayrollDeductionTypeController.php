<?php

namespace App\Http\Controllers\Web\Payroll;

use App\Domains\Payroll\Models\PayrollDeductionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollDeductionTypeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollDeductionTypeController extends Controller
{
    public function index(Request $request): View
    {
        $query = PayrollDeductionType::query()
            ->orderByDesc('active')
            ->orderBy('deduction_name');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('deduction_code', 'ilike', "%{$keyword}%")
                    ->orWhere('deduction_name', 'ilike', "%{$keyword}%")
                    ->orWhere('category_code', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('category_code')) {
            $query->where('category_code', $request->string('category_code')->toString());
        }

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->filled('debt_forming_default_flag')) {
            $query->where('debt_forming_default_flag', $request->boolean('debt_forming_default_flag'));
        }

        $statsBase = clone $query;

        $summaryStats = [
            'total_rows' => (clone $statsBase)->count(),
            'active_rows' => (clone $statsBase)->where('active', true)->count(),
            'debt_forming_rows' => (clone $statsBase)->where('debt_forming_default_flag', true)->count(),
            'attendance_rows' => (clone $statsBase)->whereIn('category_code', [
                'ATTENDANCE_FINE',
                'ATTENDANCE_DEDUCTION',
            ])->count(),
        ];

        $rows = $query->paginate(15)->withQueryString();

        return view('payroll.deduction-types.index', [
            'rows' => $rows,
            'summaryStats' => $summaryStats,
            'categories' => collect([
                'ATTENDANCE_FINE',
                'ATTENDANCE_DEDUCTION',
                'DAMAGE_CHARGE',
                'LOSS_CHARGE',
                'CASH_ADVANCE',
                'MANUAL_ADJUSTMENT',
            ]),
        ]);
    }

    public function create(): View
    {
        return view('payroll.deduction-types.create', [
            'categories' => collect([
                'ATTENDANCE_FINE',
                'ATTENDANCE_DEDUCTION',
                'DAMAGE_CHARGE',
                'LOSS_CHARGE',
                'CASH_ADVANCE',
                'MANUAL_ADJUSTMENT',
            ]),
        ]);
    }

    public function store(StorePayrollDeductionTypeRequest $request): RedirectResponse
    {
        PayrollDeductionType::query()->create([
            'deduction_code' => strtoupper(trim((string) $request->input('deduction_code'))),
            'deduction_name' => trim((string) $request->input('deduction_name')),
            'category_code' => $request->input('category_code'),
            'debt_forming_default_flag' => $request->boolean('debt_forming_default_flag'),
            'active' => $request->has('active') ? $request->boolean('active') : true,
            'notes' => $request->input('notes'),
        ]);

        return redirect()
            ->route('payroll.deduction-types.index')
            ->with('success', 'Payroll deduction type berhasil ditambahkan.');
    }
}