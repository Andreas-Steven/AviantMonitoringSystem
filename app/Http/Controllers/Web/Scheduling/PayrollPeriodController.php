<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Actions\GenerateBranchCalendarAction;
use App\Domains\Scheduling\DTOs\GenerateBranchCalendarData;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Scheduling\Models\PayrollPeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StorePayrollPeriodRequest;
use App\Http\Requests\Scheduling\UpdatePayrollPeriodRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;


class PayrollPeriodController extends Controller
{
    public function index(Request $request): View
    {
        $baseQuery = PayrollPeriod::query();

        $activePeriod = PayrollPeriod::query()
            ->where('payroll_period_status_code', PayrollPeriod::STATUS_OPEN)
            ->orderByDesc('period_start_date')
            ->first();

        $latestPeriod = PayrollPeriod::query()
            ->orderByDesc('period_start_date')
            ->first();

        $openCount = (clone $baseQuery)
            ->where('payroll_period_status_code', PayrollPeriod::STATUS_OPEN)
            ->count();

        $closedCount = (clone $baseQuery)
            ->where('payroll_period_status_code', PayrollPeriod::STATUS_CLOSED)
            ->count();

        $lockedCount = (clone $baseQuery)
            ->where('payroll_period_status_code', PayrollPeriod::STATUS_LOCKED)
            ->count();

        $query = PayrollPeriod::query()
            ->orderByRaw("
                CASE payroll_period_status_code
                    WHEN 'OPEN' THEN 0
                    WHEN 'CLOSED' THEN 1
                    WHEN 'LOCKED' THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('period_start_date');

        if ($request->filled('q')) {
            $keyword = $request->string('q')->toString();
            $query->where('period_code', 'ilike', "%{$keyword}%");
        }

        if ($request->filled('payroll_period_status_code')) {
            $query->where('payroll_period_status_code', $request->string('payroll_period_status_code')->toString());
        }

        $payrollPeriods = $query->paginate(15)->withQueryString();

        $statuses = PayrollPeriodStatus::query()
            ->orderBy('payroll_period_status_name')
            ->get();

        return view('scheduling.payroll-periods.index', compact(
            'payrollPeriods',
            'statuses',
            'activePeriod',
            'latestPeriod',
            'openCount',
            'closedCount',
            'lockedCount',
        ));
    }

    public function create(): View
    {
        $statuses = PayrollPeriodStatus::query()
            ->orderBy('payroll_period_status_name')
            ->get();

        return view('scheduling.payroll-periods.create', compact('statuses'));
    }

    public function store(
        StorePayrollPeriodRequest $request,
        GenerateBranchCalendarAction $generateBranchCalendarAction
    ): RedirectResponse {
        $payrollPeriod = PayrollPeriod::create($request->validated());

        $branchIds = Branch::query()
            ->where('active', true)
            ->pluck('branch_id');

        foreach ($branchIds as $branchId) {
            $generateBranchCalendarAction->execute(new GenerateBranchCalendarData(
                branchId: (int) $branchId,
                payrollPeriodId: (int) $payrollPeriod->payroll_period_id,
                overwriteExisting: false,
                generatedByUserId: auth()->id(),
            ));
        }

        return redirect()
            ->route('scheduling.payroll-periods.index')
            ->with('success', 'Payroll period berhasil ditambahkan dan branch calendar otomatis dibuat untuk semua branch aktif.');
    }

    public function show(int $payroll_period): View
    {
        $payrollPeriod = PayrollPeriod::findOrFail($payroll_period);

        return view('scheduling.payroll-periods.show', compact('payrollPeriod'));
    }

    public function edit(int $payroll_period): View
    {
        $payrollPeriod = PayrollPeriod::findOrFail($payroll_period);

        $statuses = PayrollPeriodStatus::query()
            ->orderBy('payroll_period_status_name')
            ->get();

        return view('scheduling.payroll-periods.edit', compact('payrollPeriod', 'statuses'));
    }

    public function update(UpdatePayrollPeriodRequest $request, int $payroll_period): RedirectResponse
    {
        $payrollPeriod = PayrollPeriod::findOrFail($payroll_period);
        $payrollPeriod->update($request->validated());

        return redirect()
            ->route('scheduling.payroll-periods.index')
            ->with('success', 'Payroll period berhasil diperbarui.');
    }

    public function close(int $payroll_period): RedirectResponse
    {
        $payrollPeriod = PayrollPeriod::findOrFail($payroll_period);

        if (! $payrollPeriod->isOpen()) {
            return back()->with('error', 'Hanya payroll period OPEN yang bisa di-close.');
        }

        $payrollPeriod->update([
            'payroll_period_status_code' => PayrollPeriod::STATUS_CLOSED,
        ]);

        return back()->with('success', 'Payroll period berhasil di-close.');
    }

    public function reopen(int $payroll_period): RedirectResponse
    {
        $payrollPeriod = PayrollPeriod::findOrFail($payroll_period);

        if (! $payrollPeriod->isClosed()) {
            return back()->with('error', 'Hanya payroll period CLOSED yang bisa di-reopen.');
        }

        DB::transaction(function () use ($payrollPeriod) {
            PayrollPeriod::query()
                ->where('payroll_period_id', '!=', $payrollPeriod->payroll_period_id)
                ->where('payroll_period_status_code', PayrollPeriod::STATUS_OPEN)
                ->update([
                    'payroll_period_status_code' => PayrollPeriod::STATUS_CLOSED,
                ]);

            $payrollPeriod->update([
                'payroll_period_status_code' => PayrollPeriod::STATUS_OPEN,
            ]);
        });

        return back()->with(
            'success',
            'Payroll period berhasil dibuka kembali dan menjadi active payroll period.'
        );
    }

    public function lock(int $payroll_period): RedirectResponse
    {
        $payrollPeriod = PayrollPeriod::findOrFail($payroll_period);

        if (! $payrollPeriod->isClosed()) {
            return back()->with('error', 'Hanya payroll period CLOSED yang bisa di-lock.');
        }

        $payrollPeriod->update([
            'payroll_period_status_code' => PayrollPeriod::STATUS_LOCKED,
        ]);

        return back()->with('success', 'Payroll period berhasil di-lock.');
    }

    public function unlock(int $payroll_period): RedirectResponse
    {
        $payrollPeriod = PayrollPeriod::findOrFail($payroll_period);

        if (! $payrollPeriod->isLocked()) {
            return back()->with('error', 'Hanya payroll period LOCKED yang bisa di-unlock.');
        }

        $payrollPeriod->update([
            'payroll_period_status_code' => PayrollPeriod::STATUS_CLOSED,
        ]);

        return back()->with('success', 'Payroll period berhasil di-unlock ke status CLOSED.');
    }

    public function createNext(
        int $payroll_period,
        GenerateBranchCalendarAction $generateBranchCalendarAction
    ): RedirectResponse {
        $currentPeriod = PayrollPeriod::findOrFail($payroll_period);

        if ($currentPeriod->isLocked()) {
            return back()->with('error', 'Payroll period LOCKED tidak bisa membuat next period langsung.');
        }

        $currentPeriodEndDate = CarbonImmutable::parse($currentPeriod->period_end_date);

        $nextEndDate = $currentPeriodEndDate
            ->addMonthNoOverflow()
            ->day(25);

        $nextStartDate = $nextEndDate
            ->subMonthNoOverflow()
            ->day(26);

        $nextPeriodCode = $nextEndDate->format('Y-m');

        $existingNext = PayrollPeriod::query()
            ->where('period_code', $nextPeriodCode)
            ->first();

        if ($existingNext) {
            return redirect()
                ->route('scheduling.payroll-periods.show', $existingNext->payroll_period_id)
                ->with('error', 'Next payroll period sudah ada.');
        }

        $nextPeriod = DB::transaction(function () use ($currentPeriod, $nextStartDate, $nextEndDate, $nextPeriodCode) {
            $currentPeriod->update([
                'payroll_period_status_code' => PayrollPeriod::STATUS_CLOSED,
            ]);

            return PayrollPeriod::create([
                'period_code' => $nextPeriodCode,
                'period_start_date' => $nextStartDate->toDateString(),
                'period_end_date' => $nextEndDate->toDateString(),
                'payroll_year' => (int) $nextEndDate->format('Y'),
                'payroll_month' => (int) $nextEndDate->format('m'),
                'payroll_period_status_code' => PayrollPeriod::STATUS_OPEN,
                'notes' => 'Auto-created from previous payroll period ' . $currentPeriod->period_code . '.',
            ]);
        });

        $branchIds = Branch::query()
            ->where('active', true)
            ->pluck('branch_id');

        foreach ($branchIds as $branchId) {
            $generateBranchCalendarAction->execute(new GenerateBranchCalendarData(
                branchId: (int) $branchId,
                payrollPeriodId: (int) $nextPeriod->payroll_period_id,
                overwriteExisting: false,
                generatedByUserId: auth()->id(),
            ));
        }

        return redirect()
            ->route('scheduling.payroll-periods.show', $nextPeriod->payroll_period_id)
            ->with('success', 'Next payroll period berhasil dibuat. Period sebelumnya otomatis CLOSED.');
    }
}