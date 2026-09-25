<?php

namespace App\Domains\Attendance\Services;

use App\Domains\Requests\Repositories\LeaveRequestRepository;

class LeaveResolverService
{
    public function __construct(
        protected LeaveRequestRepository $leaveRequestRepository,
    ) {}

    /**
     * Return shape:
     * [
     *   'has_approved_leave' => bool,
     *   'leave_request_id' => ?int,
     *   'leave_type_id' => ?int,
     *   'leave_type_code' => ?string,
     *   'leave_type_name' => ?string,
     *   'partial_day_flag' => bool,
     *   'partial_start_time' => ?string,
     *   'partial_end_time' => ?string,
     *   'status_for_daily' => ?string,
     *   'notes' => ?string,
     * ]
     */
    public function resolveForDate(int $empId, string $workDate): array
    {
        $leave = $this->leaveRequestRepository->findFirstApprovedLeaveForDate($empId, $workDate);

        if (! $leave) {
            return [
                'has_approved_leave' => false,
                'leave_request_id' => null,
                'leave_type_id' => null,
                'leave_type_code' => null,
                'leave_type_name' => null,
                'partial_day_flag' => false,
                'partial_start_time' => null,
                'partial_end_time' => null,
                'status_for_daily' => null,
                'notes' => null,
            ];
        }

        $leaveTypeCode = $leave->leaveType?->leave_type_code;
        $statusForDaily = $this->mapLeaveTypeToAttendanceStatus($leaveTypeCode);

        return [
            'has_approved_leave' => true,
            'leave_request_id' => (int) $leave->leave_request_id,
            'leave_type_id' => (int) $leave->leave_type_id,
            'leave_type_code' => $leaveTypeCode,
            'leave_type_name' => $leave->leaveType?->leave_type_name,
            'partial_day_flag' => (bool) $leave->partial_day_flag,
            'partial_start_time' => $leave->partial_start_time?->format('H:i:s'),
            'partial_end_time' => $leave->partial_end_time?->format('H:i:s'),
            'status_for_daily' => $statusForDaily,
            'notes' => $leave->notes,
        ];
    }

    protected function mapLeaveTypeToAttendanceStatus(?string $leaveTypeCode): string
    {
        return match ($leaveTypeCode) {
            'SICK' => 'SICK',
            'PERMIT' => 'PERMISSION',
            default => 'LEAVE',
        };
    }
}