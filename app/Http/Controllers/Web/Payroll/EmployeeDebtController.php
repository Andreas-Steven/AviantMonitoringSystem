<?php

namespace App\Http\Controllers\Web\Payroll;

use App\Domains\Master\Models\Employee;
use App\Domains\Payroll\Models\EmployeeDebt;
use App\Domains\Payroll\Models\EmployeeDebtTransaction;
use App\Domains\Payroll\Models\EmployeeDebtTransactionType;
use App\Domains\Payroll\Models\PayrollDeduction;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Scheduling\Models\SourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StoreEmployeeDebtRequest;
use App\Http\Requests\Payroll\StoreEmployeeDebtTransactionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeDebtController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmployeeDebt::query()
            ->with(['employee.assignments.branch'])
            ->orderByRaw("
                CASE status_code
                    WHEN 'OPEN' THEN 1
                    WHEN 'SETTLED' THEN 2
                    WHEN 'CANCELLED' THEN 3
                    ELSE 9
                END
            ")
            ->orderByDesc('origin_date')
            ->orderByDesc('employee_debt_id');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('debt_code', 'ilike', "%{$keyword}%")
                    ->orWhere('debt_name', 'ilike', "%{$keyword}%")
                    ->orWhereHas('employee', function ($sub) use ($keyword): void {
                        $sub->where('emp_code', 'ilike', "%{$keyword}%")
                            ->orWhere('full_name', 'ilike', "%{$keyword}%")
                            ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('status_code')) {
            $query->where('status_code', $request->string('status_code')->toString());
        }

        if ($request->filled('debt_category_code')) {
            $query->where('debt_category_code', $request->string('debt_category_code')->toString());
        }

        $user = auth()->user();

        if ($user && !$user->hasRole('SUPER_ADMIN')) {
            $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

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
            'open_rows' => (clone $statsBase)->where('status_code', 'OPEN')->count(),
            'settled_rows' => (clone $statsBase)->where('status_code', 'SETTLED')->count(),
            'outstanding_total' => (float) ((clone $statsBase)->sum('outstanding_amount')),
            'original_total' => (float) ((clone $statsBase)->sum('original_amount')),
        ];

        $rows = $query->paginate(15)->withQueryString();

        return view('payroll.debts.index', [
            'rows' => $rows,
            'summaryStats' => $summaryStats,
            'statusOptions' => collect(['OPEN', 'SETTLED', 'CANCELLED']),
            'categoryOptions' => collect([
                'DAMAGE_CHARGE',
                'LOSS_CHARGE',
                'CASH_ADVANCE',
                'MANUAL_DEBT',
            ]),
        ]);
    }

    public function create(): View
    {
        return view('payroll.debts.create', [
            'employees' => Employee::query()
                ->where('active', true)
                ->orderBy('full_name')
                ->get(),
            'sourceTypes' => SourceType::query()
                ->orderBy('source_type_name')
                ->get(),
            'categoryOptions' => collect([
                'DAMAGE_CHARGE',
                'LOSS_CHARGE',
                'CASH_ADVANCE',
                'MANUAL_DEBT',
            ]),
        ]);
    }

    public function store(StoreEmployeeDebtRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $debtCode = $request->boolean('use_custom_debt_code')
                ? strtoupper(trim((string) $request->input('debt_code')))
                : $this->generateDebtCode();

            $debt = EmployeeDebt::query()->create([
                'emp_id' => (int) $request->input('emp_id'),
                'debt_code' => $debtCode,
                'debt_name' => trim((string) $request->input('debt_name')),
                'debt_category_code' => $request->input('debt_category_code'),
                'origin_date' => $request->input('origin_date'),
                'original_amount' => (float) $request->input('original_amount'),
                'outstanding_amount' => (float) $request->input('original_amount'),
                'status_code' => 'OPEN',
                'source_type_code' => $request->input('source_type_code'),
                'source_ref_id' => $request->input('source_ref_id'),
                'notes' => $request->input('notes'),
            ]);

            EmployeeDebtTransaction::query()->create([
                'employee_debt_id' => (int) $debt->employee_debt_id,
                'emp_id' => (int) $debt->emp_id,
                'transaction_type_code' => 'DEBT_CREATE',
                'transaction_date' => $request->input('origin_date'),
                'amount' => (float) $request->input('original_amount'),
                'payment_source_code' => 'MANUAL',
                'source_type_code' => $request->input('source_type_code'),
                'source_ref_id' => $request->input('source_ref_id'),
                'notes' => $request->input('notes') ?: 'Initial debt creation',
            ]);

            DB::statement(
                'SELECT recalc_employee_debt_outstanding(?)',
                [$debt->employee_debt_id]
            );
        });

        return redirect()
            ->route('payroll.debts.index')
            ->with('success', 'Employee debt berhasil dibuat.');
    }

    public function show(int $debt): View
    {
        $row = EmployeeDebt::query()
            ->with(['employee.assignments.branch', 'sourceType'])
            ->findOrFail($debt);

        $this->authorizeBranch($row);

        $transactions = EmployeeDebtTransaction::query()
            ->with([
                'transactionType',
                'payrollPeriod',
                'payrollDeduction.deductionType',
                'sourceType',
            ])
            ->where('employee_debt_id', $row->employee_debt_id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('employee_debt_transaction_id')
            ->paginate(20)
            ->withQueryString();

        $linkedDeductions = PayrollDeduction::query()
            ->with(['payrollPeriod', 'deductionType'])
            ->where('employee_debt_id', $row->employee_debt_id)
            ->orderByDesc('payroll_deduction_id')
            ->get();

        $summaryCards = [
            'transaction_count' => EmployeeDebtTransaction::query()
                ->where('employee_debt_id', $row->employee_debt_id)
                ->count(),
            'installment_total' => (float) EmployeeDebtTransaction::query()
                ->where('employee_debt_id', $row->employee_debt_id)
                ->whereIn('transaction_type_code', [
                    'PAYROLL_INSTALLMENT',
                    'NON_PAYROLL_PAYMENT',
                    'FULL_SETTLEMENT',
                    'ADJUST_MINUS',
                ])
                ->sum('amount'),
            'increase_total' => (float) EmployeeDebtTransaction::query()
                ->where('employee_debt_id', $row->employee_debt_id)
                ->whereIn('transaction_type_code', [
                    'DEBT_CREATE',
                    'ADJUST_PLUS',
                ])
                ->sum('amount'),
            'linked_deduction_total' => (float) PayrollDeduction::query()
                ->where('employee_debt_id', $row->employee_debt_id)
                ->sum('amount'),
        ];

        return view('payroll.debts.show', [
            'row' => $row,
            'transactions' => $transactions,
            'linkedDeductions' => $linkedDeductions,
            'summaryCards' => $summaryCards,
            'payrollPeriods' => PayrollPeriod::query()
                ->orderByDesc('period_start_date')
                ->get(),
            'sourceTypes' => SourceType::query()
                ->orderBy('source_type_name')
                ->get(),
        ]);
    }

    public function addTransaction(
        StoreEmployeeDebtTransactionRequest $request,
        int $debt
    ): RedirectResponse {
        $row = EmployeeDebt::query()->findOrFail($debt);
        $this->authorizeBranch($row);

        if ($request->filled('payroll_deduction_id')) {
            $deduction = PayrollDeduction::query()->findOrFail((int) $request->input('payroll_deduction_id'));

            if ((int) $deduction->employee_debt_id !== (int) $row->employee_debt_id) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Payroll deduction yang dipilih tidak terkait ke debt ini.');
            }
        }

        DB::transaction(function () use ($request, $row): void {
            EmployeeDebtTransaction::query()->create([
                'employee_debt_id' => (int) $row->employee_debt_id,
                'emp_id' => (int) $row->emp_id,
                'transaction_type_code' => $request->input('transaction_type_code'),
                'transaction_date' => $request->input('transaction_date'),
                'amount' => (float) $request->input('amount'),
                'payment_source_code' => $request->input('payment_source_code'),
                'payroll_period_id' => $request->input('payroll_period_id'),
                'payroll_deduction_id' => $request->input('payroll_deduction_id'),
                'source_type_code' => $request->input('source_type_code'),
                'source_ref_id' => $request->input('source_ref_id'),
                'notes' => $request->input('notes'),
            ]);

            DB::statement(
                'SELECT recalc_employee_debt_outstanding(?)',
                [$row->employee_debt_id]
            );
        });

        return redirect()
            ->route('payroll.debts.show', $row->employee_debt_id)
            ->with('success', 'Debt transaction berhasil ditambahkan.');
    }

    public function reverseTransaction(int $debt, int $tx): RedirectResponse
    {
        $row = EmployeeDebt::query()
            ->with('employee.assignments.branch')
            ->findOrFail($debt);

        $this->authorizeBranch($row);

        $original = EmployeeDebtTransaction::query()
            ->with(['transactionType', 'payrollDeduction'])
            ->findOrFail($tx);

        abort_unless(
            (int) $original->employee_debt_id === (int) $row->employee_debt_id,
            404
        );

        if (str_contains((string) ($original->notes ?? ''), '[REVERSED]')) {
            return redirect()
                ->back()
                ->with('error', 'Transaction ini sudah pernah direverse.');
        }

        if ($original->transaction_type_code === 'DEBT_CREATE') {
            return redirect()
                ->back()
                ->with('error', 'Transaction DEBT_CREATE tidak bisa direverse dari sini.');
        }

        if ($original->payroll_deduction_id && $original->payrollDeduction?->is_cancelled) {
            return redirect()
                ->back()
                ->with('error', 'Tidak bisa reverse. Payroll deduction terkait sudah dibatalkan.');
        }

        $reverseTypeCode = match ($original->transaction_type_code) {
            'PAYROLL_INSTALLMENT',
            'NON_PAYROLL_PAYMENT',
            'FULL_SETTLEMENT',
            'ADJUST_MINUS' => 'ADJUST_PLUS',

            'ADJUST_PLUS' => 'ADJUST_MINUS',

            default => null,
        };

        if (!$reverseTypeCode) {
            return redirect()
                ->back()
                ->with('error', 'Transaction type ini belum didukung untuk reversal.');
        }

        DB::transaction(function () use ($row, $original, $reverseTypeCode): void {
            EmployeeDebtTransaction::query()->create([
                'employee_debt_id' => (int) $row->employee_debt_id,
                'emp_id' => (int) $row->emp_id,
                'transaction_type_code' => $reverseTypeCode,
                'transaction_date' => now()->toDateString(),
                'amount' => (float) $original->amount,
                'payment_source_code' => 'MANUAL',
                'payroll_period_id' => null,
                'payroll_deduction_id' => null,
                'source_type_code' => null,
                'source_ref_id' => null,
                'notes' => 'Reversal of TX #' . $original->employee_debt_transaction_id,
            ]);

            $original->update([
                'notes' => trim((string) ($original->notes ?? '') . ' [REVERSED]'),
            ]);

            DB::statement(
                'SELECT recalc_employee_debt_outstanding(?)',
                [$row->employee_debt_id]
            );
        });

        return redirect()
            ->back()
            ->with('success', 'Debt transaction berhasil direverse.');
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

    protected function authorizeBranch(EmployeeDebt $debt): void
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('SUPER_ADMIN')) {
            return;
        }

        $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

        $branchId = optional(
            $debt->employee?->assignments
                ?->sortByDesc('effective_start_date')
                ->first()
        )->branch_id;

        abort_unless($branchId && in_array($branchId, $allowedBranchIds, true), 403);
    }
}