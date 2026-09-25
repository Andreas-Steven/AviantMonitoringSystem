<?php

namespace App\Domains\Summary\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeriodObligationBuilderService
{
    public function __construct(
        protected DailySummaryClassifierService $classifier,
    ) {}

    public function buildByPeriodCode(string $periodCode, ?int $empId = null): array
    {
        $period = DB::table('payroll_periods')
            ->where('period_code', $periodCode)
            ->first();

        if (! $period) {
            throw new RuntimeException("Payroll period not found: {$periodCode}");
        }

        return $this->build(
            payrollPeriodId: (int) $period->payroll_period_id,
            dateFrom: $period->period_start_date,
            dateTo: $period->period_end_date,
            periodCode: $periodCode,
            empId: $empId,
        );
    }

    public function build(
        int $payrollPeriodId,
        string $dateFrom,
        string $dateTo,
        string $periodCode,
        ?int $empId = null
    ): array {
        return DB::transaction(function () use ($payrollPeriodId, $dateFrom, $dateTo, $periodCode, $empId) {
            $this->clearExistingObligations(
                payrollPeriodId: $payrollPeriodId,
                empId: $empId
            );

            $employeesQuery = DB::table('employee_work_pattern_assignments as ewpa')
                ->join('work_patterns as wp', 'wp.work_pattern_id', '=', 'ewpa.work_pattern_id')
                ->where('wp.evaluation_mode_code', 'OFFICE_OBLIGATION')
                ->whereDate('ewpa.effective_start_date', '<=', $dateTo)
                ->where(function ($q) use ($dateFrom) {
                    $q->whereNull('ewpa.effective_end_date')
                        ->orWhereDate('ewpa.effective_end_date', '>=', $dateFrom);
                })
                ->select('ewpa.emp_id', 'ewpa.work_pattern_id');

            if ($empId !== null) {
                $employeesQuery->where('ewpa.emp_id', $empId);
            }

            $employees = $employeesQuery->get();

            $rows = [];
            $upserted = 0;

            foreach ($employees as $employee) {
                $rules = DB::table('work_pattern_rules')
                    ->where('work_pattern_id', $employee->work_pattern_id)
                    ->where('active', true)
                    ->whereIn('rule_type_code', [
                        'REQUIRED_WEEKDAY',
                        'PERIODIC_REQUIRED_DAY',
                    ])
                    ->orderBy('priority_order')
                    ->get();

                foreach ($rules as $rule) {
                    $obligation = match ((string) $rule->rule_type_code) {
                        'REQUIRED_WEEKDAY' => $this->calculateRequiredWeekdayObligation(
                            empId: (int) $employee->emp_id,
                            payrollPeriodId: $payrollPeriodId,
                            dateFrom: $dateFrom,
                            dateTo: $dateTo,
                            workPatternRule: $rule,
                        ),
                        'PERIODIC_REQUIRED_DAY' => $this->calculatePeriodicRequiredDayObligation(
                            empId: (int) $employee->emp_id,
                            payrollPeriodId: $payrollPeriodId,
                            dateFrom: $dateFrom,
                            dateTo: $dateTo,
                            workPatternRule: $rule,
                        ),
                        default => null,
                    };

                    if ($obligation === null) {
                        continue;
                    }

                    $this->persist($obligation);
                    $rows[] = array_merge([
                        'period_code' => $periodCode,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                    ], $obligation);
                    $upserted++;
                }
            }

            return [
                'payroll_period_id' => $payrollPeriodId,
                'period_code' => $periodCode,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'employee_count' => $employees->pluck('emp_id')->unique()->count(),
                'upserted_count' => $upserted,
                'filtered_emp_id' => $empId,
                'rows' => $rows,
            ];
        });
    }

    private function clearExistingObligations(int $payrollPeriodId, ?int $empId = null): void
    {
        $query = DB::table('employee_period_obligations')
            ->where('payroll_period_id', $payrollPeriodId);

        if ($empId !== null) {
            $query->where('emp_id', $empId);
        }

        $query->delete();
    }

    private function calculateRequiredWeekdayObligation(
        int $empId,
        int $payrollPeriodId,
        string $dateFrom,
        string $dateTo,
        object $workPatternRule
    ): ?array {
        if ($workPatternRule->day_of_week_code === null) {
            return null;
        }

        $branchId = $this->resolveBranchId($empId, $dateFrom, $dateTo);
        if ($branchId <= 0) {
            return null;
        }

        $calendarRows = DB::table('branch_calendars')
            ->where('branch_id', $branchId)
            ->whereBetween('work_date', [$dateFrom, $dateTo])
            ->get();

        $eligibleDays = $calendarRows
            ->filter(function ($row) use ($workPatternRule) {
                $sameDay =
                    $this->mapIsoDowToCode((int) date('N', strtotime((string) $row->work_date)))
                    === $workPatternRule->day_of_week_code;

                if (! $sameDay) {
                    return false;
                }

                // Required weekday only applies on actual branch workdays.
                // Holiday/company off/weekoff rows are not weekday obligations.
                return (bool) $row->is_workday === true;
            })
            ->values();

        $dailyRows = DB::table('attendance_daily')
            ->where('emp_id', $empId)
            ->whereBetween('work_date', [$dateFrom, $dateTo])
            ->get()
            ->keyBy(fn ($row) => (string) $row->work_date);

        $actualCount = 0.0;

        foreach ($eligibleDays as $eligibleDay) {
            $daily = $dailyRows->get((string) $eligibleDay->work_date);

            if (! $daily) {
                continue;
            }

            // Strict 1/0 for OBLIGATION weekday:
            // PRESENT valid = 1, anything else = 0.
            // Partial/fractional attendance is treated as an attendance quality issue,
            // not partial fulfillment of weekday obligation.
            $isValidPresent =
                $daily->attendance_status_code === 'PRESENT'
                && ! $this->isExcludedFromPayrollPresent($daily->review_reason_code);

            if ($isValidPresent) {
                $actualCount += 1;
            }
        }

        $requiredCount = (float) $eligibleDays->count();
        $fulfilled = $actualCount >= $requiredCount;
        $excessCount = 0;

        return [
            'emp_id' => $empId,
            'payroll_period_id' => $payrollPeriodId,
            'work_pattern_rule_id' => (int) $workPatternRule->work_pattern_rule_id,
            'obligation_type_code' => $this->mapRuleToObligationTypeCode((string) $workPatternRule->rule_code),
            'required_count' => round($requiredCount, 2),
            'actual_count' => round($actualCount, 2),
            'fulfilled_flag' => $fulfilled,
            'excess_count' => round($excessCount, 2),
            'notes' => sprintf(
                'Required weekday rule. Day=%s, eligible_workday_count=%d, required=%.2f, actual=%.2f. Strict 1/0, no substitution allowed.',
                $workPatternRule->day_of_week_code,
                $eligibleDays->count(),
                $requiredCount,
                $actualCount
            ),
        ];
    }

    private function calculatePeriodicRequiredDayObligation(
    int $empId,
    int $payrollPeriodId,
    string $dateFrom,
    string $dateTo,
    object $workPatternRule
    ): ?array {
        if ($workPatternRule->day_of_week_code === null) {
            return null;
        }

        $branchId = $this->resolveBranchId($empId, $dateFrom, $dateTo);
        if ($branchId <= 0) {
            return null;
        }

        $calendarRows = DB::table('branch_calendars')
            ->where('branch_id', $branchId)
            ->whereBetween('work_date', [$dateFrom, $dateTo])
            ->get();

        $matchingDays = $calendarRows
            ->filter(function ($row) use ($workPatternRule) {
                return $this->mapIsoDowToCode((int) date('N', strtotime((string) $row->work_date)))
                    === $workPatternRule->day_of_week_code;
            })
            ->values();

        $eligibleDays = $matchingDays
            ->filter(function ($row) use ($workPatternRule) {
                if (! $workPatternRule->holiday_wins_flag) {
                    return true;
                }

                return ! $this->isHolidayMatchedByScope(
                    dayTypeCode: (string) $row->day_type_code,
                    holidayScopeModeCode: $workPatternRule->holiday_scope_mode_code
                );
            })
            ->values();

        $targetCount = (float) ($workPatternRule->target_count_per_period ?? 0);

        if ($workPatternRule->rule_code === 'OSA_SAT_ALLOWANCE') {
            $requiredCount = $this->calculateSaturdayAllowanceRequiredCount(
                matchingDays: $matchingDays,
                allowanceCount: $targetCount,
                holidayScopeModeCode: $workPatternRule->holiday_scope_mode_code,
            );
        } else {
            $requiredCount = min($targetCount, (float) $eligibleDays->count());
        }

        $dailyRows = DB::table('attendance_daily')
            ->where('emp_id', $empId)
            ->whereBetween('work_date', [$dateFrom, $dateTo])
            ->get()
            ->keyBy(fn ($row) => (string) $row->work_date);

        $actualCount = 0.0;

        foreach ($eligibleDays as $eligibleDay) {
            $daily = $dailyRows->get((string) $eligibleDay->work_date);

            if (! $daily) {
                continue;
            }

            // Strict 1/0 for OBLIGATION periodic day:
            // PRESENT valid = 1, anything else = 0.
            // Partial/fractional attendance is treated as an attendance quality issue,
            // not partial fulfillment of Saturday/periodic obligation.
            $isValidPresent =
                $daily->attendance_status_code === 'PRESENT'
                && ! $this->isExcludedFromPayrollPresent($daily->review_reason_code);

            if ($isValidPresent) {
                $actualCount += 1;
            }
        }

        $fulfilled = $actualCount >= $requiredCount;
        $excessCount = 0;

        $obligationTypeCode = $this->mapRuleToObligationTypeCode($workPatternRule->rule_code);

        if ($workPatternRule->rule_code === 'OSA_SAT_ALLOWANCE') {
            $holidaySaturdayCount = $matchingDays
                ->filter(function ($row) use ($workPatternRule) {
                    return $this->isHolidayMatchedByScope(
                        dayTypeCode: (string) $row->day_type_code,
                        holidayScopeModeCode: $workPatternRule->holiday_scope_mode_code
                    );
                })
                ->count();

            $notes = sprintf(
                'Saturday allowance rule. Total SAT=%d, holiday SAT=%d, allowance=%.2f, required=%.2f, actual=%.2f. Strict 1/0, no substitution allowed.',
                $matchingDays->count(),
                $holidaySaturdayCount,
                $targetCount,
                $requiredCount,
                $actualCount
            );
        } else {
            $notes = sprintf(
                'Eligible %s count=%d, target=%.2f, required=%.2f, actual=%.2f. Strict 1/0.',
                $workPatternRule->day_of_week_code,
                $eligibleDays->count(),
                $targetCount,
                $requiredCount,
                $actualCount
            );
        }

        return [
            'emp_id' => $empId,
            'payroll_period_id' => $payrollPeriodId,
            'work_pattern_rule_id' => (int) $workPatternRule->work_pattern_rule_id,
            'obligation_type_code' => $obligationTypeCode,
            'required_count' => round($requiredCount, 2),
            'actual_count' => round($actualCount, 2),
            'fulfilled_flag' => $fulfilled,
            'excess_count' => round($excessCount, 2),
            'notes' => $notes,
        ];
    }

    private function calculateSaturdayAllowanceRequiredCount(
        Collection $matchingDays,
        float $allowanceCount,
        ?string $holidayScopeModeCode
    ): float {
        $totalSaturday = (float) $matchingDays->count();

        $holidaySaturday = (float) $matchingDays
            ->filter(function ($row) use ($holidayScopeModeCode) {
                return $this->isHolidayMatchedByScope(
                    dayTypeCode: (string) $row->day_type_code,
                    holidayScopeModeCode: $holidayScopeModeCode
                );
            })
            ->count();

        return max($totalSaturday - $holidaySaturday - $allowanceCount, 0);
    }

    private function resolveBranchId(int $empId, string $dateFrom, string $dateTo): int
    {
        $assignment = DB::table('employee_assignments')
            ->where('emp_id', $empId)
            ->whereDate('effective_start_date', '<=', $dateTo)
            ->where(function ($q) use ($dateFrom) {
                $q->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $dateFrom);
            })
            ->orderByDesc('effective_start_date')
            ->first();

        return (int) ($assignment->branch_id ?? 0);
    }

    private function mapIsoDowToCode(int $isoDow): string
    {
        return match ($isoDow) {
            1 => 'MON',
            2 => 'TUE',
            3 => 'WED',
            4 => 'THU',
            5 => 'FRI',
            6 => 'SAT',
            7 => 'SUN',
        };
    }

    private function isHolidayMatchedByScope(string $dayTypeCode, ?string $holidayScopeModeCode): bool
    {
        return match ($holidayScopeModeCode) {
            'NATIONAL_ONLY' => $dayTypeCode === 'HOLIDAY_NATIONAL',
            'COMPANY_ONLY' => $dayTypeCode === 'HOLIDAY_COMPANY',
            'NATIONAL_AND_COMPANY' => in_array($dayTypeCode, ['HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY'], true),
            default => false,
        };
    }

    private function mapRuleToObligationTypeCode(string $ruleCode): string
    {
        if (str_starts_with($ruleCode, 'OFF_') && $ruleCode !== 'OFF_SAT_MIN2') {
            return 'CUSTOM_PERIODIC';
        }

        return match ($ruleCode) {
            'OFF_SAT_MIN2', 'OSA_SAT_ALLOWANCE' => 'SATURDAY_MIN',
            default => 'CUSTOM_PERIODIC',
        };
    }

    private function persist(array $row): void
    {
        $payload = [
            'required_count' => $row['required_count'],
            'actual_count' => $row['actual_count'],
            'fulfilled_flag' => $row['fulfilled_flag'],
            'excess_count' => $row['excess_count'],
            'calculated_at' => now(),
            'notes' => $row['notes'],
            'updated_at' => now(),
        ];

        $existing = DB::table('employee_period_obligations')
            ->where('emp_id', $row['emp_id'])
            ->where('payroll_period_id', $row['payroll_period_id'])
            ->where('work_pattern_rule_id', $row['work_pattern_rule_id'])
            ->first();

        if ($existing) {
            DB::table('employee_period_obligations')
                ->where('employee_period_obligation_id', $existing->employee_period_obligation_id)
                ->update($payload);

            return;
        }

        DB::table('employee_period_obligations')->insert(array_merge($payload, [
            'emp_id' => $row['emp_id'],
            'payroll_period_id' => $row['payroll_period_id'],
            'work_pattern_rule_id' => $row['work_pattern_rule_id'],
            'obligation_type_code' => $row['obligation_type_code'],
            'created_at' => now(),
        ]));
    }

    private function isExcludedFromPayrollPresent(?string $reviewReasonCode): bool
    {
        return in_array($reviewReasonCode, [
            'SHIFT_MISSING',
            'FORGOT_CHECKIN_APPROVED',
            'FORGOT_CHECKOUT_APPROVED',
        ], true);
    }
    
}