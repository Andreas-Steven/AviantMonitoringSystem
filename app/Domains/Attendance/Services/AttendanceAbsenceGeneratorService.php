<?php

namespace App\Domains\Attendance\Services;

class AttendanceAbsenceGeneratorService
{
    /**
     * Determine whether a row should be generated as ABSENT.
     *
     * ABSENT only applies when:
     * - the day is a workday
     * - there is no valid attendance
     * - there is no approved leave
     * - there is no approved exception that covers attendance
     * - there is no existing daily result yet
     */
    public function shouldGenerateAbsent(
        bool $isWorkday,
        bool $hasActualAttendance,
        bool $hasApprovedLeave,
        bool $hasApprovedException,
        bool $alreadyHasDailyResult
    ): bool {
        if ($alreadyHasDailyResult) {
            return false;
        }

        if (! $isWorkday) {
            return false;
        }

        if ($hasActualAttendance) {
            return false;
        }

        if ($hasApprovedLeave) {
            return false;
        }

        if ($hasApprovedException) {
            return false;
        }

        return true;
    }

    /**
     * For Opsi 1, every employee-date should produce one daily result.
     *
     * This method resolves the final non-attendance status:
     * - ABSENT for workday without valid coverage
     * - HOLIDAY/OFF for non-workday without attendance
     */
    public function resolveStatusForNoAttendance(
        bool $isWorkday,
        string $dayTypeCode
    ): string {
        if ($isWorkday) {
            return 'ABSENT';
        }

        return $this->resolveNonWorkdayStatus($dayTypeCode);
    }

    public function resolveNonWorkdayStatus(string $dayTypeCode): string
    {
        return match ($dayTypeCode) {
            'HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY' => 'HOLIDAY',
            'WEEKOFF' => 'OFF',
            default => 'OFF',
        };
    }

    /**
     * Optional helper for explanation / audit detail.
     */
    public function resolveReasonCode(
        bool $isWorkday,
        bool $hasActualAttendance,
        bool $hasApprovedLeave,
        bool $hasApprovedException
    ): string {
        if (! $isWorkday) {
            return 'NON_WORKDAY';
        }

        if ($hasApprovedLeave) {
            return 'LEAVE_EXISTS';
        }

        if ($hasApprovedException) {
            return 'EXCEPTION_EXISTS';
        }

        if ($hasActualAttendance) {
            return 'ATTENDANCE_EXISTS';
        }

        return 'WORKDAY_WITHOUT_COVERAGE';
    }
}