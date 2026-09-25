<?php

namespace App\Domains\Summary\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollAttendanceCalculatorService
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
            dateFrom: $period->period_start_date,
            dateTo: $period->period_end_date,
            periodCode: $periodCode,
            empId: $empId,
        );
    }

    public function calculate(
        int $payrollPeriodId,
        string $dateFrom,
        string $dateTo,
        string $periodCode,
        ?int $empId = null
    ): array {
        return DB::transaction(function () use ($payrollPeriodId, $dateFrom, $dateTo, $periodCode, $empId) {
            $this->clearExistingResults(
                payrollPeriodId: $payrollPeriodId,
                empId: $empId
            );

            $query = DB::table('attendance_period_summaries');

            if ($empId !== null) {
                $query->where('emp_id', $empId);
            }

            $summaries = $query
                ->where('payroll_period_id', $payrollPeriodId)
                ->get();

            $results = [];
            $upserted = 0;

            foreach ($summaries as $summary) {
                $overtimeMinPayable = (int) $summary->overtime_min_total;
                $deductionDayPayable = (float) $summary->deduction_day_count;

                $obligationUnfulfilled = 0;
                $obligationExcess = 0;

                if ($summary->summary_basis_type_code === 'OBLIGATION') {
                    $obligations = DB::table('employee_period_obligations')
                        ->where('emp_id', $summary->emp_id)
                        ->where('payroll_period_id', $payrollPeriodId)
                        ->get();

                    foreach ($obligations as $row) {
                        if (! $row->fulfilled_flag) {
                            $obligationUnfulfilled++;
                        }

                        $obligationExcess += (float) $row->excess_count;
                    }
                }

                $payload = [
                    'emp_id' => $summary->emp_id,
                    'payroll_period_id' => $payrollPeriodId,
                    'work_pattern_id' => $summary->work_pattern_id,
                    'branch_id' => $summary->branch_id,
                    'summary_basis_type_code' => $summary->summary_basis_type_code,

                    'overtime_min_payable' => $overtimeMinPayable,
                    'deduction_day_payable' => $deductionDayPayable,

                    'obligation_unfulfilled_count' => $obligationUnfulfilled,
                    'obligation_excess_count' => $obligationExcess,

                    'calculated_at' => now(),
                    'updated_at' => now(),
                ];

                $this->persist($payload);

                $results[] = array_merge($payload, [
                    'period_code' => $periodCode,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]);

                $upserted++;
            }

            return [
                'payroll_period_id' => $payrollPeriodId,
                'period_code' => $periodCode,
                'employee_count' => $summaries->count(),
                'upserted_count' => $upserted,
                'rows' => $results,
            ];
        });
    }

    private function clearExistingResults(int $payrollPeriodId, ?int $empId = null): void
    {
        $query = DB::table('payroll_attendance_results')
            ->where('payroll_period_id', $payrollPeriodId);

        if ($empId !== null) {
            $query->where('emp_id', $empId);
        }

        $query->delete();
    }

    private function persist(array $payload): void
    {
        $existing = DB::table('payroll_attendance_results')
            ->where('emp_id', $payload['emp_id'])
            ->where('payroll_period_id', $payload['payroll_period_id'])
            ->first();

        if ($existing) {
            DB::table('payroll_attendance_results')
                ->where('payroll_attendance_result_id', $existing->payroll_attendance_result_id)
                ->update($payload);

            return;
        }

        DB::table('payroll_attendance_results')->insert(array_merge($payload, [
            'created_at' => now(),
        ]));
    }
}