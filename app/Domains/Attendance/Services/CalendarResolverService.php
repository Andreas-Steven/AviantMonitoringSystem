<?php

namespace App\Domains\Attendance\Services;

use App\Domains\Scheduling\Repositories\BranchCalendarRepository;
use RuntimeException;

class CalendarResolverService
{
    public function __construct(
        protected BranchCalendarRepository $branchCalendarRepository,
    ) {}

    /**
     * Resolve final branch calendar for a given date.
     *
     * Return shape:
     * [
     *   'branch_calendar_id' => int,
     *   'branch_id' => int,
     *   'work_date' => string,
     *   'day_type_code' => string,
     *   'day_name' => ?string,
     *   'is_workday' => bool,
     *   'notes' => ?string,
     * ]
     *
     * @throws RuntimeException
     */
    public function resolveOrFail(int $branchId, string $workDate): array
    {
        $calendar = $this->branchCalendarRepository->findByBranchAndDate($branchId, $workDate);

        if (! $calendar) {
            throw new RuntimeException(
                sprintf(
                    'Branch calendar not found for branch_id=%d on work_date=%s',
                    $branchId,
                    $workDate
                )
            );
        }

        return [
            'branch_calendar_id' => (int) $calendar->branch_calendar_id,
            'branch_id' => (int) $calendar->branch_id,
            'work_date' => $calendar->work_date->toDateString(),
            'day_type_code' => (string) $calendar->day_type_code,
            'day_name' => $calendar->day_name,
            'is_workday' => (bool) $calendar->is_workday,
            'notes' => $calendar->notes,
        ];
    }

    public function isWorkday(int $branchId, string $workDate): bool
    {
        $calendar = $this->resolveOrFail($branchId, $workDate);

        return $calendar['is_workday'];
    }

    public function resolveAttendanceStatusForNoAttendance(int $branchId, string $workDate): string
    {
        $calendar = $this->resolveOrFail($branchId, $workDate);

        if ($calendar['is_workday']) {
            return 'ABSENT';
        }

        return $this->mapNonWorkdayStatus($calendar['day_type_code']);
    }

    protected function mapNonWorkdayStatus(string $dayTypeCode): string
    {
        return match ($dayTypeCode) {
            'HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY' => 'HOLIDAY',
            'WEEKOFF' => 'OFF',
            default => 'OFF',
        };
    }
}