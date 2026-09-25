<?php

namespace App\Http\Controllers\Web\Payroll;

use App\Domains\Master\Models\Employee;
use App\Domains\Payroll\Models\EmployeeDebt;
use App\Domains\Payroll\Models\EmployeeDebtTransaction;
use App\Domains\Payroll\Models\PayrollDeduction;
use App\Domains\Payroll\Models\PayrollDeductionType;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollDeductionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollDeductionController extends Controller
{
    public function index(Request $request): View
    {
        $query = PayrollDeduction::query()
            ->with([
                'employee.assignments.branch',
                'payrollPeriod',
                'deductionType',
                'employeeDebt',
                'sourceType',
            ])
            ->orderByDesc('payroll_deduction_id');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->whereHas('employee', function ($q) use ($keyword): void {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%")
                    ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('payroll_period_id')) {
            $query->where('payroll_period_id', (int) $request->input('payroll_period_id'));
        }

        if ($request->filled('payroll_deduction_type_id')) {
            $query->where('payroll_deduction_type_id', (int) $request->input('payroll_deduction_type_id'));
        }

        if ($request->filled('debt_forming_flag')) {
            $query->where('debt_forming_flag', $request->boolean('debt_forming_flag'));
        }

        if ($request->filled('linked_debt_only')) {
            $query->whereNotNull('employee_debt_id');
        }

        $allowedBranchIds = $this->allowedBranchIds();

        if ($allowedBranchIds !== null) {
            if (!empty($allowedBranchIds)) {
                $query->whereHas('employee.assignments', function ($q) use ($allowedBranchIds): void {
                    $q->whereIn('branch_id', $allowedBranchIds)
                        ->where(function ($sub): void {
                            $sub->whereNull('effective_end_date')
                                ->orWhereDate('effective_end_date', '>=', now()->toDateString());
                        });
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $statsBase = clone $query;

        $summaryStats = [
            'total_rows' => (clone $statsBase)->count(),
            'debt_forming_rows' => (clone $statsBase)->where('debt_forming_flag', true)->count(),
            'linked_debt_rows' => (clone $statsBase)->whereNotNull('employee_debt_id')->count(),
            'amount_total' => (float) ((clone $statsBase)->sum('amount')),
        ];

        $rows = $query->paginate(15)->withQueryString();

        return view('payroll.deductions.index', [
            'rows' => $rows,
            'summaryStats' => $summaryStats,
            'payrollPeriods' => PayrollPeriod::query()->orderByDesc('period_start_date')->get(),
            'deductionTypes' => PayrollDeductionType::query()->where('active', true)->orderBy('deduction_name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('payroll.deductions.create', [
            'employees' => Employee::query()
                ->where('active', true)
                ->orderBy('full_name')
                ->get(),
            'payrollPeriods' => PayrollPeriod::query()
                ->orderByDesc('period_start_date')
                ->get(),
            'deductionTypes' => PayrollDeductionType::query()
                ->where('active', true)
                ->orderBy('deduction_name')
                ->get(),
            'sourceTypes' => \App\Domains\Scheduling\Models\SourceType::query()
                ->orderBy('source_type_name')
                ->get(),
            'employeeDebts' => EmployeeDebt::query()
                ->with('employee')
                ->where('status_code', 'OPEN')
                ->where('outstanding_amount', '>', 0)
                ->orderByDesc('origin_date')
                ->limit(300)
                ->get()
                ->map(function (EmployeeDebt $debt) {
                    return [
                        'employee_debt_id' => (int) $debt->employee_debt_id,
                        'emp_id' => (int) $debt->emp_id,
                        'debt_code' => (string) $debt->debt_code,
                        'debt_name' => (string) $debt->debt_name,
                        'debt_category_code' => (string) $debt->debt_category_code,
                        'origin_date' => optional($debt->origin_date)->format('Y-m-d'),
                        'outstanding_amount' => (float) $debt->outstanding_amount,
                        'employee_label' => trim(
                            ($debt->employee?->emp_code ?? '-') . ' · ' . ($debt->employee?->full_name ?? '-')
                        ),
                        'option_label' => trim(
                            ($debt->debt_code ?? '-') . ' · ' .
                            ($debt->employee?->emp_code ?? '-') . ' · ' .
                            ($debt->debt_name ?? '-') . ' · sisa ' .
                            number_format((float) $debt->outstanding_amount, 2)
                        ),
                    ];
                })
                ->values(),
            'debtCategoryOptions' => collect([
                'DAMAGE_CHARGE',
                'LOSS_CHARGE',
                'CASH_ADVANCE',
                'MANUAL_DEBT',
            ]),
        ]);
    }

    public function store(StorePayrollDeductionRequest $request): RedirectResponse
    {
        $deductionType = PayrollDeductionType::query()->findOrFail(
            (int) $request->input('payroll_deduction_type_id')
        );

        $qty = (float) $request->input('qty');
        $rateAmount = (float) ($request->input('rate_amount') ?? 0);
        $inputAmount = $request->filled('amount') ? (float) $request->input('amount') : null;
        $finalAmount = $inputAmount !== null ? $inputAmount : ($qty * $rateAmount);

        $isDebtForming = $request->has('debt_forming_flag')
            ? $request->boolean('debt_forming_flag')
            : (bool) $deductionType->debt_forming_default_flag;

        $payrollDeduction = DB::transaction(function () use ($request, $qty, $rateAmount, $finalAmount, $isDebtForming) {
            $employeeDebt = null;

            if ($request->filled('employee_debt_id')) {
                $employeeDebt = EmployeeDebt::query()->findOrFail((int) $request->input('employee_debt_id'));
            }

            if ($isDebtForming && $request->boolean('create_new_debt')) {
                $useCustomCode = $request->boolean('use_custom_debt_code');

                $debtCode = $useCustomCode
                    ? strtoupper(trim((string) $request->input('new_debt_code')))
                    : $this->generateDebtCode();

                $employeeDebt = EmployeeDebt::query()->create([
                    'emp_id' => (int) $request->input('emp_id'),
                    'debt_code' => $debtCode,
                    'debt_name' => trim((string) $request->input('new_debt_name')),
                    'debt_category_code' => $request->input('new_debt_category_code'),
                    'origin_date' => $request->input('new_debt_origin_date'),
                    'original_amount' => $finalAmount,
                    'outstanding_amount' => $finalAmount,
                    'status_code' => 'OPEN',
                    'source_type_code' => $request->input('source_type_code'),
                    'source_ref_id' => $request->input('source_ref_id'),
                    'notes' => $request->input('new_debt_notes') ?: $request->input('notes'),
                ]);

                EmployeeDebtTransaction::query()->create([
                    'employee_debt_id' => (int) $employeeDebt->employee_debt_id,
                    'emp_id' => (int) $employeeDebt->emp_id,
                    'transaction_type_code' => 'DEBT_CREATE',
                    'transaction_date' => $request->input('new_debt_origin_date'),
                    'amount' => $finalAmount,
                    'payment_source_code' => 'MANUAL',
                    'source_type_code' => $request->input('source_type_code'),
                    'source_ref_id' => $request->input('source_ref_id'),
                    'notes' => $request->input('new_debt_notes') ?: 'Debt created from payroll deduction',
                ]);
            }

            $payrollDeduction = PayrollDeduction::query()->create([
                'emp_id' => (int) $request->input('emp_id'),
                'payroll_period_id' => (int) $request->input('payroll_period_id'),
                'payroll_deduction_type_id' => (int) $request->input('payroll_deduction_type_id'),
                'source_type_code' => $request->input('source_type_code'),
                'source_ref_id' => $request->input('source_ref_id'),
                'description' => $request->input('description'),
                'qty' => $qty,
                'rate_amount' => $rateAmount,
                'amount' => $finalAmount,
                'debt_forming_flag' => $isDebtForming,
                'employee_debt_id' => $employeeDebt?->employee_debt_id,
                'notes' => $request->input('notes'),
                'is_cancelled' => false,
            ]);

            if ($isDebtForming && $employeeDebt) {
                $duplicateInstallment = EmployeeDebtTransaction::query()
                    ->where('payroll_deduction_id', (int) $payrollDeduction->payroll_deduction_id)
                    ->exists();

                if (!$duplicateInstallment) {
                    EmployeeDebtTransaction::query()->create([
                        'employee_debt_id' => (int) $employeeDebt->employee_debt_id,
                        'emp_id' => (int) $employeeDebt->emp_id,
                        'transaction_type_code' => 'PAYROLL_INSTALLMENT',
                        'transaction_date' => now()->toDateString(),
                        'amount' => $finalAmount,
                        'payment_source_code' => 'PAYROLL',
                        'payroll_period_id' => (int) $request->input('payroll_period_id'),
                        'payroll_deduction_id' => (int) $payrollDeduction->payroll_deduction_id,
                        'source_type_code' => $request->input('source_type_code'),
                        'source_ref_id' => $request->input('source_ref_id'),
                        'notes' => $request->input('notes') ?: 'Payroll deduction installment',
                    ]);
                }

                DB::statement(
                    'SELECT recalc_employee_debt_outstanding(?)',
                    [$employeeDebt->employee_debt_id]
                );
            }

            return $payrollDeduction;
        });

        return redirect()
            ->route('payroll.deductions.show', $payrollDeduction->payroll_deduction_id)
            ->with('success', 'Payroll deduction berhasil ditambahkan.');
    }

    public function show(int $deduction): View
    {
        $row = PayrollDeduction::query()
            ->with([
                'employee.assignments.branch',
                'payrollPeriod',
                'deductionType',
                'employeeDebt.transactions.transactionType',
                'sourceType',
            ])
            ->findOrFail($deduction);

        $this->authorizeEmployeeBranch($row->employee);

        return view('payroll.deductions.show', [
            'row' => $row,
        ]);
    }

    public function cancel(int $deduction): RedirectResponse
    {
        $row = PayrollDeduction::query()
            ->with('employee')
            ->findOrFail($deduction);

        $this->authorizeEmployeeBranch($row->employee);

        if ((bool) $row->is_cancelled) {
            return redirect()
                ->back()
                ->with('error', 'Payroll deduction ini sudah dibatalkan.');
        }

        $hasReversedInstallment = EmployeeDebtTransaction::query()
            ->where('payroll_deduction_id', $row->payroll_deduction_id)
            ->where('notes', 'ilike', '%[REVERSED]%')
            ->exists();

        if ($hasReversedInstallment) {
            return redirect()
                ->back()
                ->with('error', 'Tidak bisa cancel. Installment transaction untuk deduction ini sudah direverse.');
        }

        DB::transaction(function () use ($row): void {
            if ($row->employee_debt_id) {
                EmployeeDebtTransaction::query()->create([
                    'employee_debt_id' => (int) $row->employee_debt_id,
                    'emp_id' => (int) $row->emp_id,
                    'transaction_type_code' => 'ADJUST_PLUS',
                    'transaction_date' => now()->toDateString(),
                    'amount' => (float) $row->amount,
                    'payment_source_code' => 'MANUAL',
                    'payroll_period_id' => null,
                    'payroll_deduction_id' => null,
                    'source_type_code' => null,
                    'source_ref_id' => null,
                    'notes' => 'Reversal from cancelled payroll deduction #' . $row->payroll_deduction_id,
                ]);

                DB::statement(
                    'SELECT recalc_employee_debt_outstanding(?)',
                    [$row->employee_debt_id]
                );
            }

            $row->update([
                'is_cancelled' => true,
                'notes' => trim((string) ($row->notes ?? '') . ' [CANCELLED]'),
            ]);
        });

        return redirect()
            ->back()
            ->with('success', 'Payroll deduction berhasil dibatalkan.');
    }

    protected function generateDebtCode(): string
    {
        $prefix = 'DEBT-' . now()->format('Y-m');

        $last = EmployeeDebt::query()
            ->where('debt_code', 'like', $prefix . '-%')
            ->orderByDesc('debt_code')
            ->value('debt_code');

        if (!$last) {
            return $prefix . '-0001';
        }

        $lastNumber = (int) substr($last, -4);
        $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $nextNumber;
    }

    protected function allowedBranchIds(): ?array
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('SUPER_ADMIN')) {
            return null;
        }

        return $user->branchAccesses()->pluck('branches.branch_id')->all();
    }

    protected function authorizeEmployeeBranch(?Employee $employee): void
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('SUPER_ADMIN') || !$employee) {
            return;
        }

        $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

        $branchId = optional(
            $employee->assignments
                ?->sortByDesc('effective_start_date')
                ->first()
        )->branch_id;

        abort_unless($branchId && in_array($branchId, $allowedBranchIds, true), 403);
    }
}