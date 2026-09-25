<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Master\Models\Branch;
use Carbon\Carbon;

class CalendarDayTypeResolverService
{
    public function __construct(
        protected HolidaySourceService $holidaySourceService,
    ) {
    }

    public function resolve(Branch $branch, string $workDate): array
    {
        $date = Carbon::parse($workDate);

        $holiday = $this->holidaySourceService->resolveForBranchAndDate(
            branch: $branch,
            workDate: $date->toDateString(),
        );

        if (($holiday['found'] ?? false) === true) {
            return [
                'day_type_code' => $holiday['day_type_code'],
                'day_name' => $date->translatedFormat('l'),
                'is_workday' => (bool) $holiday['is_workday'],
                'notes' => $holiday['notes'],
            ];
        }

        if ($this->isWeeklyOff($branch, $date)) {
            return [
                'day_type_code' => 'WEEKOFF',
                'day_name' => $date->translatedFormat('l'),
                'is_workday' => false,
                'notes' => $this->resolveWeeklyOffNote($branch, $date),
            ];
        }

        return [
            'day_type_code' => 'WORKDAY',
            'day_name' => $date->translatedFormat('l'),
            'is_workday' => true,
            'notes' => null,
        ];
    }

    protected function isWeeklyOff(Branch $branch, Carbon $date): bool
    {
        $branchTypeCode = (string) ($branch->branch_type_code ?? '');

        if ($branchTypeCode === 'OFFICE') {
            return (int) $date->dayOfWeekIso === 7;
        }

        return false;
    }

    protected function resolveWeeklyOffNote(Branch $branch, Carbon $date): ?string
    {
        $branchTypeCode = (string) ($branch->branch_type_code ?? '');

        if ($branchTypeCode === 'OFFICE' && (int) $date->dayOfWeekIso === 7) {
            return 'Weekly off (Sunday)';
        }

        return null;
    }
}