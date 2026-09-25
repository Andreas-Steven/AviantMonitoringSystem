<?php

namespace App\Domains\Summary\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollAttendanceAmountCalculatorService
{
    public function calculateByPeriodCode(string $periodCode, ?int $empId = null): array
    {
        $period = DB::table('payroll_periods')
            ->where('period_code', $periodCode)
            ->first();

        if (! $period) {
            throw new RuntimeException("Payroll period not found: {$periodCode}");
        }

        return $this->calculate(
            payrollPeriodId: (int) $period->payroll_period_id,
            periodCode: $periodCode,
            empId: $empId,
        );
    }

    public function calculate(
        int $payrollPeriodId,
        string $periodCode,
        ?int $empId = null
    ): array {
        return DB::transaction(function () use ($payrollPeriodId, $periodCode, $empId) {
            $this->clearExistingAmounts(
                payrollPeriodId: $payrollPeriodId,
                empId: $empId
            );

            $query = DB::table('payroll_attendance_results as par')
                ->select('par.*');

            if ($empId !== null) {
                $query->where('par.emp_id', $empId);
            }

            $rows = $query
                ->where('par.payroll_period_id', $payrollPeriodId)
                ->get();

            $results = [];
            $upserted = 0;

            foreach ($rows as $row) {
                $rate = $this->resolveRatePolicy(
                    branchId: (int) $row->branch_id,
                    summaryBasisTypeCode: (string) $row->summary_basis_type_code
                );

                $overtimeMinPayable = (int) $row->overtime_min_payable;
                $deductionDayPayable = (float) $row->deduction_day_payable;

                $overtimeRatePerMin = (float) ($rate->overtime_rate_per_min ?? 0);
                $deductionRatePerDay = (float) ($rate->deduction_rate_per_day ?? 0);

                $overtimeAmount = $overtimeMinPayable * $overtimeRatePerMin;
                $deductionAmount = $deductionDayPayable * $deductionRatePerDay;
                $netAttendanceAmount = $overtimeAmount - $deductionAmount;

                $notes = $this->buildNotes(
                    overtimeRatePerMin: $overtimeRatePerMin,
                    deductionRatePerDay: $deductionRatePerDay,
                    obligationUnfulfilledCount: (float) $row->obligation_unfulfilled_count,
                    obligationExcessCount: (float) $row->obligation_excess_count
                );

                $payload = [
                    'emp_id' => $row->emp_id,
                    'payroll_period_id' => $payrollPeriodId,

                    'overtime_min_payable' => $overtimeMinPayable,
                    'deduction_day_payable' => $deductionDayPayable,

                    'overtime_amount' => $overtimeAmount,
                    'deduction_amount' => $deductionAmount,
                    'net_attendance_amount' => $netAttendanceAmount,

                    'notes' => $notes,
                    'calculated_at' => now(),
                    'updated_at' => now(),
                ];

                $this->persist($payload);

                $results[] = array_merge($payload, [
                    'period_code' => $periodCode,
                ]);

                $upserted++;
            }

            return [
                'payroll_period_id' => $payrollPeriodId,
                'period_code' => $periodCode,
                'employee_count' => $rows->count(),
                'upserted_count' => $upserted,
                'rows' => $results,
            ];
        });
    }

    private function clearExistingAmounts(int $payrollPeriodId, ?int $empId = null): void
    {
        $query = DB::table('payroll_attendance_amounts')
            ->where('payroll_period_id', $payrollPeriodId);

        if ($empId !== null) {
            $query->where('emp_id', $empId);
        }

        $query->delete();
    }

    private function resolveRatePolicy(int $branchId, string $summaryBasisTypeCode): ?object
    {
        return DB::table('payroll_rate_policies')
            ->where('active', true)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            })
            ->where(function ($q) use ($summaryBasisTypeCode) {
                $q->whereNull('summary_basis_type_code')
                    ->orWhere('summary_basis_type_code', $summaryBasisTypeCode);
            })
            ->orderByRaw('CASE WHEN branch_id IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN summary_basis_type_code IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('effective_start_date')
            ->first();
    }

    private function buildNotes(
        float $overtimeRatePerMin,
        float $deductionRatePerDay,
        float $obligationUnfulfilledCount,
        float $obligationExcessCount
    ): string {
        return sprintf(
            'Calculated from payroll_attendance_results. overtime_rate_per_min=%.2f, deduction_rate_per_day=%.2f, obligation_unfulfilled_count=%.2f, obligation_excess_count=%.2f. Obligation counts are informative only and not converted to amount in current schema.',
            $overtimeRatePerMin,
            $deductionRatePerDay,
            $obligationUnfulfilledCount,
            $obligationExcessCount
        );
    }

    private function persist(array $payload): void
    {
        $existing = DB::table('payroll_attendance_amounts')
            ->where('emp_id', $payload['emp_id'])
            ->where('payroll_period_id', $payload['payroll_period_id'])
            ->first();

        $updatePayload = [
            'overtime_min_payable' => $payload['overtime_min_payable'],
            'deduction_day_payable' => $payload['deduction_day_payable'],
            'overtime_amount' => $payload['overtime_amount'],
            'deduction_amount' => $payload['deduction_amount'],
            'net_attendance_amount' => $payload['net_attendance_amount'],
            'notes' => $payload['notes'],
            'calculated_at' => $payload['calculated_at'],
            'updated_at' => $payload['updated_at'],
        ];

        if ($existing) {
            DB::table('payroll_attendance_amounts')
                ->where('payroll_attendance_amount_id', $existing->payroll_attendance_amount_id)
                ->update($updatePayload);

            return;
        }

        DB::table('payroll_attendance_amounts')->insert(array_merge($updatePayload, [
            'emp_id' => $payload['emp_id'],
            'payroll_period_id' => $payload['payroll_period_id'],
            'created_at' => now(),
        ]));
    }
}