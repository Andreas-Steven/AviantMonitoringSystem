<?php

namespace App\Domains\Attendance\Services;

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

                $processedDays++;
                $upsertedCount++;
                $affectedEmpIds[(int) $empId] = true;
            }
        }

        if (! empty($affectedEmpIds)) {
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

    private function buildOneEmployeeDay(int $empId, string $workDate, Collection $logs): ?array
    {
        $assignment = $this->resolveAssignment($empId, $workDate);

        if (! $assignment || ! $assignment->branch_id) {
            return null;
        }

        $branchId = (int) $assignment->branch_id;

        $branchPolicy = $this->resolveBranchPolicy($branchId, $workDate);
        if (! $branchPolicy || ! $branchPolicy->policy_id) {
            return null;
        }

        $policy = DB::table('attendance_policies')
            ->where('policy_id', $branchPolicy->policy_id)
            ->first();

        if (! $policy) {
            return null;
        }

        $calendar = $this->calendarResolverService->resolveOrFail($branchId, $workDate);
        $leave = $this->leaveResolverService->resolveForDate($empId, $workDate);
        $exception = $this->attendanceExceptionResolverService->resolveForDate($empId, $workDate);

        $details = [];
        $notes = [];

        $details[] = $this->makeDetail('ASSIGNMENT_RESOLVED', 'Employee assignment resolved.', 'SYSTEM', (string) $assignment->assignment_id);
        $details[] = $this->makeDetail('POLICY_RESOLVED', 'Branch attendance policy resolved.', 'SYSTEM', (string) $branchPolicy->branch_policy_assignment_id);
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
            $exception['has_exception'] ? 'Attendance exception found.' : 'No attendance exception.',
            $exception['has_exception'] ? 'APPROVAL' : 'SYSTEM',
            $exception['has_exception'] ? 'EXCEPTION' : null,
            $exception['has_exception'] ? implode(', ', $exception['exception_types']) : null
        );

        $resolvedShift = $this->resolveShift($empId, $workDate, $exception);
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
        $exceptionFlag = (bool) $exception['has_exception'];
        $leaveFlag = (bool) $leave['has_approved_leave'];

        $actualInDatetime = $logAnalysis['actual_in_datetime'];
        $actualOutDatetime = $logAnalysis['actual_out_datetime'];
        $workMin = (int) $logAnalysis['work_min'];
        $lateMin = (int) $logAnalysis['late_min'];
        $earlyOutMin = (int) $logAnalysis['early_out_min'];
        $overtimeMin = (int) $logAnalysis['overtime_min'];

        $hasActualAttendance = $logAnalysis['has_actual_attendance'];
        $alreadyHasDailyResult = false;

        if (! $hasActualAttendance && ! $calendar['is_workday']) {
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
        } elseif ($exception['has_status_override']) {
            $attendanceStatusCode = $exception['status_value_code'];
            $presenceTypeCode = $this->resolvePresenceTypeForForcedStatus($attendanceStatusCode);
            $notes[] = 'Status override applied from attendance exception.';
        } elseif ($hasActualAttendance) {
            $attendanceStatusCode = $logAnalysis['attendance_status_code'];
            $presenceTypeCode = $logAnalysis['presence_type_code'];
            $notes = array_merge($notes, $logAnalysis['notes']);
        } else {
            $shouldGenerateAbsent = $this->attendanceAbsenceGeneratorService->shouldGenerateAbsent(
                isWorkday: (bool) $calendar['is_workday'],
                hasActualAttendance: false,
                hasApprovedLeave: (bool) $leave['has_approved_leave'],
                hasApprovedException: (bool) $exception['has_exception'],
                alreadyHasDailyResult: $alreadyHasDailyResult,
            );

            if ($shouldGenerateAbsent) {
                $attendanceStatusCode = 'ABSENT';
                $presenceTypeCode = 'NO_SHOW';
                $notes[] = 'Workday without attendance/leave/exception coverage.';
            } else {
                $attendanceStatusCode = $this->attendanceAbsenceGeneratorService->resolveStatusForNoAttendance(
                    isWorkday: (bool) $calendar['is_workday'],
                    dayTypeCode: $calendar['day_type_code']
                );
                $presenceTypeCode = null;
                $notes[] = 'Resolved as non-attendance status.';
            }
        }

        if ($exception['has_minutes_override']) {
            $notes[] = 'Minutes override detected but not yet fully interpreted.';
        }

        if ($exception['has_time_override']) {
            $notes[] = 'Time override detected but not yet fully interpreted.';
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

        $reviewReasonCode = null;
        if ($attendanceStatusCode === 'MANUAL_REVIEW') {
            $reviewReasonCode = $this->detectReviewReason(
                hasIn: $actualInDatetime !== null,
                hasOut: $actualOutDatetime !== null,
                hasBoth: $actualInDatetime !== null && $actualOutDatetime !== null,
                hasAnomaly: $anomalyFlag,
                workMin: $workMin
            );
        }

        $attendanceScore = $this->calculateAttendanceScore(
            attendanceStatusCode: $attendanceStatusCode,
            presenceTypeCode: $presenceTypeCode ?? 'PARTIAL',
            lateMin: $lateMin,
            earlyOutMin: $earlyOutMin,
            reviewReasonCode: $reviewReasonCode
        );

        $lateSeverityCode = $this->getLateSeverity($lateMin);

        $details[] = $this->makeDetail(
            'STATUS_DECIDED',
            sprintf(
                'Final status=%s, presence_type=%s',
                $attendanceStatusCode,
                $presenceTypeCode ?? 'NULL'
            ),
            'SYSTEM',
            null,
            implode(' ', $notes)
        );

        $now = now();

        return [
            'daily' => [
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
                'attendance_status_code' => $attendanceStatusCode,
                'presence_type_code' => $presenceTypeCode,
                'anomaly_flag' => $anomalyFlag,
                'exception_flag' => $exceptionFlag,
                'leave_flag' => $leaveFlag,
                'attendance_score' => $attendanceScore,
                'late_severity_code' => $lateSeverityCode,
                'pattern_flag' => false,
                'review_reason_code' => $reviewReasonCode,
                'calculation_version' => 8,
                'calculated_at' => $now,
                'notes' => implode(' ', $notes),
                'updated_at' => $now,
                'created_at' => $now,
            ],
            'details' => $details,
        ];
    }

    private function persistDailyResult(array $dailyRow, array $details): void
    {
        DB::transaction(function () use ($dailyRow, $details): void {
            $existing = DB::table('attendance_daily')
                ->where('emp_id', $dailyRow['emp_id'])
                ->whereDate('work_date', $dailyRow['work_date'])
                ->first();

            if ($existing) {
                DB::table('attendance_daily')
                    ->where('attendance_daily_id', $existing->attendance_daily_id)
                    ->update([
                        'branch_id' => $dailyRow['branch_id'],
                        'policy_id' => $dailyRow['policy_id'],
                        'shift_id' => $dailyRow['shift_id'],
                        'scheduled_in_datetime' => $dailyRow['scheduled_in_datetime'],
                        'scheduled_out_datetime' => $dailyRow['scheduled_out_datetime'],
                        'actual_in_datetime' => $dailyRow['actual_in_datetime'],
                        'actual_out_datetime' => $dailyRow['actual_out_datetime'],
                        'break_min' => $dailyRow['break_min'],
                        'work_min' => $dailyRow['work_min'],
                        'late_min' => $dailyRow['late_min'],
                        'early_out_min' => $dailyRow['early_out_min'],
                        'overtime_min' => $dailyRow['overtime_min'],
                        'attendance_status_code' => $dailyRow['attendance_status_code'],
                        'presence_type_code' => $dailyRow['presence_type_code'],
                        'anomaly_flag' => $dailyRow['anomaly_flag'],
                        'exception_flag' => $dailyRow['exception_flag'],
                        'leave_flag' => $dailyRow['leave_flag'],
                        'attendance_score' => $dailyRow['attendance_score'],
                        'late_severity_code' => $dailyRow['late_severity_code'],
                        'pattern_flag' => $dailyRow['pattern_flag'],
                        'review_reason_code' => $dailyRow['review_reason_code'],
                        'calculation_version' => $dailyRow['calculation_version'],
                        'calculated_at' => $dailyRow['calculated_at'],
                        'notes' => $dailyRow['notes'],
                        'updated_at' => $dailyRow['updated_at'],
                    ]);

                $attendanceDailyId = (int) $existing->attendance_daily_id;

                DB::table('attendance_daily_details')
                    ->where('attendance_daily_id', $attendanceDailyId)
                    ->delete();
            } else {
                $attendanceDailyId = DB::table('attendance_daily')->insertGetId($dailyRow, 'attendance_daily_id');
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

            if (! empty($detailRows)) {
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

        if (! $period) {
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
                'source_ref_id' => (string) $shiftAssignment->employee_shift_assignment_id,
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

            if (! empty($shift->start_time)) {
                $scheduledInDatetime = Carbon::parse($workDate . ' ' . $shift->start_time, 'Asia/Jakarta');
            }

            if (! empty($shift->end_time)) {
                $scheduledOutDatetime = Carbon::parse($workDate . ' ' . $shift->end_time, 'Asia/Jakarta');

                if (! empty($shift->cross_day_flag) && $shift->cross_day_flag) {
                    $scheduledOutDatetime = $scheduledOutDatetime->copy()->addDay();
                }
            }

            if (! empty($shift->default_work_min)) {
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
        $lateGraceInMin = (int) ($policy->late_grace_in_min ?? 0);
        $earlyOutGraceMin = (int) ($policy->early_out_grace_min ?? 0);
        $minWorkMinHalfDay = (int) ($policy->min_work_min_half_day ?? 0);
        $minWorkMinFullDay = (int) ($policy->min_work_min_full_day ?? 0);
        $overtimeMinBefore = (int) ($policy->overtime_min_before ?? 0);
        $missingOutPolicyCode = strtoupper((string) ($policy->missing_out_policy_code ?? ''));
        $missingInPolicyCode = strtoupper((string) ($policy->missing_in_policy_code ?? ''));

        $validIns = $logs->filter(function ($row) {
            return $row->derived_event_type_code === 'IN'
                && $row->normalized_status_code === 'VALID';
        });

        $validOuts = $logs->filter(function ($row) {
            return $row->derived_event_type_code === 'OUT'
                && $row->normalized_status_code === 'VALID';
        });

        $suspiciousRows = $logs->filter(function ($row) {
            return $row->normalized_status_code === 'SUSPICIOUS';
        });

        $actualInDatetime = null;
        $actualOutDatetime = null;
        $attendanceStatusCode = null;
        $presenceTypeCode = null;
        $anomalyFlag = false;
        $notes = [];

        if ($validIns->isNotEmpty() && $validOuts->isNotEmpty()) {
            $actualInDatetime = $validIns->sortBy('log_datetime')->first()->log_datetime;
            $actualOutDatetime = $validOuts->sortByDesc('log_datetime')->first()->log_datetime;
            $attendanceStatusCode = 'PRESENT';
            $presenceTypeCode = 'FULL_DAY';
            $notes[] = 'Built from valid IN and OUT normalized events.';
        } elseif ($validIns->isNotEmpty()) {
            $actualInDatetime = $validIns->sortBy('log_datetime')->first()->log_datetime;
            $presenceTypeCode = 'PARTIAL';
            $anomalyFlag = true;

            if ($missingOutPolicyCode === 'REVIEW') {
                $attendanceStatusCode = 'MANUAL_REVIEW';
                $notes[] = 'Only valid IN found. Missing OUT policy = REVIEW.';
            } else {
                $attendanceStatusCode = 'INCOMPLETE';
                $notes[] = 'Only valid IN found.';
            }
        } elseif ($validOuts->isNotEmpty()) {
            $actualOutDatetime = $validOuts->sortByDesc('log_datetime')->first()->log_datetime;
            $presenceTypeCode = 'PARTIAL';
            $anomalyFlag = true;

            if ($missingInPolicyCode === 'REVIEW') {
                $attendanceStatusCode = 'MANUAL_REVIEW';
                $notes[] = 'Only valid OUT found. Missing IN policy = REVIEW.';
            } else {
                $attendanceStatusCode = 'INCOMPLETE';
                $notes[] = 'Only valid OUT found.';
            }
        } elseif ($suspiciousRows->count() === 1) {
            $singleTap = $suspiciousRows->first();
            $singleTapDt = Carbon::parse($singleTap->log_datetime)->setTimezone('Asia/Jakarta');
            $hour = $singleTapDt->hour;

            $notes[] = 'Infer hour=' . $hour;
            $presenceTypeCode = 'PARTIAL';
            $anomalyFlag = true;

            if ($hour < 12) {
                $actualInDatetime = $singleTap->log_datetime;

                if ($missingOutPolicyCode === 'REVIEW') {
                    $attendanceStatusCode = 'MANUAL_REVIEW';
                    $notes[] = 'Single suspicious tap inferred as IN based on time. Missing OUT policy = REVIEW.';
                } else {
                    $attendanceStatusCode = 'INCOMPLETE';
                    $notes[] = 'Single suspicious tap inferred as IN based on time.';
                }
            } else {
                $actualOutDatetime = $singleTap->log_datetime;

                if ($missingInPolicyCode === 'REVIEW') {
                    $attendanceStatusCode = 'MANUAL_REVIEW';
                    $notes[] = 'Single suspicious tap inferred as OUT based on time. Missing IN policy = REVIEW.';
                } else {
                    $attendanceStatusCode = 'INCOMPLETE';
                    $notes[] = 'Single suspicious tap inferred as OUT based on time.';
                }
            }
        } else {
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
        $scheduledIn = $schedule['scheduled_in_datetime'] ? Carbon::parse($schedule['scheduled_in_datetime'])->setTimezone('Asia/Jakarta') : null;
        $scheduledOut = $schedule['scheduled_out_datetime'] ? Carbon::parse($schedule['scheduled_out_datetime'])->setTimezone('Asia/Jakarta') : null;
        $breakMin = (int) $schedule['break_min'];

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

        if (
            $attendanceStatusCode === 'PRESENT' &&
            $actualIn &&
            $actualOut
        ) {
            if ($minWorkMinFullDay > 0 && $workMin >= $minWorkMinFullDay) {
                $presenceTypeCode = 'FULL_DAY';
            } elseif ($minWorkMinHalfDay > 0 && $workMin >= $minWorkMinHalfDay) {
                $presenceTypeCode = 'HALF_DAY';
            } else {
                $presenceTypeCode = 'PARTIAL';
                $notes[] = 'Work minutes below half-day threshold.';
            }
        }

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

        if ($presenceTypeCode === 'PARTIAL' && $anomalyFlag) {
            $attendanceStatusCode = 'MANUAL_REVIEW';
            $overtimeMin = 0;
            $notes[] = 'Final refinement: PARTIAL with anomaly flagged for manual review.';
        }

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

    private function resolvePresenceTypeForForcedStatus(?string $attendanceStatusCode): ?string
    {
        return match ($attendanceStatusCode) {
            'ABSENT' => 'NO_SHOW',
            'PRESENT' => 'FULL_DAY',
            default => null,
        };
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
        int $workMin
    ): ?string {
        if ($hasBoth && $hasAnomaly) {
            return 'INVALID_PAIR';
        }

        if ($hasIn && ! $hasOut) {
            return 'MISSING_OUT';
        }

        if (! $hasIn && $hasOut) {
            return 'MISSING_IN';
        }

        if (! $hasIn && ! $hasOut) {
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
}