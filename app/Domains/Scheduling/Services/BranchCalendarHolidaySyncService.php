<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Models\BranchCalendar;
use App\Domains\Scheduling\Models\HolidayEvent;
use Illuminate\Support\Collection;

class BranchCalendarHolidaySyncService
{
    public function __construct(
        protected CalendarDayTypeResolverService $calendarDayTypeResolverService,
    ) {
    }

    /**
     * Sync existing branch calendar rows for specific dates and branches.
     *
     * Important:
     * - Does not create missing branch_calendar rows.
     * - Does not regenerate full period.
     * - Resolves final day_type/is_workday/notes using existing resolver.
     */
    public function syncDatesForBranches(array $workDates, array|Collection $branchIds): void
    {
        $dates = collect($workDates)
            ->filter()
            ->map(fn ($date) => (string) $date)
            ->unique()
            ->values();

        $branchIds = collect($branchIds)
            ->filter()
            ->map(fn ($branchId) => (int) $branchId)
            ->unique()
            ->values();

        if ($dates->isEmpty() || $branchIds->isEmpty()) {
            return;
        }

        $branches = Branch::query()
            ->whereIn('branch_id', $branchIds)
            ->get()
            ->keyBy('branch_id');

        foreach ($branchIds as $branchId) {
            $branch = $branches->get($branchId);

            if (!$branch) {
                continue;
            }

            foreach ($dates as $workDate) {
                $calendar = BranchCalendar::query()
                    ->where('branch_id', $branchId)
                    ->whereDate('work_date', $workDate)
                    ->first();

                if (!$calendar) {
                    continue;
                }

                $resolved = $this->calendarDayTypeResolverService->resolve(
                    branch: $branch,
                    workDate: $workDate,
                );

                $calendar->update([
                    'day_type_code' => $resolved['day_type_code'],
                    'day_name' => $resolved['day_name'],
                    'is_workday' => $resolved['is_workday'],
                    'notes' => $resolved['notes'],
                ]);
            }
        }
    }

    public function resolveBranchIdsFromHolidayEvent(HolidayEvent $holidayEvent): Collection
    {
        $holidayEvent->loadMissing('scopes');

        if ($holidayEvent->scopes->contains('applies_to_all_branches', true)) {
            return Branch::query()
                ->where('active', true)
                ->pluck('branch_id')
                ->map(fn ($branchId) => (int) $branchId)
                ->values();
        }

        return $holidayEvent->scopes
            ->pluck('branch_id')
            ->filter()
            ->map(fn ($branchId) => (int) $branchId)
            ->unique()
            ->values();
    }

    public function syncHolidayEvent(HolidayEvent $holidayEvent): void
    {
        $branchIds = $this->resolveBranchIdsFromHolidayEvent($holidayEvent);

        $this->syncDatesForBranches(
            workDates: [$holidayEvent->holiday_date->toDateString()],
            branchIds: $branchIds,
        );
    }
}