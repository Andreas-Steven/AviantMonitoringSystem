<?php

namespace App\Domains\Scheduling\Repositories;

use App\Domains\Scheduling\Models\BranchCalendar;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BranchCalendarRepository
{
    public function deleteByBranchAndDateRange(
        int $branchId,
        CarbonInterface $startDate,
        CarbonInterface $endDate
    ): void {
        BranchCalendar::query()
            ->where('branch_id', $branchId)
            ->whereBetween('work_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->delete();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function upsertRows(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        BranchCalendar::query()->upsert(
            $rows,
            ['branch_id', 'work_date'],
            ['day_type_code', 'day_name', 'is_workday', 'notes', 'updated_at']
        );
    }

    public function findByBranchAndDate(int $branchId, string $workDate): ?BranchCalendar
    {
        return BranchCalendar::query()
            ->where('branch_id', $branchId)
            ->whereDate('work_date', $workDate)
            ->first();
    }

    public function getRange(int $branchId, string $startDate, string $endDate): Collection
    {
        return BranchCalendar::query()
            ->where('branch_id', $branchId)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->orderBy('work_date')
            ->get();
    }

    public function existsInRange(int $branchId, string $startDate, string $endDate): bool
    {
        return BranchCalendar::query()
            ->where('branch_id', $branchId)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->exists();
    }
}