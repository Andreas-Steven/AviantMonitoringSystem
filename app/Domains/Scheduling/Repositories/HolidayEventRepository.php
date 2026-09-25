<?php

namespace App\Domains\Scheduling\Repositories;

use App\Domains\Scheduling\Models\HolidayEvent;
use Illuminate\Support\Collection;

class HolidayEventRepository
{
    public function findApplicableHolidayForBranchAndDate(int $branchId, string $workDate): ?HolidayEvent
    {
        return HolidayEvent::query()
            ->with(['scopes'])
            ->where('active', true)
            ->whereDate('holiday_date', $workDate)
            ->where(function ($query) use ($branchId) {
                $query->whereHas('scopes', function ($scopeQuery) use ($branchId) {
                    $scopeQuery
                        ->where('applies_to_all_branches', true)
                        ->orWhere('branch_id', $branchId);
                });
            })
            ->orderByRaw("
                CASE day_type_code
                    WHEN 'HOLIDAY_NATIONAL' THEN 1
                    WHEN 'HOLIDAY_COMPANY' THEN 2
                    WHEN 'HALF_DAY' THEN 3
                    WHEN 'SPECIAL' THEN 4
                    ELSE 99
                END
            ")
            ->orderBy('holiday_event_id')
            ->first();
    }

    public function getApplicableHolidaysForBranchAndDateRange(
        int $branchId,
        string $startDate,
        string $endDate
    ): Collection {
        return HolidayEvent::query()
            ->with(['scopes'])
            ->where('active', true)
            ->whereBetween('holiday_date', [$startDate, $endDate])
            ->where(function ($query) use ($branchId) {
                $query->whereHas('scopes', function ($scopeQuery) use ($branchId) {
                    $scopeQuery
                        ->where('applies_to_all_branches', true)
                        ->orWhere('branch_id', $branchId);
                });
            })
            ->orderBy('holiday_date')
            ->orderByRaw("
                CASE day_type_code
                    WHEN 'HOLIDAY_NATIONAL' THEN 1
                    WHEN 'HOLIDAY_COMPANY' THEN 2
                    WHEN 'HALF_DAY' THEN 3
                    WHEN 'SPECIAL' THEN 4
                    ELSE 99
                END
            ")
            ->orderBy('holiday_event_id')
            ->get();
    }
}