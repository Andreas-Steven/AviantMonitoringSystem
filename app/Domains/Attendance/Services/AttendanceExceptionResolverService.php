<?php

namespace App\Domains\Attendance\Services;

use App\Domains\Review\Repositories\AttendanceExceptionRepository;

class AttendanceExceptionResolverService
{
    public function __construct(
        protected AttendanceExceptionRepository $attendanceExceptionRepository,
    ) {}

    /**
     * Return shape:
     * [
     *   'has_exception' => bool,
     *   'has_status_override' => bool,
     *   'has_shift_override' => bool,
     *   'has_time_override' => bool,
     *   'has_minutes_override' => bool,
     *   'status_value_code' => ?string,
     *   'shift_id_value' => ?int,
     *   'time_value' => ?string,
     *   'minutes_value' => ?int,
     *   'exception_types' => string[],
     *   'raw_items' => array<int, array<string, mixed>>,
     * ]
     */
    public function resolveForDate(int $empId, string $workDate): array
    {
        $items = $this->attendanceExceptionRepository->getByEmployeeAndDate($empId, $workDate);

        if ($items->isEmpty()) {
            return [
                'has_exception' => false,
                'has_status_override' => false,
                'has_shift_override' => false,
                'has_time_override' => false,
                'has_minutes_override' => false,
                'status_value_code' => null,
                'shift_id_value' => null,
                'time_value' => null,
                'minutes_value' => null,
                'exception_types' => [],
                'raw_items' => [],
            ];
        }

        $statusOverride = $items->firstWhere('status_value_code', '!=', null);
        $shiftOverride = $items->firstWhere('shift_id_value', '!=', null);
        $timeOverride = $items->firstWhere('time_value', '!=', null);
        $minutesOverride = $items->firstWhere('minutes_value', '!=', null);

        return [
            'has_exception' => true,
            'has_status_override' => (bool) $statusOverride,
            'has_shift_override' => (bool) $shiftOverride,
            'has_time_override' => (bool) $timeOverride,
            'has_minutes_override' => (bool) $minutesOverride,
            'status_value_code' => $statusOverride?->status_value_code,
            'shift_id_value' => $shiftOverride?->shift_id_value ? (int) $shiftOverride->shift_id_value : null,
            'time_value' => $timeOverride?->time_value?->toDateTimeString(),
            'minutes_value' => $minutesOverride?->minutes_value !== null
                ? (int) $minutesOverride->minutes_value
                : null,
            'exception_types' => $items
                ->pluck('exception_type_code')
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'raw_items' => $items->map(function ($item): array {
                return [
                    'attendance_exception_id' => (int) $item->attendance_exception_id,
                    'exception_type_code' => $item->exception_type_code,
                    'minutes_value' => $item->minutes_value !== null ? (int) $item->minutes_value : null,
                    'time_value' => $item->time_value?->toDateTimeString(),
                    'shift_id_value' => $item->shift_id_value !== null ? (int) $item->shift_id_value : null,
                    'status_value_code' => $item->status_value_code,
                    'reason' => $item->reason,
                    'source_type_code' => $item->source_type_code,
                    'source_ref_id' => $item->source_ref_id,
                    'notes' => $item->notes,
                ];
            })->all(),
        ];
    }
}