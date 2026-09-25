<?php

namespace App\Domains\Summary\Services;

class DailySummaryClassifierService
{
    /**
     * @param array{
     *   attendance_status_code:string,
     *   presence_type_code:?string,
     *   anomaly_flag:bool,
     *   leave_flag:bool,
     *   exception_flag:bool
     * } $daily
     *
     * @param array{
     *   day_type_code:string,
     *   is_workday:bool
     * } $calendar
     *
     * @return array{
     *   summary_class:string,
     *   count_present_value:float,
     *   count_overtime_candidate:bool,
     *   count_excess_candidate:bool,
     *   count_deficit_value:float,
     *   count_leave_quota_value:float,
     *   notes:?string
     * }
     */
    public function classify(array $daily, array $calendar): array
    {
        $status = strtoupper((string) $daily['attendance_status_code']);
        $presenceType = strtoupper((string) ($daily['presence_type_code'] ?? ''));

        if ($status === 'PRESENT') {
            $presentValue = $this->resolvePresentValue($presenceType);

            if (! $calendar['is_workday']) {
                if (in_array($calendar['day_type_code'], ['HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY'], true)) {
                    return [
                        'summary_class' => 'HOLIDAY_WORK',
                        'count_present_value' => $presentValue,
                        'count_overtime_candidate' => true,
                        'count_excess_candidate' => true,
                        'count_deficit_value' => 0.0,
                        'count_leave_quota_value' => 0.0,
                        'notes' => 'Present on holiday.',
                    ];
                }

                if ($calendar['day_type_code'] === 'WEEKOFF') {
                    return [
                        'summary_class' => 'OFFDAY_WORK',
                        'count_present_value' => $presentValue,
                        'count_overtime_candidate' => true,
                        'count_excess_candidate' => true,
                        'count_deficit_value' => 0.0,
                        'count_leave_quota_value' => 0.0,
                        'notes' => 'Present on weekly off.',
                    ];
                }

                return [
                    'summary_class' => 'NON_WORKDAY_PRESENT',
                    'count_present_value' => $presentValue,
                    'count_overtime_candidate' => true,
                    'count_excess_candidate' => true,
                    'count_deficit_value' => 0.0,
                    'count_leave_quota_value' => 0.0,
                    'notes' => 'Present on non-workday.',
                ];
            }

            return [
                'summary_class' => 'PRESENT_WORKDAY',
                'count_present_value' => $presentValue,
                'count_overtime_candidate' => false,
                'count_excess_candidate' => false,
                'count_deficit_value' => 0.0,
                'count_leave_quota_value' => 0.0,
                'notes' => 'Present on workday.',
            ];
        }

        if ($status === 'ABSENT') {
            return [
                'summary_class' => 'ABSENT',
                'count_present_value' => 0.0,
                'count_overtime_candidate' => false,
                'count_excess_candidate' => false,
                'count_deficit_value' => 1.0,
                'count_leave_quota_value' => 0.0,
                'notes' => 'Absent on workday.',
            ];
        }

        if ($status === 'LEAVE') {
            return [
                'summary_class' => 'LEAVE',
                'count_present_value' => 0.0,
                'count_overtime_candidate' => false,
                'count_excess_candidate' => false,
                'count_deficit_value' => 0.0,
                'count_leave_quota_value' => 1.0,
                'notes' => 'Approved leave.',
            ];
        }

        if ($status === 'SICK') {
            return [
                'summary_class' => 'SICK',
                'count_present_value' => 0.0,
                'count_overtime_candidate' => false,
                'count_excess_candidate' => false,
                'count_deficit_value' => 0.0,
                'count_leave_quota_value' => 0.0,
                'notes' => 'Sick leave / sick status.',
            ];
        }

        if ($status === 'PERMISSION') {
            return [
                'summary_class' => 'PERMISSION',
                'count_present_value' => 0.0,
                'count_overtime_candidate' => false,
                'count_excess_candidate' => false,
                'count_deficit_value' => 0.0,
                'count_leave_quota_value' => 0.0,
                'notes' => 'Permission status.',
            ];
        }

        if (in_array($status, ['HOLIDAY', 'OFF'], true)) {
            return [
                'summary_class' => 'NON_WORKDAY_NO_ATTENDANCE',
                'count_present_value' => 0.0,
                'count_overtime_candidate' => false,
                'count_excess_candidate' => false,
                'count_deficit_value' => 0.0,
                'count_leave_quota_value' => 0.0,
                'notes' => 'Non-workday without attendance.',
            ];
        }

        if (in_array($status, ['MANUAL_REVIEW', 'INCOMPLETE'], true)) {
            return [
                'summary_class' => 'REVIEW_PENDING',
                'count_present_value' => 0.0,
                'count_overtime_candidate' => false,
                'count_excess_candidate' => false,
                'count_deficit_value' => 0.0,
                'count_leave_quota_value' => 0.0,
                'notes' => 'Pending manual review / incomplete.',
            ];
        }

        return [
            'summary_class' => 'UNCLASSIFIED',
            'count_present_value' => 0.0,
            'count_overtime_candidate' => false,
            'count_excess_candidate' => false,
            'count_deficit_value' => 0.0,
            'count_leave_quota_value' => 0.0,
            'notes' => 'Unhandled attendance status.',
        ];
    }

    private function resolvePresentValue(?string $presenceTypeCode): float
    {
        return match ($presenceTypeCode) {
            'FULL_DAY' => 1.0,
            'HALF_DAY' => 0.5,
            'PARTIAL' => 0.25,
            default => 0.0,
        };
    }
}