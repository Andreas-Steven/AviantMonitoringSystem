<?php

namespace App\Domains\Attendance\Services;

use App\Domains\Attendance\DTOs\ExceptionApplyResult;
use App\Domains\Review\Models\AttendanceException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExceptionApplierService
{
    /**
     * @param  array<string, mixed>  $dailyDraft
     * @param  iterable<AttendanceException>  $exceptions
     * @param  array<string, mixed>  $context
     */
    public function apply(array $dailyDraft, iterable $exceptions, array $context = []): ExceptionApplyResult
    {
        $detailLogs = [];
        $appliedExceptions = [];

        $sortedExceptions = $this->sortExceptions($exceptions);

        foreach ($sortedExceptions as $exception) {
            [$dailyDraft, $applied, $detailLog] = $this->applySingle($dailyDraft, $exception, $context);

            if ($applied) {
                $dailyDraft['exception_flag'] = true;
                $appliedExceptions[] = $exception;

                if ($detailLog !== null) {
                    $detailLogs[] = $detailLog;
                }
            }
        }

        return new ExceptionApplyResult(
            dailyDraft: $this->normalizeFinalDraft($dailyDraft),
            appliedExceptions: $appliedExceptions,
            detailLogs: $detailLogs,
        );
    }

    /**
     * @param  iterable<AttendanceException>  $exceptions
     * @return array<int, AttendanceException>
     */
    protected function sortExceptions(iterable $exceptions): array
    {
        $items = $exceptions instanceof Collection
            ? $exceptions->all()
            : (is_array($exceptions) ? $exceptions : iterator_to_array($exceptions));

        usort($items, function (AttendanceException $a, AttendanceException $b): int {
            $priorityCompare = $this->priorityOf($a->exception_type_code) <=> $this->priorityOf($b->exception_type_code);

            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            return $a->attendance_exception_id <=> $b->attendance_exception_id;
        });

        return $items;
    }

    protected function priorityOf(string $exceptionTypeCode): int
    {
        return match ($exceptionTypeCode) {
            'SHIFT_OVERRIDE' => 10,
            'MANUAL_IN' => 20,
            'MANUAL_OUT' => 30,
            'LATE_DISPENSATION' => 40,
            'EARLY_OUT_DISPENSATION' => 50,
            'OVERTIME_OVERRIDE' => 60,
            'FORCE_PRESENT' => 70,
            'FORCE_ABSENT' => 80,
            'FORGOT_CHECKIN_APPROVAL' => 90,
            'FORGOT_CHECKOUT_APPROVAL' => 100,
            default => 999,
        };
    }

    /**
     * @param  array<string, mixed>  $draft
     * @param  array<string, mixed>  $context
     * @return array{0: array<string, mixed>, 1: bool, 2: array<string, mixed>|null}
     */
    protected function applySingle(array $draft, AttendanceException $exception, array $context): array
    {
        return match ($exception->exception_type_code) {
            'SHIFT_OVERRIDE' => $this->applyShiftOverride($draft, $exception, $context),
            'MANUAL_IN' => $this->applyManualIn($draft, $exception, $context),
            'MANUAL_OUT' => $this->applyManualOut($draft, $exception, $context),

            'LATE_DISPENSATION' => $this->applyLateDispensation($draft, $exception),
            'EARLY_OUT_DISPENSATION' => $this->applyEarlyOutDispensation($draft, $exception),
            'OVERTIME_OVERRIDE' => $this->applyOvertimeOverride($draft, $exception),
            'FORCE_PRESENT' => $this->applyForcePresent($draft, $exception),
            'FORCE_ABSENT' => $this->applyForceAbsent($draft, $exception),

            'FORGOT_CHECKIN_APPROVAL' => $this->applyForgotCheckinApprovalMarker($draft, $exception),
            'FORGOT_CHECKOUT_APPROVAL' => $this->applyForgotCheckoutApprovalMarker($draft, $exception),

            default => [$draft, false, null],
        };
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{0: array<string, mixed>, 1: bool, 2: array<string, mixed>}
     */
    protected function applyLateDispensation(array $draft, AttendanceException $exception): array
    {
        $currentStatus = (string) ($draft['attendance_status_code'] ?? '');

        // Late dispensation hanya valid untuk attendance yang sudah PRESENT.
        // Tidak boleh membuat ABSENT/OFF/LEAVE/null menjadi PRESENT.
        if ($currentStatus !== 'PRESENT') {
            return [
                $draft,
                false,
                null,
            ];
        }

        $originalLateMin = (int) ($draft['late_min'] ?? 0);

        // Kalau tidak ada late, tidak perlu apply apa pun.
        if ($originalLateMin <= 0) {
            return [
                $draft,
                false,
                null,
            ];
        }

        $dispensationMin = max(0, (int) ($exception->minutes_value ?? 0));
        $newLateMin = max(0, $originalLateMin - $dispensationMin);

        $draft['late_min'] = $newLateMin;
        $draft['notes'] = $this->appendNote(
            $draft['notes'] ?? null,
            sprintf('Late dispensation applied: -%d minute(s).', $dispensationMin)
        );

        return [
            $draft,
            true,
            $this->makeDetailLog(
                stepName: 'EXCEPTION_LATE_DISPENSATION',
                stepResult: 'Late dispensation applied.',
                exception: $exception,
                notes: sprintf('late_min changed from %d to %d.', $originalLateMin, $newLateMin),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{0: array<string, mixed>, 1: bool, 2: array<string, mixed>}
     */
    protected function applyEarlyOutDispensation(array $draft, AttendanceException $exception): array
    {
        $currentStatus = (string) ($draft['attendance_status_code'] ?? '');

        // Early out dispensation hanya valid untuk attendance yang sudah PRESENT.
        if ($currentStatus !== 'PRESENT') {
            return [
                $draft,
                false,
                null,
            ];
        }

        $originalEarlyOutMin = (int) ($draft['early_out_min'] ?? 0);

        if ($originalEarlyOutMin <= 0) {
            return [
                $draft,
                false,
                null,
            ];
        }

        $dispensationMin = max(0, (int) ($exception->minutes_value ?? 0));
        $newEarlyOutMin = max(0, $originalEarlyOutMin - $dispensationMin);

        $draft['early_out_min'] = $newEarlyOutMin;
        $draft['notes'] = $this->appendNote(
            $draft['notes'] ?? null,
            sprintf('Early out dispensation applied: -%d minute(s).', $dispensationMin)
        );

        return [
            $draft,
            true,
            $this->makeDetailLog(
                stepName: 'EXCEPTION_EARLY_OUT_DISPENSATION',
                stepResult: 'Early out dispensation applied.',
                exception: $exception,
                notes: sprintf('early_out_min changed from %d to %d.', $originalEarlyOutMin, $newEarlyOutMin),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{0: array<string, mixed>, 1: bool, 2: array<string, mixed>}
     */
    protected function applyOvertimeOverride(array $draft, AttendanceException $exception): array
    {
        $originalOvertimeMin = (int) ($draft['overtime_min'] ?? 0);
        $overrideMin = max(0, (int) ($exception->minutes_value ?? 0));

        $draft['overtime_min'] = $overrideMin;
        $draft['notes'] = $this->appendNote(
            $draft['notes'] ?? null,
            sprintf('Overtime override applied: set to %d minute(s).', $overrideMin)
        );

        return [
            $draft,
            true,
            $this->makeDetailLog(
                stepName: 'EXCEPTION_OVERTIME_OVERRIDE',
                stepResult: 'Overtime override applied.',
                exception: $exception,
                notes: sprintf('overtime_min changed from %d to %d.', $originalOvertimeMin, $overrideMin),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{0: array<string, mixed>, 1: bool, 2: array<string, mixed>}
     */
    protected function applyForcePresent(array $draft, AttendanceException $exception): array
    {
        $originalStatus = (string) ($draft['attendance_status_code'] ?? '');
        $originalPresenceType = $draft['presence_type_code'] ?? null;

        $draft['attendance_status_code'] = 'PRESENT';
        $draft['presence_type_code'] = $originalPresenceType ?: 'FULL_DAY';
        $draft['notes'] = $this->appendNote($draft['notes'] ?? null, 'Force present exception applied.');

        return [
            $draft,
            true,
            $this->makeDetailLog(
                stepName: 'EXCEPTION_FORCE_PRESENT',
                stepResult: 'Attendance status forced to PRESENT.',
                exception: $exception,
                notes: sprintf(
                    'attendance_status_code changed from %s to PRESENT.',
                    $originalStatus !== '' ? $originalStatus : '[empty]'
                ),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{0: array<string, mixed>, 1: bool, 2: array<string, mixed>}
     */
    protected function applyForceAbsent(array $draft, AttendanceException $exception): array
    {
        $originalStatus = (string) ($draft['attendance_status_code'] ?? '');

        $draft['attendance_status_code'] = 'ABSENT';
        $draft['presence_type_code'] = 'NO_SHOW';
        $draft['work_min'] = 0;
        $draft['late_min'] = 0;
        $draft['early_out_min'] = 0;
        $draft['overtime_min'] = 0;
        $draft['notes'] = $this->appendNote($draft['notes'] ?? null, 'Force absent exception applied.');

        return [
            $draft,
            true,
            $this->makeDetailLog(
                stepName: 'EXCEPTION_FORCE_ABSENT',
                stepResult: 'Attendance status forced to ABSENT.',
                exception: $exception,
                notes: sprintf(
                    'attendance_status_code changed from %s to ABSENT; derived metrics reset to zero.',
                    $originalStatus !== '' ? $originalStatus : '[empty]'
                ),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @param  array<string, mixed>  $context
     * @return array{0: array<string, mixed>, 1: bool, 2: array<string, mixed>|null}
     */
    protected function applyShiftOverride(array $draft, AttendanceException $exception, array $context): array
    {
        if (!$exception->shift_id_value) {
            return [$draft, false, null];
        }

        $shift = DB::table('shifts')
            ->where('shift_id', $exception->shift_id_value)
            ->first();

        if (!$shift) {
            return [$draft, false, null];
        }

        $originalShiftId = $draft['shift_id'] ?? null;

        $draft['shift_id'] = (int) $exception->shift_id_value;

        $workDate = (string) ($draft['work_date'] ?? ($context['work_date'] ?? ''));
        $policy = $context['policy'] ?? null;
        $calendarIsWorkday = (bool) ($context['calendar_is_workday'] ?? true);

        if ($policy) {
            $schedule = $this->buildScheduleFromShift(
                workDate: $workDate,
                shift: $shift,
                policy: $policy,
            );

            $draft['scheduled_in_datetime'] = $schedule['scheduled_in_datetime'];
            $draft['scheduled_out_datetime'] = $schedule['scheduled_out_datetime'];
            $draft['break_min'] = $schedule['break_min'];

            $draft = $this->recalculateDerivedMetrics(
                draft: $draft,
                policy: $policy,
                schedule: $schedule,
                calendarIsWorkday: $calendarIsWorkday
            );
        }

        $draft['notes'] = $this->appendNote(
            $draft['notes'] ?? null,
            sprintf(
                'Shift override applied: %s -> %s.',
                $originalShiftId !== null ? (string) $originalShiftId : '[null]',
                (string) $exception->shift_id_value
            )
        );

        return [
            $draft,
            true,
            $this->makeDetailLog(
                stepName: 'EXCEPTION_SHIFT_OVERRIDE',
                stepResult: 'Shift override applied with recalculation.',
                exception: $exception,
                notes: sprintf(
                    'shift_id changed from %s to %s; schedule and derived metrics recalculated.',
                    $originalShiftId !== null ? (string) $originalShiftId : '[null]',
                    (string) $exception->shift_id_value
                ),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @param  array<string, mixed>  $context
     * @return array{0: array<string, mixed>, 1: bool, 2: array<string, mixed>|null}
     */
    protected function applyManualIn(array $draft, AttendanceException $exception, array $context): array
    {
        if (!$exception->time_value instanceof CarbonInterface) {
            return [$draft, false, null];
        }

        $original = $draft['actual_in_datetime'] ?? null;
        $draft['actual_in_datetime'] = $exception->time_value->copy()->setTimezone('Asia/Jakarta');

        $policy = $context['policy'] ?? null;
        $calendarIsWorkday = (bool) ($context['calendar_is_workday'] ?? true);
        $schedule = $this->scheduleFromDraft($draft);

        if ($policy) {
            $draft = $this->recalculateDerivedMetrics(
                draft: $draft,
                policy: $policy,
                schedule: $schedule,
                calendarIsWorkday: $calendarIsWorkday
            );
        }

        $draft['notes'] = $this->appendNote(
            $draft['notes'] ?? null,
            'Manual IN applied with recalculation.'
        );

        return [
            $draft,
            true,
            $this->makeDetailLog(
                stepName: 'EXCEPTION_MANUAL_IN',
                stepResult: 'Manual IN applied with recalculation.',
                exception: $exception,
                notes: sprintf(
                    'actual_in_datetime changed from %s to %s.',
                    $this->stringifyDateTime($original),
                    $exception->time_value->copy()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                ),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @param  array<string, mixed>  $context
     * @return array{0: array<string, mixed>, 1: bool, 2: array<string, mixed>|null}
     */
    protected function applyManualOut(array $draft, AttendanceException $exception, array $context): array
    {
        if (!$exception->time_value instanceof CarbonInterface) {
            return [$draft, false, null];
        }

        $original = $draft['actual_out_datetime'] ?? null;
        $draft['actual_out_datetime'] = $exception->time_value->copy()->setTimezone('Asia/Jakarta');

        $policy = $context['policy'] ?? null;
        $calendarIsWorkday = (bool) ($context['calendar_is_workday'] ?? true);
        $schedule = $this->scheduleFromDraft($draft);

        if ($policy) {
            $draft = $this->recalculateDerivedMetrics(
                draft: $draft,
                policy: $policy,
                schedule: $schedule,
                calendarIsWorkday: $calendarIsWorkday
            );
        }

        $draft['notes'] = $this->appendNote(
            $draft['notes'] ?? null,
            'Manual OUT applied with recalculation.'
        );

        return [
            $draft,
            true,
            $this->makeDetailLog(
                stepName: 'EXCEPTION_MANUAL_OUT',
                stepResult: 'Manual OUT applied with recalculation.',
                exception: $exception,
                notes: sprintf(
                    'actual_out_datetime changed from %s to %s.',
                    $this->stringifyDateTime($original),
                    $exception->time_value->copy()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                ),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{0: array<string, mixed>, 1: bool, 2: array<string, mixed>|null}
     */
    protected function applyForgotCheckinApprovalMarker(array $draft, AttendanceException $exception): array
    {
        $hasIn = !empty($draft['actual_in_datetime']);
        $hasOut = !empty($draft['actual_out_datetime']);

        if ($hasIn || !$hasOut) {
            return [$draft, false, null];
        }

        $originalStatus = (string) ($draft['attendance_status_code'] ?? '');
        $originalPresenceType = (string) ($draft['presence_type_code'] ?? '');

        $draft['attendance_status_code'] = 'PRESENT';
        $draft['presence_type_code'] = 'PARTIAL';
        $draft['late_min'] = 0;
        $draft['work_min'] = 0;
        $draft['overtime_min'] = 0;
        $draft['anomaly_flag'] = false;
        $draft['review_reason_code'] = 'FORGOT_CHECKIN_APPROVED';
        $draft['notes'] = $this->appendNote(
            $draft['notes'] ?? null,
            'Forgot check-in approval applied: status resolved without synthetic check-in time.'
        );

        return [
            $draft,
            true,
            $this->makeDetailLog(
                stepName: 'EXCEPTION_FORGOT_CHECKIN_APPROVAL',
                stepResult: 'Forgot check-in approval applied.',
                exception: $exception,
                notes: sprintf(
                    'Resolved missing IN with approval. Status %s -> PRESENT, presence %s -> PARTIAL, work/late/overtime normalized to zero without synthetic IN timestamp.',
                    $originalStatus !== '' ? $originalStatus : '[empty]',
                    $originalPresenceType !== '' ? $originalPresenceType : '[empty]'
                ),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{0: array<string, mixed>, 1: bool, 2: array<string, mixed>|null}
     */
    protected function applyForgotCheckoutApprovalMarker(array $draft, AttendanceException $exception): array
    {
        $hasIn = !empty($draft['actual_in_datetime']);
        $hasOut = !empty($draft['actual_out_datetime']);

        if (!$hasIn || $hasOut) {
            return [$draft, false, null];
        }

        $originalStatus = (string) ($draft['attendance_status_code'] ?? '');
        $originalPresenceType = (string) ($draft['presence_type_code'] ?? '');

        $draft['attendance_status_code'] = 'PRESENT';
        $draft['presence_type_code'] = 'PARTIAL';
        $draft['early_out_min'] = 0;
        $draft['work_min'] = 0;
        $draft['overtime_min'] = 0;
        $draft['anomaly_flag'] = false;
        $draft['review_reason_code'] = 'FORGOT_CHECKOUT_APPROVED';
        $draft['notes'] = $this->appendNote(
            $draft['notes'] ?? null,
            'Forgot check-out approval applied: status resolved without synthetic check-out time.'
        );

        return [
            $draft,
            true,
            $this->makeDetailLog(
                stepName: 'EXCEPTION_FORGOT_CHECKOUT_APPROVAL',
                stepResult: 'Forgot check-out approval applied.',
                exception: $exception,
                notes: sprintf(
                    'Resolved missing OUT with approval. Status %s -> PRESENT, presence %s -> PARTIAL, work/early_out/overtime normalized to zero without synthetic OUT timestamp.',
                    $originalStatus !== '' ? $originalStatus : '[empty]',
                    $originalPresenceType !== '' ? $originalPresenceType : '[empty]'
                ),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @param  object  $policy
     * @param  array<string, mixed>  $schedule
     * @return array<string, mixed>
     */
    protected function recalculateDerivedMetrics(
        array $draft,
        object $policy,
        array $schedule,
        bool $calendarIsWorkday = true
    ): array {
        $actualIn = $this->toCarbon($draft['actual_in_datetime'] ?? null);
        $actualOut = $this->toCarbon($draft['actual_out_datetime'] ?? null);
        $scheduledIn = $this->toCarbon($schedule['scheduled_in_datetime'] ?? null);
        $scheduledOut = $this->toCarbon($schedule['scheduled_out_datetime'] ?? null);

        $breakMin = (int) ($schedule['break_min'] ?? 0);
        $lateGraceInMin = (int) ($policy->late_grace_in_min ?? 0);
        $earlyOutGraceMin = (int) ($policy->early_out_grace_min ?? 0);
        $overtimeMinBefore = (int) ($policy->overtime_min_before ?? 0);
        $minWorkMinHalfDay = (int) ($policy->min_work_min_half_day ?? 0);
        $minWorkMinFullDay = (int) ($policy->min_work_min_full_day ?? 0);

        $workMin = 0;
        $lateMin = 0;
        $earlyOutMin = 0;
        $overtimeMin = 0;
        $anomalyFlag = false;

        $hasIn = $actualIn !== null;
        $hasOut = $actualOut !== null;
        $hasBoth = $hasIn && $hasOut;

        if ($hasBoth) {
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

        if ($scheduledOut && $actualOut && $actualOut->greaterThan($scheduledOut)) {
            $rawOtMin = $scheduledOut->diffInMinutes($actualOut);
            $overtimeMin = max(0, $rawOtMin - $overtimeMinBefore);
        }

        if ($actualIn && $scheduledOut && $actualIn->greaterThan($scheduledOut)) {
            $anomalyFlag = true;
        }

        if ($hasBoth) {
            $diffMin = $actualIn->diffInMinutes($actualOut, false);

            if ($diffMin < 60) {
                $anomalyFlag = true;
            }

            if ($actualOut->lessThan($actualIn)) {
                $anomalyFlag = true;
            }
        }

        $attendanceStatusCode = $draft['attendance_status_code'] ?? null;
        $presenceType = $draft['presence_type_code'] ?? null;

        if ($hasIn || $hasOut) {
            if ($hasBoth) {
                $attendanceStatusCode = 'PRESENT';

                if ($minWorkMinFullDay > 0 && $workMin >= $minWorkMinFullDay) {
                    $presenceType = 'FULL_DAY';
                } elseif ($minWorkMinHalfDay > 0 && $workMin >= $minWorkMinHalfDay) {
                    $presenceType = 'HALF_DAY';
                } else {
                    $presenceType = 'PARTIAL';
                }

                if ($presenceType === 'PARTIAL' && $anomalyFlag) {
                    $attendanceStatusCode = 'MANUAL_REVIEW';
                }
            } else {
                $presenceType = 'PARTIAL';
                $attendanceStatusCode = 'MANUAL_REVIEW';
                $anomalyFlag = true;
            }
        }

        if (!$calendarIsWorkday && ($hasIn || $hasOut)) {
            $attendanceStatusCode = 'PRESENT';

            if (!$presenceType) {
                $presenceType = 'PARTIAL';
            }
        }

        if ($calendarIsWorkday && ($hasIn || $hasOut) && empty($draft['shift_id'])) {
            $attendanceStatusCode = 'MANUAL_REVIEW';
            $anomalyFlag = true;
        }

        if (in_array((string) $attendanceStatusCode, ['HOLIDAY', 'OFF', 'LEAVE', 'SICK', 'PERMISSION', 'ABSENT'], true)) {
            $workMin = 0;
            $lateMin = 0;
            $earlyOutMin = 0;
            $overtimeMin = 0;
        }

        if ($attendanceStatusCode !== 'PRESENT') {
            $overtimeMin = 0;
        }

        if ($anomalyFlag || $presenceType !== 'FULL_DAY') {
            $overtimeMin = 0;
        }

        $draft['work_min'] = $workMin;
        $draft['late_min'] = $lateMin;
        $draft['early_out_min'] = $earlyOutMin;
        $draft['overtime_min'] = $overtimeMin;
        $draft['presence_type_code'] = $presenceType;
        $draft['attendance_status_code'] = $attendanceStatusCode;
        $draft['anomaly_flag'] = $anomalyFlag;

        return $draft;
    }

    /**
     * @param  object  $shift
     * @param  object  $policy
     * @return array<string, mixed>
     */
    protected function buildScheduleFromShift(string $workDate, object $shift, object $policy): array
    {
        $scheduledInDatetime = null;
        $scheduledOutDatetime = null;
        $breakMin = (int) ($shift->break_min ?? 0);

        if (!empty($shift->start_time)) {
            $scheduledInDatetime = Carbon::parse($workDate.' '.$shift->start_time, 'Asia/Jakarta');
        }

        if (!empty($shift->end_time)) {
            $scheduledOutDatetime = Carbon::parse($workDate.' '.$shift->end_time, 'Asia/Jakarta');

            if (!empty($shift->cross_day_flag) && $shift->cross_day_flag) {
                $scheduledOutDatetime = $scheduledOutDatetime->copy()->addDay();
            }
        }

        $expectedWorkMin = 0;
        if (!empty($shift->default_work_min)) {
            $expectedWorkMin = (int) $shift->default_work_min;
        } elseif ($scheduledInDatetime && $scheduledOutDatetime) {
            $expectedWorkMin = max(
                0,
                $scheduledInDatetime->diffInMinutes($scheduledOutDatetime, false) - $breakMin
            );
        }

        if ($expectedWorkMin <= 0) {
            $expectedWorkMin = (int) ($policy->min_work_min_full_day ?? 0);
        }

        return [
            'scheduled_in_datetime' => $scheduledInDatetime,
            'scheduled_out_datetime' => $scheduledOutDatetime,
            'break_min' => $breakMin,
            'expected_work_min' => $expectedWorkMin,
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    protected function scheduleFromDraft(array $draft): array
    {
        return [
            'scheduled_in_datetime' => $draft['scheduled_in_datetime'] ?? null,
            'scheduled_out_datetime' => $draft['scheduled_out_datetime'] ?? null,
            'break_min' => (int) ($draft['break_min'] ?? 0),
            'expected_work_min' => 0,
        ];
    }

    protected function toCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->setTimezone('Asia/Jakarta');
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value)->setTimezone('Asia/Jakarta');
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value)->setTimezone('Asia/Jakarta');
        }

        return null;
    }

    protected function stringifyDateTime(mixed $value): string
    {
        $carbon = $this->toCarbon($value);

        return $carbon ? $carbon->format('Y-m-d H:i:s') : '[null]';
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    protected function normalizeFinalDraft(array $draft): array
    {
        $draft['work_min'] = max(0, (int) ($draft['work_min'] ?? 0));
        $draft['late_min'] = max(0, (int) ($draft['late_min'] ?? 0));
        $draft['early_out_min'] = max(0, (int) ($draft['early_out_min'] ?? 0));
        $draft['overtime_min'] = max(0, (int) ($draft['overtime_min'] ?? 0));
        $draft['exception_flag'] = (bool) ($draft['exception_flag'] ?? false);

        $finalStatus = (string) ($draft['attendance_status_code'] ?? '');

        if (!in_array($finalStatus, ['LEAVE', 'SICK', 'PERMISSION'], true)) {
            $draft['leave_flag'] = false;
        } else {
            $draft['leave_flag'] = (bool) ($draft['leave_flag'] ?? false);
        }

        return $draft;
    }

    protected function appendNote(?string $existingNotes, string $newNote): string
    {
        $existingNotes = trim((string) $existingNotes);
        $newNote = trim($newNote);

        if ($existingNotes === '') {
            return $newNote;
        }

        return $existingNotes.' | '.$newNote;
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeDetailLog(
        string $stepName,
        string $stepResult,
        AttendanceException $exception,
        string $notes
    ): array {
        return [
            'step_name' => $stepName,
            'step_result' => $stepResult,
            'source_type_code' => $exception->source_type_code ?: 'APPROVAL',
            'source_ref_id' => $exception->source_ref_id ?: ('attendance_exception:'.$exception->attendance_exception_id),
            'notes' => $notes,
        ];
    }
}