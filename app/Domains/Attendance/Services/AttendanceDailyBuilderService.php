<?php

namespace App\Domains\Attendance\Services;

use App\Domains\Review\Models\AttendanceException;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttendanceDailyBuilderService
{
    public function __construct(
        protected CalendarResolverService $calendarResolverService,
        protected AttendanceAbsenceGeneratorService $attendanceAbsenceGeneratorService,
        protected LeaveResolverService $leaveResolverService,
        protected AttendanceExceptionResolverService $attendanceExceptionResolverService,
        protected ExceptionApplierService $exceptionApplierService,
        protected \App\Domains\Requests\Services\LeaveBalanceResolverService $leaveBalanceResolverService,
        protected \App\Domains\Requests\Services\LeaveBalanceMutationService $leaveBalanceMutationService,
        protected \App\Domains\Requests\Services\OvertimeResolverService $overtimeResolverService,
    ) {}

    public function build(?string $dateFrom = null, ?string $dateTo = null): array
    {
        [$dateFrom, $dateTo] = $this->resolveBuildRange($dateFrom, $dateTo);

        $employeeIds = $this->getEmployeeIdsInRange($dateFrom, $dateTo);
        $logsByEmpDate = $this->getNormalizedLogsGrouped($dateFrom, $dateTo);

        $processedDays = 0;
        $upsertedCount = 0;
        $skippedDays = 0;
        $affectedEmpIds = [];

        foreach ($employeeIds as $empId) {
            $period = CarbonPeriod::create($dateFrom, $dateTo);

            foreach ($period as $date) {
                $workDate = $date->toDateString();

                $result = $this->buildOneEmployeeDay(
                    empId: (int) $empId,
                    workDate: $workDate,
                    logs: $logsByEmpDate->get($this->makeEmpDateKey((int) $empId, $workDate), collect()),
                );

                if ($result === null) {
                    $skippedDays++;
                    continue;
                }

                $this->persistDailyResult($result['daily'], $result['details']);

                $this->reconcileAutoForceLeaveUsage($result['daily']);

                $processedDays++;
                $upsertedCount++;
                $affectedEmpIds[(int) $empId] = true;
            }
        }

        if (!empty($affectedEmpIds)) {
            $this->applyPatternDetection(array_keys($affectedEmpIds));
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'employee_count' => count($employeeIds),
            'processed_days' => $processedDays,
            'upserted_count' => $upsertedCount,
            'skipped_days' => $skippedDays,
        ];
    }

    public function buildOneDay(int $empId, string $workDate): ?array
    {
        $logsByEmpDate = $this->getNormalizedLogsGrouped($workDate, $workDate);

        $result = $this->buildOneEmployeeDay(
            empId: $empId,
            workDate: $workDate,
            logs: $logsByEmpDate->get($this->makeEmpDateKey($empId, $workDate), collect()),
        );

        if ($result === null) {
            return null;
        }

        $this->persistDailyResult($result['daily'], $result['details']);
        $this->reconcileAutoForceLeaveUsage($result['daily']);
        $this->applyPatternDetection([$empId]);

        $stored = DB::table('attendance_daily')
            ->where('emp_id', $empId)
            ->whereDate('work_date', $workDate)
            ->first();

        return [
            'attendance_daily_id' => $stored ? (int) $stored->attendance_daily_id : null,
            'emp_id' => $empId,
            'work_date' => $workDate,
            'details_count' => count($result['details']),
            'daily' => $result['daily'],
        ];
    }    

    private function buildOneEmployeeDay(int $empId, string $workDate, Collection $logs): ?array
    {
        $previousDaily = $this->getPreviousDailyRow($empId, $workDate);

        $assignment = $this->resolveAssignment($empId, $workDate);

        if (!$assignment || !$assignment->branch_id) {
            return null;
        }

        $branchId = (int) $assignment->branch_id;

        $branchPolicy = $this->resolveBranchPolicy($branchId, $workDate);
        if (!$branchPolicy || !$branchPolicy->policy_id) {
            return null;
        }

        $policy = DB::table('attendance_policies')
            ->where('policy_id', $branchPolicy->policy_id)
            ->first();

        if (!$policy) {
            return null;
        }

        $calendar = $this->calendarResolverService->resolveOrFail($branchId, $workDate);
        $leave = $this->leaveResolverService->resolveForDate($empId, $workDate);
        $exceptionSummary = $this->attendanceExceptionResolverService->resolveForDate($empId, $workDate);
        $overtime = $this->overtimeResolverService->resolveForDate($empId, $workDate);

        $rawExceptions = AttendanceException::query()
            ->where('emp_id', $empId)
            ->whereDate('work_date', $workDate)
            ->orderBy('attendance_exception_id')
            ->get();

        $details = [];
        $notes = [];

        $autoForceLeaveApplied = false;
        $autoForceLeaveBalanceId = null;
        $autoForceLeaveTypeId = null;
        $autoForceLeaveTypeCode = 'ANNUAL';
        $autoForceLeaveQty = 1.0;
        $autoForceLeaveSourceRefId = null;

        $details[] = $this->makeDetail(
            'ASSIGNMENT_RESOLVED',
            'Employee assignment resolved.',
            'SYSTEM',
            isset($assignment->assignment_id) ? (string) $assignment->assignment_id : null
        );

        $details[] = $this->makeDetail(
            'POLICY_RESOLVED',
            'Branch attendance policy resolved.',
            'SYSTEM',
            isset($branchPolicy->branch_policy_assignment_id) ? (string) $branchPolicy->branch_policy_assignment_id : null
        );

        $details[] = $this->makeDetail(
            'CALENDAR_RESOLVED',
            sprintf(
                'Calendar resolved: %s / is_workday=%s',
                $calendar['day_type_code'],
                $calendar['is_workday'] ? 'true' : 'false'
            ),
            'SYSTEM',
            (string) $calendar['branch_calendar_id'],
            $calendar['notes']
        );

        $details[] = $this->makeDetail(
            'LEAVE_CHECKED',
            $leave['has_approved_leave'] ? 'Approved leave found.' : 'No approved leave.',
            $leave['has_approved_leave'] ? 'APPROVAL' : 'SYSTEM',
            $leave['leave_request_id'] ? (string) $leave['leave_request_id'] : null,
            $leave['leave_type_name']
        );

        $details[] = $this->makeDetail(
            'EXCEPTION_CHECKED',
            $exceptionSummary['has_exception'] ? 'Attendance exception found.' : 'No attendance exception.',
            $exceptionSummary['has_exception'] ? 'APPROVAL' : 'SYSTEM',
            $exceptionSummary['has_exception'] ? 'EXCEPTION' : null,
            $exceptionSummary['has_exception'] ? implode(', ', $exceptionSummary['exception_types']) : null
        );

        $details[] = $this->makeDetail(
            'OVERTIME_CHECKED',
            $overtime['has_approved_overtime'] ? 'Approved overtime found.' : 'No approved overtime.',
            $overtime['has_approved_overtime'] ? 'APPROVAL' : 'SYSTEM',
            $overtime['overtime_request_id'] ? (string) $overtime['overtime_request_id'] : null,
            $overtime['has_approved_overtime']
                ? 'Approved overtime min=' . (string) $overtime['approved_overtime_min']
                : null
        );

        $resolvedShift = $this->resolveShift($empId, $workDate, $exceptionSummary);
        $shiftId = $resolvedShift['shift_id'];
        $shiftSourceType = $resolvedShift['source_type_code'];
        $shiftSourceRefId = $resolvedShift['source_ref_id'];
        $shiftNotes = $resolvedShift['notes'];

        $details[] = $this->makeDetail(
            'SHIFT_RESOLVED',
            $shiftId ? 'Shift resolved.' : 'No shift resolved.',
            $shiftSourceType,
            $shiftSourceRefId,
            $shiftNotes
        );

        $schedule = $this->resolveSchedule(
            workDate: $workDate,
            shiftId: $shiftId,
            policy: $policy,
        );

        $logAnalysis = $this->analyzeLogs(
            logs: $logs,
            policy: $policy,
            schedule: $schedule,
        );

        $details[] = $this->makeDetail(
            'LOGS_EVALUATED',
            $logAnalysis['step_result'],
            'SYSTEM',
            null,
            $logAnalysis['notes_text']
        );

        $attendanceStatusCode = null;
        $presenceTypeCode = null;
        $anomalyFlag = (bool) $logAnalysis['anomaly_flag'];
        $exceptionFlag = (bool) $exceptionSummary['has_exception'];
        $leaveFlag = (bool) $leave['has_approved_leave'];

        $actualInDatetime = $logAnalysis['actual_in_datetime'];
        $actualOutDatetime = $logAnalysis['actual_out_datetime'];
        $workMin = (int) $logAnalysis['work_min'];
        $lateMin = (int) $logAnalysis['late_min'];
        $earlyOutMin = (int) $logAnalysis['early_out_min'];

        $hasActualAttendance = (bool) $logAnalysis['has_actual_attendance'];
        $alreadyHasDailyResult = false;

        $isApprovedOvertimeOnly =
            (bool) ($overtime['has_approved_overtime'] ?? false)
            && (int) ($overtime['approved_overtime_min'] ?? 0) > 0
            && ! $hasActualAttendance
            && ! (bool) ($leave['has_approved_leave'] ?? false)
            && ! (bool) ($calendar['is_workday'] ?? true);

        $overtimeMin = $this->resolveFinalOvertimeMin(
            computedOvertimeMin: (int) $logAnalysis['overtime_min'],
            overtime: $overtime,
            hasActualAttendance: $hasActualAttendance,
            anomalyFlag: (bool) $logAnalysis['anomaly_flag'],
            presenceTypeCode: $logAnalysis['presence_type_code']
        );

        // $overtimeBreakdown = $this->resolveOvertimeBreakdown(
        //     finalOvertimeMin: $overtimeMin,
        //     dayTypeCode: $calendar['day_type_code'] ?? null,
        //     isWorkday: (bool) ($calendar['is_workday'] ?? true),
        // );

        // $details[] = $this->makeDetail(
        //     'OVERTIME_BREAKDOWN',
        //     'Overtime bucket classified.',
        //     'SYSTEM',
        //     null,
        //     sprintf(
        //         'Total=%d, workday=%d, holiday=%d, offday=%d, day_type=%s, is_workday=%s',
        //         $overtimeMin,
        //         $overtimeBreakdown['overtime_workday_min'],
        //         $overtimeBreakdown['overtime_holiday_min'],
        //         $overtimeBreakdown['overtime_offday_min'],
        //         (string) ($calendar['day_type_code'] ?? 'NULL'),
        //         (bool) ($calendar['is_workday'] ?? true) ? 'true' : 'false'
        //     )
        // );

        

        if ($isApprovedOvertimeOnly) {
            $attendanceStatusCode = 'PRESENT';
            $presenceTypeCode = 'OVERTIME_ONLY';
            $notes[] = 'Approved overtime applied without regular attendance logs.';

            if (! empty($overtime['actual_start_datetime'])) {
                $actualInDatetime = $overtime['actual_start_datetime'];
            }

            if (! empty($overtime['actual_end_datetime'])) {
                $actualOutDatetime = $overtime['actual_end_datetime'];
            }

                $details[] = $this->makeDetail(
                    'OVERTIME_ONLY_APPLIED',
                    'Approved overtime without regular attendance converted to PRESENT / OVERTIME_ONLY.',
                    'APPROVAL',
                    $overtime['overtime_request_id'] ? (string) $overtime['overtime_request_id'] : null,
                    'Approved overtime min=' . (string) ($overtime['approved_overtime_min'] ?? 0)
                );
        } elseif (! $hasActualAttendance && ! $calendar['is_workday']) {
            $attendanceStatusCode = $this->attendanceAbsenceGeneratorService->resolveStatusForNoAttendance(
                isWorkday: false,
                dayTypeCode: $calendar['day_type_code']
            );
            $presenceTypeCode = null;
            $notes[] = 'Non-workday without attendance.';
        } elseif ($leave['has_approved_leave'] && ! $hasActualAttendance) {
            $attendanceStatusCode = $leave['status_for_daily'] ?? 'LEAVE';
            $presenceTypeCode = null;
            $notes[] = 'Approved leave applied.';
        } elseif ($hasActualAttendance) {
            $attendanceStatusCode = $logAnalysis['attendance_status_code'];
            $presenceTypeCode = $logAnalysis['presence_type_code'];
            $notes = array_merge($notes, $logAnalysis['notes']);
        } else {
            $shouldGenerateAbsent = $this->attendanceAbsenceGeneratorService->shouldGenerateAbsent(
                isWorkday: (bool) $calendar['is_workday'],
                hasActualAttendance: false,
                hasApprovedLeave: (bool) $leave['has_approved_leave'],
                hasApprovedException: (bool) $exceptionSummary['has_exception'],
                alreadyHasDailyResult: $alreadyHasDailyResult,
            );

            if ($shouldGenerateAbsent) {
                if ($this->shouldDeferAutoForceLeaveToPeriodObligation(
                    empId: $empId,
                    workDate: $workDate,
                    branchId: $branchId
                )) {
                    $attendanceStatusCode = 'ABSENT';
                    $presenceTypeCode = 'NO_SHOW';
                    $leaveFlag = false;

                    $autoForceLeaveApplied = false;
                    $autoForceLeaveBalanceId = null;
                    $autoForceLeaveTypeId = null;
                    $autoForceLeaveSourceRefId = sprintf('AUTO_FORCE_LEAVE|%d|%s', $empId, $workDate);

                    $notes[] = 'Workday without attendance on OFFICE_SATURDAY_ALLOWANCE Saturday. Daily auto force leave skipped; period obligation will handle final deficit.';

                    $details[] = $this->makeDetail(
                        'AUTO_FORCE_LEAVE_SKIPPED',
                        'Daily auto force leave skipped for OFFICE_SATURDAY_ALLOWANCE Saturday.',
                        'SYSTEM',
                        null,
                        'Saturday allowance is evaluated at period obligation level.'
                    );
                } else {
                    $annualBalance = $this->leaveBalanceResolverService->resolveAvailableBalance(
                        empId: $empId,
                        leaveTypeCode: $autoForceLeaveTypeCode,
                        workDate: $workDate
                    );

                    if (
                        $annualBalance
                        && (float) ($annualBalance['available_balance'] ?? 0) >= $autoForceLeaveQty
                    ) {
                        $attendanceStatusCode = 'LEAVE';
                        $presenceTypeCode = null;
                        $leaveFlag = true;

                        $autoForceLeaveApplied = true;
                        $autoForceLeaveBalanceId = (int) $annualBalance['employee_leave_balance_id'];
                        $autoForceLeaveTypeId = (int) $annualBalance['leave_type_id'];
                        $autoForceLeaveSourceRefId = sprintf('AUTO_FORCE_LEAVE|%d|%s', $empId, $workDate);

                        $notes[] = sprintf(
                            'No attendance on workday. Auto force %s applied using available leave balance.',
                            $autoForceLeaveTypeCode
                        );

                        $details[] = $this->makeDetail(
                            'AUTO_FORCE_LEAVE_CHECKED',
                            'Available annual leave balance found. Auto force leave will be applied.',
                            'SYSTEM',
                            (string) $autoForceLeaveBalanceId,
                            'Available balance: ' . (string) $annualBalance['available_balance']
                        );
                    } else {
                        $attendanceStatusCode = 'ABSENT';
                        $presenceTypeCode = 'NO_SHOW';
                        $leaveFlag = false;

                        $notes[] = 'Workday without attendance/leave/exception coverage and no annual leave balance available.';

                        $details[] = $this->makeDetail(
                            'AUTO_FORCE_LEAVE_CHECKED',
                            'No annual leave balance available. Fallback to ABSENT.',
                            'SYSTEM',
                            null,
                            null
                        );
                    }
                }
            }
        }

        if ($attendanceStatusCode === null) {
            $attendanceStatusCode = $this->attendanceAbsenceGeneratorService->resolveStatusForNoAttendance(
                isWorkday: (bool) $calendar['is_workday'],
                dayTypeCode: $calendar['day_type_code']
            );

            $presenceTypeCode = $calendar['is_workday'] ? 'NO_SHOW' : null;

            $notes[] = 'No attendance status was resolved before exception application. Fallback non-attendance status applied.';
        }

        if (!$calendar['is_workday'] && $hasActualAttendance) {
            $attendanceStatusCode = 'PRESENT';

            if (!$presenceTypeCode) {
                $presenceTypeCode = 'PARTIAL';
            }

            $notes[] = sprintf(
                'Attendance occurred on non-workday (%s).',
                $calendar['day_type_code']
            );
        }

        if ($this->shouldForceReviewBecauseShiftMissing(
            calendarIsWorkday: (bool) $calendar['is_workday'],
            hasActualAttendance: $hasActualAttendance,
            shiftId: $shiftId
        )) {
            $attendanceStatusCode = 'MANUAL_REVIEW';
            $anomalyFlag = true;
            $notes[] = 'Attendance exists on workday but no shift resolved.';
        }

        if (
            in_array($attendanceStatusCode, ['HOLIDAY', 'OFF', 'LEAVE', 'SICK', 'PERMISSION', 'ABSENT'], true)
        ) {
            $workMin = 0;
            $lateMin = 0;
            $earlyOutMin = 0;
            $overtimeMin = 0;
        }

        if ($attendanceStatusCode !== 'PRESENT') {
            $overtimeMin = 0;
        }

        if ($overtime['has_approved_overtime']) {
            $notes[] = sprintf(
                'Approved overtime request found. Final overtime min=%d (approved=%d).',
                $overtimeMin,
                (int) $overtime['approved_overtime_min']
            );
        }

        $dailyDraft = [
            'emp_id' => $empId,
            'work_date' => $workDate,
            'branch_id' => $branchId,
            'policy_id' => (int) $branchPolicy->policy_id,
            'shift_id' => $shiftId,
            'scheduled_in_datetime' => $schedule['scheduled_in_datetime'],
            'scheduled_out_datetime' => $schedule['scheduled_out_datetime'],
            'actual_in_datetime' => $actualInDatetime,
            'actual_out_datetime' => $actualOutDatetime,
            'break_min' => $schedule['break_min'],
            'work_min' => $workMin,
            'late_min' => $lateMin,
            'early_out_min' => $earlyOutMin,
            'overtime_min' => $overtimeMin,
            'overtime_workday_min' => 0,
            'overtime_holiday_min' => 0,
            'overtime_offday_min' => 0,
            'attendance_status_code' => $attendanceStatusCode,
            'presence_type_code' => $presenceTypeCode,
            'anomaly_flag' => $anomalyFlag,
            'exception_flag' => $exceptionFlag,
            'leave_flag' => $leaveFlag,
            'notes' => implode(' ', $notes),
        ];

        $exceptionResult = $this->exceptionApplierService->apply(
            $dailyDraft,
            $rawExceptions,
            [
                'policy' => $policy,
                'work_date' => $workDate,
                'calendar_is_workday' => (bool) $calendar['is_workday'],
            ]
        );
        $dailyDraft = $exceptionResult->dailyDraft;

        $finalOvertimeBreakdown = $this->resolveOvertimeBreakdown(
            finalOvertimeMin: (int) ($dailyDraft['overtime_min'] ?? 0),
            dayTypeCode: $calendar['day_type_code'] ?? null,
            isWorkday: (bool) ($calendar['is_workday'] ?? true),
        );

        $dailyDraft['overtime_workday_min'] = $finalOvertimeBreakdown['overtime_workday_min'];
        $dailyDraft['overtime_holiday_min'] = $finalOvertimeBreakdown['overtime_holiday_min'];
        $dailyDraft['overtime_offday_min'] = $finalOvertimeBreakdown['overtime_offday_min'];

        $details[] = $this->makeDetail(
            'OVERTIME_BREAKDOWN',
            'Overtime bucket classified from final daily draft.',
            'SYSTEM',
            null,
            sprintf(
                'Total=%d, workday=%d, holiday=%d, offday=%d, day_type=%s, is_workday=%s',
                (int) ($dailyDraft['overtime_min'] ?? 0),
                $finalOvertimeBreakdown['overtime_workday_min'],
                $finalOvertimeBreakdown['overtime_holiday_min'],
                $finalOvertimeBreakdown['overtime_offday_min'],
                (string) ($calendar['day_type_code'] ?? 'NULL'),
                (bool) ($calendar['is_workday'] ?? true) ? 'true' : 'false'
            )
        );

        foreach ($exceptionResult->detailLogs as $log) {
            $details[] = $this->makeDetail(
                $log['step_name'],
                $log['step_result'],
                $log['source_type_code'],
                $log['source_ref_id'],
                $log['notes']
            );
        }

        $reviewReasonCode = $dailyDraft['review_reason_code'] ?? null;

        if (
            $reviewReasonCode === null
            && ($dailyDraft['attendance_status_code'] ?? null) === 'MANUAL_REVIEW'
        ) {
            $reviewReasonCode = $this->detectReviewReason(
                hasIn: !empty($dailyDraft['actual_in_datetime']),
                hasOut: !empty($dailyDraft['actual_out_datetime']),
                hasBoth: !empty($dailyDraft['actual_in_datetime']) && !empty($dailyDraft['actual_out_datetime']),
                hasAnomaly: (bool) ($dailyDraft['anomaly_flag'] ?? false),
                workMin: (int) ($dailyDraft['work_min'] ?? 0),
                shiftMissing: empty($dailyDraft['shift_id'])
            );
        }

        $attendanceScore = $this->calculateAttendanceScore(
            attendanceStatusCode: (string) ($dailyDraft['attendance_status_code'] ?? 'ABSENT'),
            presenceTypeCode: (string) ($dailyDraft['presence_type_code'] ?? 'PARTIAL'),
            lateMin: (int) ($dailyDraft['late_min'] ?? 0),
            earlyOutMin: (int) ($dailyDraft['early_out_min'] ?? 0),
            reviewReasonCode: $reviewReasonCode
        );

        $lateSeverityCode = $this->getLateSeverity((int) ($dailyDraft['late_min'] ?? 0));

        $details[] = $this->makeDetail(
            'STATUS_DECIDED',
            sprintf(
                'Final status=%s, presence_type=%s',
                $dailyDraft['attendance_status_code'] ?? 'NULL',
                $dailyDraft['presence_type_code'] ?? 'NULL'
            ),
            'SYSTEM',
            null,
            (string) ($dailyDraft['notes'] ?? null)
        );

        $now = now();

        return [
            'daily' => array_merge($dailyDraft, [
                'attendance_score' => $attendanceScore,
                'late_severity_code' => $lateSeverityCode,
                'pattern_flag' => false,
                'review_reason_code' => $reviewReasonCode,
                'calculation_version' => 10,
                'calculated_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,

                // internal metadata for post-persist mutation
                '_auto_force_leave_applied' => $autoForceLeaveApplied,
                '_auto_force_leave_balance_id' => $autoForceLeaveBalanceId,
                '_auto_force_leave_type_id' => $autoForceLeaveTypeId,
                '_auto_force_leave_type_code' => $autoForceLeaveTypeCode,
                '_auto_force_leave_qty' => $autoForceLeaveQty,
                '_auto_force_leave_source_ref_id' => $autoForceLeaveSourceRefId,
                '_previous_attendance_status_code' => $previousDaily->attendance_status_code ?? null,
                '_previous_leave_flag' => isset($previousDaily->leave_flag) ? (bool) $previousDaily->leave_flag : false,
            ]),
            'details' => $details,
        ];
    }

    private function persistDailyResult(array $dailyRow, array $details): void
    {
        $dailyRowForDb = $dailyRow;

        unset(
            $dailyRowForDb['_auto_force_leave_applied'],
            $dailyRowForDb['_auto_force_leave_balance_id'],
            $dailyRowForDb['_auto_force_leave_type_id'],
            $dailyRowForDb['_auto_force_leave_type_code'],
            $dailyRowForDb['_auto_force_leave_qty'],
            $dailyRowForDb['_auto_force_leave_source_ref_id'],
            $dailyRowForDb['_previous_attendance_status_code'],
            $dailyRowForDb['_previous_leave_flag'],
        );

        DB::transaction(function () use ($dailyRowForDb, $details): void {
            $existing = DB::table('attendance_daily')
                ->where('emp_id', $dailyRowForDb['emp_id'])
                ->whereDate('work_date', $dailyRowForDb['work_date'])
                ->first();

            if ($existing) {
                DB::table('attendance_daily')
                    ->where('attendance_daily_id', $existing->attendance_daily_id)
                    ->update([
                        'branch_id' => $dailyRowForDb['branch_id'],
                        'policy_id' => $dailyRowForDb['policy_id'],
                        'shift_id' => $dailyRowForDb['shift_id'],
                        'scheduled_in_datetime' => $dailyRowForDb['scheduled_in_datetime'],
                        'scheduled_out_datetime' => $dailyRowForDb['scheduled_out_datetime'],
                        'actual_in_datetime' => $dailyRowForDb['actual_in_datetime'],
                        'actual_out_datetime' => $dailyRowForDb['actual_out_datetime'],
                        'break_min' => $dailyRowForDb['break_min'],
                        'work_min' => $dailyRowForDb['work_min'],
                        'late_min' => $dailyRowForDb['late_min'],
                        'early_out_min' => $dailyRowForDb['early_out_min'],
                        'overtime_min' => $dailyRowForDb['overtime_min'],
                        'overtime_workday_min' => $dailyRowForDb['overtime_workday_min'],
                        'overtime_holiday_min' => $dailyRowForDb['overtime_holiday_min'],
                        'overtime_offday_min' => $dailyRowForDb['overtime_offday_min'],                        
                        'attendance_status_code' => $dailyRowForDb['attendance_status_code'],
                        'presence_type_code' => $dailyRowForDb['presence_type_code'],
                        'anomaly_flag' => $dailyRowForDb['anomaly_flag'],
                        'exception_flag' => $dailyRowForDb['exception_flag'],
                        'leave_flag' => $dailyRowForDb['leave_flag'],
                        'attendance_score' => $dailyRowForDb['attendance_score'],
                        'late_severity_code' => $dailyRowForDb['late_severity_code'],
                        'pattern_flag' => $dailyRowForDb['pattern_flag'],
                        'review_reason_code' => $dailyRowForDb['review_reason_code'],
                        'calculation_version' => $dailyRowForDb['calculation_version'],
                        'calculated_at' => $dailyRowForDb['calculated_at'],
                        'notes' => $dailyRowForDb['notes'],
                        'updated_at' => $dailyRowForDb['updated_at'],
                    ]);

                $attendanceDailyId = (int) $existing->attendance_daily_id;

                DB::table('attendance_daily_details')
                    ->where('attendance_daily_id', $attendanceDailyId)
                    ->delete();
            } else {
                $attendanceDailyId = DB::table('attendance_daily')->insertGetId(
                    $dailyRowForDb,
                    'attendance_daily_id'
                );
            }

            $detailRows = array_map(function (array $detail) use ($attendanceDailyId): array {
                return [
                    'attendance_daily_id' => $attendanceDailyId,
                    'step_name' => $detail['step_name'],
                    'step_result' => $detail['step_result'],
                    'source_type_code' => $detail['source_type_code'],
                    'source_ref_id' => $detail['source_ref_id'],
                    'notes' => $detail['notes'],
                    'created_at' => now(),
                ];
            }, $details);

            if (!empty($detailRows)) {
                DB::table('attendance_daily_details')->insert($detailRows);
            }
        });
    }

    private function resolveBuildRange(?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom && $dateTo) {
            return [$dateFrom, $dateTo];
        }

        $period = DB::table('payroll_periods')
            ->where('payroll_period_status_code', 'OPEN')
            ->orderByDesc('period_start_date')
            ->first();

        if (!$period) {
            throw new RuntimeException('No build range provided and no OPEN payroll period found.');
        }

        return [
            $dateFrom ?: $period->period_start_date,
            $dateTo ?: $period->period_end_date,
        ];
    }

    private function getEmployeeIdsInRange(string $dateFrom, string $dateTo): array
    {
        return DB::table('employee_assignments')
            ->whereDate('effective_start_date', '<=', $dateTo)
            ->where(function ($q) use ($dateFrom) {
                $q->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $dateFrom);
            })
            ->distinct()
            ->orderBy('emp_id')
            ->pluck('emp_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function getNormalizedLogsGrouped(string $dateFrom, string $dateTo): Collection
    {
        return DB::table('attendance_logs_normalized')
            ->whereDate('log_datetime', '>=', $dateFrom)
            ->whereDate('log_datetime', '<=', $dateTo)
            ->orderBy('emp_id')
            ->orderBy('log_datetime')
            ->orderBy('normalized_log_id')
            ->get()
            ->groupBy(function ($row) {
                return $this->makeEmpDateKey(
                    (int) $row->emp_id,
                    substr((string) $row->log_datetime, 0, 10)
                );
            });
    }

    private function makeEmpDateKey(int $empId, string $workDate): string
    {
        return $empId . '|' . $workDate;
    }

    private function resolveAssignment(int $empId, string $workDate): ?object
    {
        return DB::table('employee_assignments')
            ->where('emp_id', $empId)
            ->whereDate('effective_start_date', '<=', $workDate)
            ->where(function ($q) use ($workDate) {
                $q->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $workDate);
            })
            ->orderByDesc('is_primary')
            ->orderByDesc('effective_start_date')
            ->first();
    }

    private function resolveBranchPolicy(int $branchId, string $workDate): ?object
    {
        return DB::table('branch_policy_assignments')
            ->where('branch_id', $branchId)
            ->whereDate('effective_start_date', '<=', $workDate)
            ->where(function ($q) use ($workDate) {
                $q->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $workDate);
            })
            ->orderByDesc('effective_start_date')
            ->first();
    }

    private function resolveShift(int $empId, string $workDate, array $exception): array
    {
        if ($exception['has_shift_override'] && $exception['shift_id_value']) {
            return [
                'shift_id' => (int) $exception['shift_id_value'],
                'source_type_code' => 'APPROVAL',
                'source_ref_id' => 'EXCEPTION_SHIFT_OVERRIDE',
                'notes' => 'Shift override from attendance exception.',
            ];
        }

        $roster = DB::table('employee_shift_rosters')
            ->where('emp_id', $empId)
            ->whereDate('work_date', $workDate)
            ->first();

        if ($roster && $roster->shift_id) {
            return [
                'shift_id' => (int) $roster->shift_id,
                'source_type_code' => $roster->source_type_code,
                'source_ref_id' => $roster->source_ref_id,
                'notes' => $roster->notes,
            ];
        }

        $workPatternShift = $this->resolveShiftFromWorkPatternRule($empId, $workDate);

        if ($workPatternShift !== null) {
            return $workPatternShift;
        }

        // Non-obligation days (Sunday / holiday) should not
        // automatically inherit default weekday shift assignment.
        //
        // If no exception / roster / work-pattern shift exists,
        // leave shift empty so attendance is treated as
        // non-obligation attendance.
        $calendarRow = DB::table('branch_calendars')
            ->join('employee_assignments as ea', 'ea.branch_id', '=', 'branch_calendars.branch_id')
            ->where('ea.emp_id', $empId)
            ->whereDate('ea.effective_start_date', '<=', $workDate)
            ->where(function ($query) use ($workDate) {
                $query->whereNull('ea.effective_end_date')
                    ->orWhereDate('ea.effective_end_date', '>=', $workDate);
            })
            ->whereDate('branch_calendars.work_date', $workDate)
            ->select([
                'branch_calendars.day_type_code',
                'branch_calendars.is_workday',
            ])
            ->first();

        $isSunday = Carbon::parse($workDate)->isoWeekday() === 7;

        $isHoliday =
            $calendarRow
            && in_array($calendarRow->day_type_code, [
                'HOLIDAY_NATIONAL',
                'HOLIDAY_COMPANY',
            ], true);

        if ($isSunday || $isHoliday) {
            return [
                'shift_id' => null,
                'source_type_code' => 'SYSTEM',
                'source_ref_id' => null,
                'notes' => 'Non-obligation attendance day. No scheduled shift applied.',
            ];
        }

        $shiftAssignment = DB::table('employee_shift_assignments')
            ->where('emp_id', $empId)
            ->whereDate('effective_start_date', '<=', $workDate)
            ->where(function ($q) use ($workDate) {
                $q->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $workDate);
            })
            ->orderByDesc('effective_start_date')
            ->first();

        if ($shiftAssignment && $shiftAssignment->shift_id) {
            return [
                'shift_id' => (int) $shiftAssignment->shift_id,
                'source_type_code' => 'SYSTEM',
                'source_ref_id' => isset($shiftAssignment->employee_shift_assignment_id)
                    ? (string) $shiftAssignment->employee_shift_assignment_id
                    : null,
                'notes' => $shiftAssignment->notes,
            ];
        }

        return [
            'shift_id' => null,
            'source_type_code' => 'SYSTEM',
            'source_ref_id' => null,
            'notes' => 'No shift found.',
        ];
    }

    private function resolveShiftFromWorkPatternRule(int $empId, string $workDate): ?array
    {
        $dayOfWeekCode = $this->mapIsoDowToDayCode(
            Carbon::parse($workDate)->isoWeekday()
        );

        if ($dayOfWeekCode === null) {
            return null;
        }

        $rule = DB::table('employee_work_pattern_assignments as ewpa')
            ->join('work_patterns as wp', 'wp.work_pattern_id', '=', 'ewpa.work_pattern_id')
            ->join('work_pattern_rules as wpr', 'wpr.work_pattern_id', '=', 'wp.work_pattern_id')
            ->where('ewpa.emp_id', $empId)
            ->where('wp.active', true)
            ->where('wpr.active', true)
            ->whereNotNull('wpr.shift_id')
            ->where('wpr.day_of_week_code', $dayOfWeekCode)
            ->whereDate('ewpa.effective_start_date', '<=', $workDate)
            ->where(function ($query) use ($workDate) {
                $query->whereNull('ewpa.effective_end_date')
                    ->orWhereDate('ewpa.effective_end_date', '>=', $workDate);
            })
            ->orderByDesc('ewpa.effective_start_date')
            ->orderBy('wpr.priority_order')
            ->select([
                'ewpa.employee_work_pattern_assignment_id',
                'wp.work_pattern_code',
                'wp.work_pattern_name',
                'wpr.work_pattern_rule_id',
                'wpr.rule_code',
                'wpr.rule_name',
                'wpr.rule_type_code',
                'wpr.shift_id',
            ])
            ->first();

        if (! $rule || ! $rule->shift_id) {
            return null;
        }

        return [
            'shift_id' => (int) $rule->shift_id,
            'source_type_code' => 'SYSTEM',
            'source_ref_id' => (string) $rule->work_pattern_rule_id,
            'notes' => sprintf(
                'Shift resolved from work pattern rule. Pattern=%s, Rule=%s (%s).',
                (string) $rule->work_pattern_code,
                (string) $rule->rule_code,
                (string) $rule->rule_type_code
            ),
        ];
    }

    private function mapIsoDowToDayCode(int $isoDow): ?string
    {
        return match ($isoDow) {
            1 => 'MON',
            2 => 'TUE',
            3 => 'WED',
            4 => 'THU',
            5 => 'FRI',
            6 => 'SAT',
            7 => 'SUN',
            default => null,
        };
    }

    private function resolveSchedule(string $workDate, ?int $shiftId, object $policy): array
    {
        $scheduledInDatetime = null;
        $scheduledOutDatetime = null;
        $breakMin = 0;
        $expectedWorkMin = 0;
        $shift = null;

        if ($shiftId) {
            $shift = DB::table('shifts')
                ->where('shift_id', $shiftId)
                ->first();
        }

        if ($shift) {
            $breakMin = (int) ($shift->break_min ?? 0);

            if (!empty($shift->start_time)) {
                $scheduledInDatetime = Carbon::parse($workDate . ' ' . $shift->start_time, 'Asia/Jakarta');
            }

            if (!empty($shift->end_time)) {
                $scheduledOutDatetime = Carbon::parse($workDate . ' ' . $shift->end_time, 'Asia/Jakarta');

                if (!empty($shift->cross_day_flag) && $shift->cross_day_flag) {
                    $scheduledOutDatetime = $scheduledOutDatetime->copy()->addDay();
                }
            }

            if (!empty($shift->default_work_min)) {
                $expectedWorkMin = (int) $shift->default_work_min;
            } elseif ($scheduledInDatetime && $scheduledOutDatetime) {
                $expectedWorkMin = max(
                    0,
                    $scheduledInDatetime->diffInMinutes($scheduledOutDatetime, false) - $breakMin
                );
            }
        }

        if ($expectedWorkMin <= 0) {
            $expectedWorkMin = (int) ($policy->min_work_min_full_day ?? 0);
        }

        return [
            'scheduled_in_datetime' => $scheduledInDatetime,
            'scheduled_out_datetime' => $scheduledOutDatetime,
            'break_min' => $breakMin,
            'expected_work_min' => $expectedWorkMin,
            'shift' => $shift,
        ];
    }

    private function analyzeLogs(Collection $logs, object $policy, array $schedule): array
    {
        $clusteredLogs = $this->collapseLogsToClusteredCandidates($logs, $schedule);

        $boundaryPair = $this->resolveBoundaryPairFromClusteredCandidates($clusteredLogs);
        $boundaryIn = $boundaryPair['in_candidate'];
        $boundaryOut = $boundaryPair['out_candidate'];
        $ignoredCandidates = $boundaryPair['ignored_candidates'];

        $lateGraceInMin = (int) ($policy->late_grace_in_min ?? 0);
        $earlyOutGraceMin = (int) ($policy->early_out_grace_min ?? 0);
        $minWorkMinHalfDay = (int) ($policy->min_work_min_half_day ?? 0);
        $minWorkMinFullDay = (int) ($policy->min_work_min_full_day ?? 0);
        $overtimeMinBefore = (int) ($policy->overtime_min_before ?? 0);

        $validIns = $clusteredLogs->filter(function ($row) {
            return $row->derived_event_type_code === 'IN'
                && $row->normalized_status_code === 'VALID';
        });

        $validOuts = $clusteredLogs->filter(function ($row) {
            return $row->derived_event_type_code === 'OUT'
                && $row->normalized_status_code === 'VALID';
        });

        $suspiciousRows = $clusteredLogs->filter(function ($row) {
            return $row->normalized_status_code === 'SUSPICIOUS';
        });

        $actualInDatetime = null;
        $actualOutDatetime = null;
        $attendanceStatusCode = null;
        $presenceTypeCode = null;
        $anomalyFlag = false;
        $notes = [];

        $notes[] = sprintf(
            'Clustered %d raw log(s) into %d candidate tap(s).',
            $logs->count(),
            $clusteredLogs->count()
        );

        if ($ignoredCandidates->isNotEmpty()) {
            $notes[] = sprintf(
                'Ignored %d middle candidate tap(s) for primary IN/OUT pairing.',
                $ignoredCandidates->count()
            );
        }

        $scheduledIn = $schedule['scheduled_in_datetime']
            ? Carbon::parse($schedule['scheduled_in_datetime'])->setTimezone('Asia/Jakarta')
            : null;

        $scheduledOut = $schedule['scheduled_out_datetime']
            ? Carbon::parse($schedule['scheduled_out_datetime'])->setTimezone('Asia/Jakarta')
            : null;

        $midpoint = $this->resolveScheduleMidpoint($schedule);
        if ($midpoint) {
            $midpoint = $midpoint->setTimezone('Asia/Jakarta');
        }

        /**
         * CASE 0: multi-cluster boundary pairing
         * Rule: gunakan candidate paling awal sebagai IN dan paling akhir sebagai OUT
         * setelah raw taps dibersihkan menjadi clusters.
         */
        if (
            $clusteredLogs->count() >= 2
            && $this->isUsableBoundaryPair($boundaryIn, $boundaryOut)
        ) {
            $actualInDatetime = $boundaryIn->log_datetime;
            $actualOutDatetime = $boundaryOut->log_datetime;
            $attendanceStatusCode = 'PRESENT';
            $presenceTypeCode = 'FULL_DAY';

            $notes[] = 'Primary IN/OUT resolved from multi-cluster boundary candidates.';
            $notes[] = sprintf(
                'Boundary IN=%s, OUT=%s.',
                $actualInDatetime,
                $actualOutDatetime
            );
        }

        /**
         * CASE 1: complete valid IN + OUT
         */
        elseif ($validIns->isNotEmpty() && $validOuts->isNotEmpty()) {
            $actualInDatetime = $validIns->sortBy('log_datetime')->first()->log_datetime;
            $actualOutDatetime = $validOuts->sortByDesc('log_datetime')->first()->log_datetime;
            $attendanceStatusCode = 'PRESENT';
            $presenceTypeCode = 'FULL_DAY';
            $notes[] = 'Built from valid IN and OUT normalized events.';
            $notes[] = 'IN/OUT built from clustered candidate taps.';
        }

        /**
         * CASE 2: only valid IN
         * Rule perusahaan: tetap hadir, missing OUT
         */
        elseif ($validIns->isNotEmpty()) {
            $actualInDatetime = $validIns->sortBy('log_datetime')->first()->log_datetime;
            $attendanceStatusCode = 'PRESENT';
            $presenceTypeCode = 'PARTIAL';
            $anomalyFlag = true;
            $notes[] = 'Only valid IN found. Treated as PRESENT with missing OUT.';
            $notes[] = 'Single clustered candidate resolved as IN.';
        }

        /**
         * CASE 3: only valid OUT
         * Rule perusahaan: tetap hadir, missing IN
         */
        elseif ($validOuts->isNotEmpty()) {
            $actualOutDatetime = $validOuts->sortByDesc('log_datetime')->first()->log_datetime;
            $attendanceStatusCode = 'PRESENT';
            $presenceTypeCode = 'PARTIAL';
            $anomalyFlag = true;
            $notes[] = 'Only valid OUT found. Treated as PRESENT with missing IN.';
            $notes[] = 'Single clustered candidate resolved as OUT.';
        }

        /**
         * CASE 4: exactly one suspicious tap
         * Rule perusahaan: gunakan midpoint shift, bukan jam 12
         */
        elseif ($suspiciousRows->count() === 1) {
            $singleTap = $suspiciousRows->first();
            $singleTapDt = Carbon::parse($singleTap->log_datetime)->setTimezone('Asia/Jakarta');

            $presenceTypeCode = 'PARTIAL';
            $attendanceStatusCode = 'PRESENT';
            $anomalyFlag = true;

            if (isset($singleTap->cluster_size)) {
                $notes[] = 'Suspicious candidate came from cluster size=' . $singleTap->cluster_size . '.';
            }

            if ($midpoint) {
                $notes[] = 'Single suspicious tap evaluated using schedule midpoint ' . $midpoint->toDateTimeString() . '.';

                if ($singleTapDt->lt($midpoint)) {
                    $actualInDatetime = $singleTap->log_datetime;
                    $notes[] = 'Single suspicious tap inferred as IN using schedule midpoint.';
                } else {
                    $actualOutDatetime = $singleTap->log_datetime;
                    $notes[] = 'Single suspicious tap inferred as OUT using schedule midpoint.';
                }
            } else {
                $notes[] = 'Schedule midpoint unavailable; fallback heuristic applied.';

                if ($singleTapDt->hour < 12) {
                    $actualInDatetime = $singleTap->log_datetime;
                    $notes[] = 'Single suspicious tap fallback inferred as IN.';
                } else {
                    $actualOutDatetime = $singleTap->log_datetime;
                    $notes[] = 'Single suspicious tap fallback inferred as OUT.';
                }
            }
        }

        /**
         * CASE 5: no usable logs
         */
        else {
            return [
                'has_actual_attendance' => false,
                'actual_in_datetime' => null,
                'actual_out_datetime' => null,
                'attendance_status_code' => null,
                'presence_type_code' => null,
                'anomaly_flag' => false,
                'work_min' => 0,
                'late_min' => 0,
                'early_out_min' => 0,
                'overtime_min' => 0,
                'notes' => [],
                'notes_text' => 'No normalized logs for this day.',
                'step_result' => 'No normalized logs.',
            ];
        }

        $actualIn = $actualInDatetime ? Carbon::parse($actualInDatetime)->setTimezone('Asia/Jakarta') : null;
        $actualOut = $actualOutDatetime ? Carbon::parse($actualOutDatetime)->setTimezone('Asia/Jakarta') : null;
        $breakMin = (int) ($schedule['break_min'] ?? 0);

        $workMin = 0;
        $lateMin = 0;
        $earlyOutMin = 0;
        $overtimeMin = 0;

        if ($actualIn && $actualOut) {
            $grossWorkMin = $actualIn->diffInMinutes($actualOut, false);
            $workMin = max(0, $grossWorkMin - $breakMin);
        }

        if ($scheduledIn && $actualIn && $actualIn->greaterThan($scheduledIn)) {
            $rawLateMin = $scheduledIn->diffInMinutes($actualIn);
            $lateMin = max(0, $rawLateMin - $lateGraceInMin);
        }

        if ($scheduledOut && $actualOut && $actualOut->lessThan($scheduledOut)) {
            $rawEarlyOutMin = $actualOut->diffInMinutes($scheduledOut);
            $earlyOutMin = max(0, $rawEarlyOutMin - $earlyOutGraceMin);
        }

        if (
            $attendanceStatusCode === 'PRESENT' &&
            $actualIn &&
            $actualOut &&
            $scheduledOut &&
            $actualOut->greaterThan($scheduledOut)
        ) {
            $rawOtMin = $scheduledOut->diffInMinutes($actualOut);
            $overtimeMin = max(0, $rawOtMin - $overtimeMinBefore);
        }

        /**
         * Presence classification
         */
        if ($attendanceStatusCode === 'PRESENT' && $actualIn && $actualOut) {
            if ($minWorkMinFullDay > 0 && $workMin >= $minWorkMinFullDay) {
                $presenceTypeCode = 'FULL_DAY';
            } elseif ($minWorkMinHalfDay > 0 && $workMin >= $minWorkMinHalfDay) {
                $presenceTypeCode = 'HALF_DAY';
            } else {
                $presenceTypeCode = 'PARTIAL';
                $notes[] = 'Work minutes below half-day threshold.';
            }
        }

        /**
         * Basic anomaly markers
         */
        if ($actualIn && $scheduledOut && $actualIn->greaterThan($scheduledOut)) {
            $anomalyFlag = true;
            $notes[] = 'IN after scheduled OUT.';
        }

        if ($actualIn && $actualOut) {
            $diffMin = $actualIn->diffInMinutes($actualOut, false);

            if ($diffMin < 60) {
                $anomalyFlag = true;
                $notes[] = 'IN/OUT too close.';
            }
        }

        if ($actualIn && $actualOut && $actualOut->lessThan($actualIn)) {
            $anomalyFlag = true;
            $notes[] = 'OUT before IN.';
        }

        /**
         * Rule perusahaan:
         * missing IN / missing OUT tetap PRESENT, tidak otomatis MANUAL_REVIEW
         * tapi overtime harus nol kalau tidak full pair/full day
         */
        if ($anomalyFlag || $presenceTypeCode !== 'FULL_DAY') {
            $overtimeMin = 0;
        }

        return [
            'has_actual_attendance' => true,
            'actual_in_datetime' => $actualInDatetime,
            'actual_out_datetime' => $actualOutDatetime,
            'attendance_status_code' => $attendanceStatusCode,
            'presence_type_code' => $presenceTypeCode,
            'anomaly_flag' => $anomalyFlag,
            'work_min' => $workMin,
            'late_min' => $lateMin,
            'early_out_min' => $earlyOutMin,
            'overtime_min' => $overtimeMin,
            'notes' => $notes,
            'notes_text' => implode(' ', $notes),
            'step_result' => 'Attendance logs analyzed.',
        ];
    }

    private function shouldForceReviewBecauseShiftMissing(
        bool $calendarIsWorkday,
        bool $hasActualAttendance,
        ?int $shiftId
    ): bool {
        return $calendarIsWorkday && $hasActualAttendance && empty($shiftId);
    }

    private function makeDetail(
        string $stepName,
        ?string $stepResult,
        ?string $sourceTypeCode = null,
        ?string $sourceRefId = null,
        ?string $notes = null
    ): array {
        return [
            'step_name' => $stepName,
            'step_result' => $stepResult,
            'source_type_code' => $sourceTypeCode,
            'source_ref_id' => $sourceRefId,
            'notes' => $notes,
        ];
    }

    private function calculateAttendanceScore(
        string $attendanceStatusCode,
        string $presenceTypeCode,
        int $lateMin,
        int $earlyOutMin,
        ?string $reviewReasonCode
    ): int {
        $score = 0;

        if ($attendanceStatusCode === 'PRESENT' && $presenceTypeCode === 'FULL_DAY') {
            $score = 100;
        } elseif ($attendanceStatusCode === 'PRESENT' && $presenceTypeCode === 'HALF_DAY') {
            $score = 85;
        } elseif ($attendanceStatusCode === 'PRESENT' && $presenceTypeCode === 'PARTIAL') {
            $score = 70;
        } elseif (in_array($attendanceStatusCode, ['LEAVE', 'SICK', 'PERMISSION'], true)) {
            $score = 90;
        } elseif (in_array($attendanceStatusCode, ['HOLIDAY', 'OFF'], true)) {
            $score = 100;
        } elseif ($attendanceStatusCode === 'MANUAL_REVIEW') {
            if ($reviewReasonCode === 'INVALID_PAIR') {
                $score = 20;
            } elseif ($reviewReasonCode === 'SHIFT_MISSING') {
                $score = 25;
            } elseif ($reviewReasonCode === 'MISSING_IN' || $reviewReasonCode === 'MISSING_OUT') {
                $score = 35;
            } else {
                $score = 40;
            }
        } elseif ($attendanceStatusCode === 'INCOMPLETE') {
            $score = 30;
        } elseif ($attendanceStatusCode === 'ABSENT') {
            $score = 0;
        }

        if ($reviewReasonCode !== 'INVALID_PAIR') {
            $score -= (int) ($lateMin / 2);
            $score -= (int) ($earlyOutMin / 2);
        }

        return max(0, min(100, $score));
    }

    private function getLateSeverity(int $lateMin): ?string
    {
        if ($lateMin === 0) {
            return null;
        }

        if ($lateMin <= 15) {
            return 'MINOR';
        }

        if ($lateMin <= 60) {
            return 'MEDIUM';
        }

        return 'MAJOR';
    }

    private function detectReviewReason(
        bool $hasIn,
        bool $hasOut,
        bool $hasBoth,
        bool $hasAnomaly,
        int $workMin,
        bool $shiftMissing = false
    ): ?string {
        if ($shiftMissing) {
            return 'SHIFT_MISSING';
        }

        if ($hasBoth && $hasAnomaly) {
            return 'INVALID_PAIR';
        }

        if ($hasIn && !$hasOut) {
            return 'MISSING_OUT';
        }

        if (!$hasIn && $hasOut) {
            return 'MISSING_IN';
        }

        if (!$hasIn && !$hasOut) {
            return 'NO_VALID_LOG';
        }

        return null;
    }

    private function applyPatternDetection(array $empIds): void
    {
        if (empty($empIds)) {
            return;
        }

        DB::table('attendance_daily')
            ->whereIn('emp_id', $empIds)
            ->update(['pattern_flag' => false]);

        $rowsByEmployee = DB::table('attendance_daily')
            ->whereIn('emp_id', $empIds)
            ->orderBy('emp_id')
            ->orderBy('work_date')
            ->get()
            ->groupBy('emp_id');

        foreach ($rowsByEmployee as $empId => $rows) {
            $consecutiveManualReview = 0;

            foreach ($rows as $row) {
                if ($row->attendance_status_code === 'MANUAL_REVIEW') {
                    $consecutiveManualReview++;
                } else {
                    $consecutiveManualReview = 0;
                }

                if ($consecutiveManualReview >= 3) {
                    DB::table('attendance_daily')
                        ->where('attendance_daily_id', $row->attendance_daily_id)
                        ->update(['pattern_flag' => true]);
                }
            }
        }
    }

    private function shouldDeferAutoForceLeaveToPeriodObligation(
        int $empId,
        string $workDate,
        int $branchId
    ): bool {
        $date = Carbon::parse($workDate);

        // Hanya Sabtu.
        if ($date->isoWeekday() !== 6) {
            return false;
        }

        // Sabtu libur nasional/perusahaan sudah bukan eligible obligation.
        $calendar = DB::table('branch_calendars')
            ->where('branch_id', $branchId)
            ->whereDate('work_date', $workDate)
            ->first();

        if (
            $calendar
            && in_array((string) $calendar->day_type_code, ['HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY'], true)
        ) {
            return false;
        }

        return DB::table('employee_work_pattern_assignments as ewpa')
            ->join('work_patterns as wp', 'wp.work_pattern_id', '=', 'ewpa.work_pattern_id')
            ->join('work_pattern_rules as wpr', 'wpr.work_pattern_id', '=', 'wp.work_pattern_id')
            ->where('ewpa.emp_id', $empId)
            ->where('wp.active', true)
            ->where('wpr.active', true)
            ->where('wpr.rule_code', 'OSA_SAT_ALLOWANCE')
            ->whereDate('ewpa.effective_start_date', '<=', $workDate)
            ->where(function ($query) use ($workDate) {
                $query->whereNull('ewpa.effective_end_date')
                    ->orWhereDate('ewpa.effective_end_date', '>=', $workDate);
            })
            ->exists();
    }

    private function reconcileAutoForceLeaveUsage(array $dailyRow): void
    {
        $empId = (int) ($dailyRow['emp_id'] ?? 0);
        $workDate = (string) ($dailyRow['work_date'] ?? '');
        $shouldApplyAutoForce = (bool) ($dailyRow['_auto_force_leave_applied'] ?? false);

        $requestedBalanceId = $dailyRow['_auto_force_leave_balance_id'] ?? null;
        $requestedLeaveTypeId = $dailyRow['_auto_force_leave_type_id'] ?? null;
        $requestedQty = (float) ($dailyRow['_auto_force_leave_qty'] ?? 1.0);
        $sourceRefId = (string) ($dailyRow['_auto_force_leave_source_ref_id'] ?? sprintf('AUTO_FORCE_LEAVE|%d|%s', $empId, $workDate));

        if ($empId <= 0 || $workDate === '' || $sourceRefId === '') {
            return;
        }

        $latestUseTx = DB::table('employee_leave_balance_transactions')
            ->where('emp_id', $empId)
            ->where('transaction_type_code', 'USE_AUTO_FORCE')
            ->where('source_ref_id', $sourceRefId)
            ->orderByDesc('employee_leave_balance_transaction_id')
            ->first();

        $latestUseAlreadyRestored = false;

        if ($latestUseTx) {
            $latestUseAlreadyRestored = DB::table('employee_leave_balance_transactions')
                ->where('transaction_type_code', 'RESTORE_CANCELLED')
                ->where('reverses_transaction_id', $latestUseTx->employee_leave_balance_transaction_id)
                ->exists();
        }

        /**
         * CASE 1:
         * Current result requires auto force leave
         */
        if ($shouldApplyAutoForce) {
            if (!$requestedBalanceId || !$requestedLeaveTypeId) {
                return;
            }

            // No previous auto-force usage -> create one
            if (!$latestUseTx) {
                $this->leaveBalanceMutationService->consumeAutoForce(
                    empId: $empId,
                    leaveTypeId: (int) $requestedLeaveTypeId,
                    workDate: $workDate,
                    qty: $requestedQty,
                    employeeLeaveBalanceId: (int) $requestedBalanceId,
                    sourceTypeCode: 'SYSTEM',
                    sourceRefId: $sourceRefId,
                    notes: 'Auto force annual leave from attendance daily builder'
                );

                return;
            }

            // Previous use exists but already restored -> re-apply
            if ($latestUseAlreadyRestored) {
                $this->leaveBalanceMutationService->consumeAutoForce(
                    empId: $empId,
                    leaveTypeId: (int) $requestedLeaveTypeId,
                    workDate: $workDate,
                    qty: $requestedQty,
                    employeeLeaveBalanceId: (int) $requestedBalanceId,
                    sourceTypeCode: 'SYSTEM',
                    sourceRefId: $sourceRefId,
                    notes: 'Re-applied auto force annual leave from attendance daily builder'
                );
            }

            return;
        }

        /**
         * CASE 2:
         * Current result does NOT require auto force leave,
         * but previous persisted result was leave and auto-force use exists.
         * Then restore the leave balance if not already restored.
         */
        if (!$shouldApplyAutoForce && $latestUseTx && !$latestUseAlreadyRestored) {
            $this->leaveBalanceMutationService->restoreCancelled(
                empId: (int) $latestUseTx->emp_id,
                leaveTypeId: (int) $latestUseTx->leave_type_id,
                transactionDate: $workDate,
                qty: abs((float) $latestUseTx->qty),
                employeeLeaveBalanceId: (int) $latestUseTx->employee_leave_balance_id,
                reversesTransactionId: (int) $latestUseTx->employee_leave_balance_transaction_id,
                sourceTypeCode: 'SYSTEM',
                sourceRefId: 'RESTORE|' . $sourceRefId,
                notes: 'Restore auto force annual leave because latest attendance result no longer requires it'
            );
        }
    }

    private function getPreviousDailyRow(int $empId, string $workDate): ?object
    {
        return DB::table('attendance_daily')
            ->where('emp_id', $empId)
            ->whereDate('work_date', $workDate)
            ->first();
    }

    private function resolveScheduleMidpoint(array $schedule): ?Carbon
    {
        $scheduledIn = $schedule['scheduled_in_datetime'] ?? null;
        $expectedWorkMin = (int) ($schedule['expected_work_min'] ?? 0);

        if (!$scheduledIn || $expectedWorkMin <= 0) {
            return null;
        }

        return Carbon::parse($scheduledIn)->copy()->addMinutes((int) floor($expectedWorkMin / 2));
    }
    
    private function clusterLogs(Collection $logs, int $thresholdMinutes = 2): Collection
    {
        if ($logs->isEmpty()) {
            return collect();
        }

        $sorted = $logs->sortBy('log_datetime')->values();
        $clusters = [];
        $currentCluster = collect([$sorted->first()]);

        for ($i = 1; $i < $sorted->count(); $i++) {
            $previous = $currentCluster->last();
            $current = $sorted[$i];

            $previousDt = Carbon::parse($previous->log_datetime)->setTimezone('Asia/Jakarta');
            $currentDt = Carbon::parse($current->log_datetime)->setTimezone('Asia/Jakarta');

            $diff = abs($previousDt->diffInMinutes($currentDt, false));

            if ($diff <= $thresholdMinutes) {
                $currentCluster->push($current);
            } else {
                $clusters[] = $currentCluster;
                $currentCluster = collect([$current]);
            }
        }

        $clusters[] = $currentCluster;

        return collect($clusters);
    }

    private function resolveClusterEventType(Collection $cluster, ?Carbon $midpoint): string
    {
        $knownTypes = $cluster
            ->pluck('derived_event_type_code')
            ->filter(fn ($type) => in_array($type, ['IN', 'OUT'], true))
            ->unique()
            ->values();

        if ($knownTypes->count() === 1) {
            return (string) $knownTypes->first();
        }

        $representative = $cluster->sortBy('log_datetime')->first();
        $tapDt = Carbon::parse($representative->log_datetime)->setTimezone('Asia/Jakarta');

        if ($midpoint) {
            return $tapDt->lt($midpoint) ? 'IN' : 'OUT';
        }

        return $tapDt->hour < 12 ? 'IN' : 'OUT';
    }

    private function resolveClusterStatus(Collection $cluster): string
    {
        $statuses = $cluster->pluck('normalized_status_code')->unique()->values();

        if ($statuses->contains('VALID')) {
            return 'VALID';
        }

        if ($statuses->contains('SUSPICIOUS')) {
            return 'SUSPICIOUS';
        }

        return (string) ($statuses->first() ?? 'SUSPICIOUS');
    }

    private function collapseLogsToClusteredCandidates(Collection $logs, array $schedule): Collection
    {
        if ($logs->isEmpty()) {
            return collect();
        }

        $midpoint = $this->resolveScheduleMidpoint($schedule);
        if ($midpoint) {
            $midpoint = $midpoint->setTimezone('Asia/Jakarta');
        }

        $clusters = $this->clusterLogs($logs, 2);

        return $clusters->map(function (Collection $cluster) use ($midpoint) {
            $representative = $cluster->sortBy('log_datetime')->first();

            return (object) [
                'log_datetime' => $representative->log_datetime,
                'derived_event_type_code' => $this->resolveClusterEventType($cluster, $midpoint),
                'normalized_status_code' => $this->resolveClusterStatus($cluster),
                'cluster_size' => $cluster->count(),
                'cluster_notes' => 'Clustered from ' . $cluster->count() . ' tap(s).',
            ];
        })->values();
    }

    private function resolveBoundaryPairFromClusteredCandidates(Collection $clusteredLogs): array
    {
        if ($clusteredLogs->isEmpty()) {
            return [
                'in_candidate' => null,
                'out_candidate' => null,
                'ignored_candidates' => collect(),
            ];
        }

        $sorted = $clusteredLogs->sortBy('log_datetime')->values();

        if ($sorted->count() === 1) {
            return [
                'in_candidate' => null,
                'out_candidate' => null,
                'ignored_candidates' => collect(),
            ];
        }

        $first = $sorted->first();
        $last = $sorted->last();

        $firstDt = Carbon::parse($first->log_datetime)->setTimezone('Asia/Jakarta');
        $lastDt = Carbon::parse($last->log_datetime)->setTimezone('Asia/Jakarta');

        if ($firstDt->equalTo($lastDt)) {
            return [
                'in_candidate' => null,
                'out_candidate' => null,
                'ignored_candidates' => collect(),
            ];
        }

        $ignored = $sorted->slice(1, max(0, $sorted->count() - 2))->values();

        return [
            'in_candidate' => $first,
            'out_candidate' => $last,
            'ignored_candidates' => $ignored,
        ];
    }

    private function isUsableBoundaryPair(?object $inCandidate, ?object $outCandidate): bool
    {
        if (!$inCandidate || !$outCandidate) {
            return false;
        }

        $inDt = Carbon::parse($inCandidate->log_datetime)->setTimezone('Asia/Jakarta');
        $outDt = Carbon::parse($outCandidate->log_datetime)->setTimezone('Asia/Jakarta');

        return $outDt->greaterThan($inDt);
    }

    private function resolveFinalOvertimeMin(
        int $computedOvertimeMin,
        array $overtime,
        bool $hasActualAttendance,
        ?bool $anomalyFlag,
        ?string $presenceTypeCode
    ): int {
        $approvedMin = (int) ($overtime['approved_overtime_min'] ?? 0);
        $hasApproved = (bool) ($overtime['has_approved_overtime'] ?? false);

        // PRIORITAS 1: approved overtime menang dulu.
        if ($hasApproved && $approvedMin > 0) {
            return $approvedMin;
        }

        if (! $hasActualAttendance) {
            return 0;
        }

        if ($anomalyFlag) {
            return 0;
        }

        if ($presenceTypeCode !== 'FULL_DAY') {
            return 0;
        }

        return max(0, $computedOvertimeMin);
    }
    
    private function resolveOvertimeBreakdown(
        int $finalOvertimeMin,
        ?string $dayTypeCode,
        bool $isWorkday
    ): array {
        if ($finalOvertimeMin <= 0) {
            return [
                'overtime_workday_min' => 0,
                'overtime_holiday_min' => 0,
                'overtime_offday_min' => 0,
            ];
        }

        if ($dayTypeCode === null) {
            return [
                'overtime_workday_min' => $finalOvertimeMin,
                'overtime_holiday_min' => 0,
                'overtime_offday_min' => 0,
            ];
        }

        if (in_array($dayTypeCode, ['HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY'], true)) {
            return [
                'overtime_workday_min' => 0,
                'overtime_holiday_min' => $finalOvertimeMin,
                'overtime_offday_min' => 0,
            ];
        }

        if (! $isWorkday || $dayTypeCode === 'WEEKOFF') {
            return [
                'overtime_workday_min' => 0,
                'overtime_holiday_min' => 0,
                'overtime_offday_min' => $finalOvertimeMin,
            ];
        }

        return [
            'overtime_workday_min' => $finalOvertimeMin,
            'overtime_holiday_min' => 0,
            'overtime_offday_min' => 0,
        ];
    }
}