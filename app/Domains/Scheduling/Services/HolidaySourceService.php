<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Repositories\HolidayEventRepository;

class HolidaySourceService
{
    public function __construct(
        protected HolidayEventRepository $holidayEventRepository,
    ) {
    }

    public function resolveForBranchAndDate(Branch $branch, string $workDate): array
    {
        $holiday = $this->holidayEventRepository->findApplicableHolidayForBranchAndDate(
            (int) $branch->branch_id,
            $workDate
        );

        if (!$holiday) {
            return [
                'found' => false,
                'holiday' => null,
                'day_type_code' => null,
                'is_workday' => null,
                'notes' => null,
            ];
        }

        $dayTypeCode = (string) $holiday->day_type_code;
        $holidayName = trim((string) ($holiday->holiday_name ?? 'Holiday'));

        return [
            'found' => true,
            'holiday' => $holiday,
            'day_type_code' => $dayTypeCode,
            'is_workday' => $this->resolveHolidayIsWorkday($branch, $dayTypeCode),
            'notes' => $holidayName,
        ];
    }

    protected function resolveHolidayIsWorkday(Branch $branch, string $dayTypeCode): bool
    {
        $branchTypeCode = (string) ($branch->branch_type_code ?? '');

        if ($dayTypeCode === 'HOLIDAY_COMPANY') {
            return false;
        }

        if ($dayTypeCode === 'HOLIDAY_NATIONAL') {
            return in_array($branchTypeCode, ['RETAIL_HO', 'RETAIL_BRANCH'], true);
        }

        if (in_array($dayTypeCode, ['HALF_DAY', 'SPECIAL'], true)) {
            return true;
        }

        return true;
    }
}