<?php

namespace App\Domains\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceDailyBuilderService
{
    public function build(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = DB::table('attendance_logs_normalized')
            ->orderBy('emp_id')
            ->orderBy('log_datetime')
            ->orderBy('log_id');

        if ($dateFrom) {
            $query->whereDate('log_datetime', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('log_datetime', '<=', $dateTo);
        }

        $normalizedRows = $query->get();

        $grouped = $normalizedRows->groupBy(function ($row) {
            return $row->emp_id . '|' . substr((string) $row->log_datetime, 0, 10);
        });

        $insertRows = [];
        $processedGroups = 0;
        $insertedCount = 0;
        $skippedGroups = 0;
        $affectedEmpIds = [];

        foreach ($grouped as $groupKey => $rows) {
            $rows = $rows->sortBy('log_datetime')->values();

            $firstRow = $rows->first();
            if (!$firstRow) {
                $skippedGroups++;
                continue;
            }

            $empId = $firstRow->emp_id;
            $workDate = substr((string) $firstRow->log_datetime, 0, 10);

            $alreadyExists = DB::table('attendance_daily')
                ->where('emp_id', $empId)
                ->whereDate('work_date', $workDate)
                ->exists();

            if ($alreadyExists) {
                $skippedGroups++;
                continue;
            }

            $assignment = DB::table('employee_assignments')
                ->where('emp_id', $empId)
                ->whereDate('effective_start_date', '<=', $workDate)
                ->where(function ($q) use ($workDate) {
                    $q->whereNull('effective_end_date')
                        ->orWhereDate('effective_end_date', '>=', $workDate);
                })
                ->orderByDesc('is_primary')
                ->orderByDesc('effective_start_date')
                ->first();

            if (!$assignment || !$assignment->branch_id) {
                $skippedGroups++;
                continue;
            }

            $branchId = $assignment->branch_id;

            $branchPolicy = DB::table('branch_policy_assignments')
                ->where('branch_id', $branchId)
                ->whereDate('effective_start_date', '<=', $workDate)
                ->where(function ($q) use ($workDate) {
                    $q->whereNull('effective_end_date')
                        ->orWhereDate('effective_end_date', '>=', $workDate);
                })
                ->orderByDesc('effective_start_date')
                ->first();

            if (!$branchPolicy || !$branchPolicy->policy_id) {
                $skippedGroups++;
                continue;
            }

            $policyId = $branchPolicy->policy_id;

            $policy = DB::table('attendance_policies')
                ->where('policy_id', $policyId)
                ->first();

            if (!$policy) {
                $skippedGroups++;
                continue;
            }

            $lateGraceInMin = (int) ($policy->late_grace_in_min ?? 0);
            $earlyOutGraceMin = (int) ($policy->early_out_grace_min ?? 0);
            $minWorkMinHalfDay = (int) ($policy->min_work_min_half_day ?? 0);
            $minWorkMinFullDay = (int) ($policy->min_work_min_full_day ?? 0);
            $overtimeMinBefore = (int) ($policy->overtime_min_before ?? 0);
            $missingOutPolicyCode = strtoupper((string) ($policy->missing_out_policy_code ?? ''));
            $missingInPolicyCode = strtoupper((string) ($policy->missing_in_policy_code ?? ''));

            $shiftId = null;

            $roster = DB::table('employee_shift_rosters')
                ->where('emp_id', $empId)
                ->whereDate('work_date', $workDate)
                ->first();

            if ($roster && $roster->shift_id) {
                $shiftId = $roster->shift_id;
            } else {
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
                    $shiftId = $shiftAssignment->shift_id;
                }
            }

            $scheduledInDatetime = null;
            $scheduledOutDatetime = null;
            $breakMin = 0;
            $expectedWorkMin = 480;

            if ($shiftId) {
                $shift = DB::table('shifts')->where('shift_id', $shiftId)->first();

                if ($shift) {
                    $breakMin = (int) ($shift->break_min ?? 0);

                    if (!empty($shift->start_time)) {
                        $scheduledInDatetime = Carbon::parse(
                            $workDate . ' ' . $shift->start_time,
                            'Asia/Jakarta'
                        );
                    }

                    if (!empty($shift->end_time)) {
                        $scheduledOutDatetime = Carbon::parse(
                            $workDate . ' ' . $shift->end_time,
                            'Asia/Jakarta'
                        );

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
            }

            if ($expectedWorkMin <= 0 && $minWorkMinFullDay > 0) {
                $expectedWorkMin = $minWorkMinFullDay;
            }

            $validIns = $rows->filter(function ($row) {
                return $row->derived_event_type_code === 'IN'
                    && $row->normalized_status_code === 'VALID';
            });

            $validOuts = $rows->filter(function ($row) {
                return $row->derived_event_type_code === 'OUT'
                    && $row->normalized_status_code === 'VALID';
            });

            $suspiciousRows = $rows->filter(function ($row) {
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
                $attendanceStatusCode = 'MANUAL_REVIEW';
                $presenceTypeCode = 'PARTIAL';
                $anomalyFlag = true;
                $notes[] = 'Could not determine reliable IN/OUT pair.';
            }

            $actualIn = $actualInDatetime ? Carbon::parse($actualInDatetime)->setTimezone('Asia/Jakarta') : null;
            $actualOut = $actualOutDatetime ? Carbon::parse($actualOutDatetime)->setTimezone('Asia/Jakarta') : null;
            $scheduledIn = $scheduledInDatetime ? Carbon::parse($scheduledInDatetime)->setTimezone('Asia/Jakarta') : null;
            $scheduledOut = $scheduledOutDatetime ? Carbon::parse($scheduledOutDatetime)->setTimezone('Asia/Jakarta') : null;

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

            $hasIn = $actualIn !== null;
            $hasOut = $actualOut !== null;
            $hasBoth = $hasIn && $hasOut;

            $reviewReasonCode = null;

            if ($attendanceStatusCode === 'MANUAL_REVIEW') {
                $reviewReasonCode = $this->detectReviewReason(
                    $hasIn,
                    $hasOut,
                    $hasBoth,
                    $anomalyFlag,
                    $workMin
                );
            }

            $attendanceScore = $this->calculateAttendanceScore(
                $attendanceStatusCode,
                $presenceTypeCode,
                $lateMin,
                $earlyOutMin,
                $reviewReasonCode
            );

            $lateSeverityCode = $this->getLateSeverity($lateMin);

            $now = now();

            $insertRows[] = [
                'emp_id' => $empId,
                'work_date' => $workDate,
                'branch_id' => $branchId,
                'policy_id' => $policyId,
                'shift_id' => $shiftId,
                'scheduled_in_datetime' => $scheduledInDatetime,
                'scheduled_out_datetime' => $scheduledOutDatetime,
                'actual_in_datetime' => $actualInDatetime,
                'actual_out_datetime' => $actualOutDatetime,
                'break_min' => $breakMin,
                'work_min' => $workMin,
                'late_min' => $lateMin,
                'early_out_min' => $earlyOutMin,
                'overtime_min' => $overtimeMin,
                'attendance_status_code' => $attendanceStatusCode,
                'presence_type_code' => $presenceTypeCode,
                'anomaly_flag' => $anomalyFlag,
                'exception_flag' => false,
                'leave_flag' => false,
                'attendance_score' => $attendanceScore,
                'late_severity_code' => $lateSeverityCode,
                'pattern_flag' => false,
                'review_reason_code' => $reviewReasonCode,
                'calculation_version' => 7,
                'calculated_at' => $now,
                'notes' => implode(' ', $notes),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $affectedEmpIds[$empId] = true;
            $processedGroups++;
        }

        if (!empty($insertRows)) {
            DB::table('attendance_daily')->insert($insertRows);
            $insertedCount = count($insertRows);

            $this->applyPatternDetection(array_keys($affectedEmpIds));
        }

        return [
            'group_count' => $processedGroups,
            'inserted_count' => $insertedCount,
            'skipped_groups' => $skippedGroups,
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
        } elseif ($attendanceStatusCode === 'PRESENT' && $presenceTypeCode === 'PARTIAL') {
            $score = 70;
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

        if ($score < 0) {
            $score = 0;
        }

        if ($score > 100) {
            $score = 100;
        }

        return $score;
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
}