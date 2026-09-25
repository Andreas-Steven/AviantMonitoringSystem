<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Models\DayType;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Scheduling\Models\HolidayEvent;
use App\Domains\Scheduling\Repositories\BranchCalendarRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BranchCalendarIndexViewService
{
    public function __construct(
        protected BranchCalendarRepository $branchCalendarRepository,
    ) {
    }

    public function build(array $filters): array
    {
        $branchId = isset($filters['branch_id']) && $filters['branch_id'] !== ''
            ? (int) $filters['branch_id']
            : null;

        $periodCode = isset($filters['period_code']) && $filters['period_code'] !== ''
            ? (string) $filters['period_code']
            : null;

        $viewMode = in_array(($filters['view_mode'] ?? 'grid'), ['grid', 'list'], true)
            ? (string) ($filters['view_mode'] ?? 'grid')
            : 'grid';

        $selectedDate = isset($filters['selected_date']) && $filters['selected_date'] !== ''
            ? (string) $filters['selected_date']
            : null;

        $showNotesOnly = (bool) ($filters['show_notes_only'] ?? false);
        $showNonWorkdayOnly = (bool) ($filters['show_non_workday_only'] ?? false);
        $editDay = (bool) ($filters['edit_day'] ?? false);

        $branchOptions = $this->buildBranchOptions();
        $periodOptions = $this->buildPeriodOptions();
        $dayTypeOptions = $this->buildDayTypeOptions();

        $activeBranch = $this->resolveActiveBranch($branchId);
        $activePeriod = $this->resolveActivePeriod($periodCode);

        if (!$activeBranch || !$activePeriod) {
            return $this->buildEmptyPayload(
                branchOptions: $branchOptions,
                periodOptions: $periodOptions,
                dayTypeOptions: $dayTypeOptions,
                filters: [
                    'branch_id' => $branchId,
                    'period_code' => $periodCode,
                    'view_mode' => $viewMode,
                    'selected_date' => $selectedDate,
                    'show_notes_only' => $showNotesOnly,
                    'show_non_workday_only' => $showNonWorkdayOnly,
                    'edit_day' => $editDay,
                ],
                activeBranch: $activeBranch,
                activePeriod: $activePeriod,
            );
        }

        $rawRows = $this->branchCalendarRepository->getRange(
            $activeBranch['id'],
            $activePeriod['date_from'],
            $activePeriod['date_to']
        );

        $calendarRows = $this->buildCalendarRows($rawRows);
        $filteredCalendarRows = $this->applyUiFilters(
            $calendarRows,
            $showNotesOnly,
            $showNonWorkdayOnly
        );

        $summary = $this->buildSummary($calendarRows);

        $selectedDay = $this->resolveSelectedDay(
            $calendarRows,
            $selectedDate,
            $activePeriod['date_from'],
            $activePeriod['date_to']
        );

        $calendarMatrix = $this->buildCalendarMatrix(
            $calendarRows,
            $activePeriod['date_from'],
            $activePeriod['date_to'],
            $selectedDay['work_date'] ?? null
        );

        return [
            'branchOptions' => $branchOptions,
            'periodOptions' => $periodOptions,
            'dayTypeOptions' => $dayTypeOptions,
            'filters' => [
                'branch_id' => $activeBranch['id'],
                'period_code' => $activePeriod['code'],
                'view_mode' => $viewMode,
                'selected_date' => $selectedDay['work_date'] ?? null,
                'show_notes_only' => $showNotesOnly,
                'show_non_workday_only' => $showNonWorkdayOnly,
                'edit_day' => $editDay,
            ],
            'activeBranch' => $activeBranch,
            'activePeriod' => $activePeriod,
            'summary' => $summary,
            'calendarRows' => $filteredCalendarRows,
            'calendarMatrix' => $calendarMatrix,
            'selectedDay' => $selectedDay,
            'canManage' => true,
            'hasCalendarData' => count($calendarRows) > 0,
            'periodHolidayEvents' => $this->buildPeriodHolidayEvents(
                branchId: $activeBranch['id'],
                dateFrom: $activePeriod['date_from'],
                dateTo: $activePeriod['date_to'],
            ),
        ];
    }

    protected function buildEmptyPayload(
        array $branchOptions,
        array $periodOptions,
        array $dayTypeOptions,
        array $filters,
        ?array $activeBranch = null,
        ?array $activePeriod = null,
    ): array {
        return [
            'branchOptions' => $branchOptions,
            'periodOptions' => $periodOptions,
            'dayTypeOptions' => $dayTypeOptions,
            'filters' => $filters,
            'activeBranch' => $activeBranch,
            'activePeriod' => $activePeriod,
            'summary' => [
                'total_days' => 0,
                'workdays' => 0,
                'holidays' => 0,
                'weekoff' => 0,
                'special_days' => 0,
                'overrides' => 0,
            ],
            'calendarRows' => [],
            'calendarMatrix' => [],
            'selectedDay' => null,
            'canManage' => true,
            'hasCalendarData' => false,
            'periodHolidayEvents' => [],
        ];
    }

    protected function resolveActiveBranch(?int $branchId): ?array
    {
        $query = Branch::query()
            ->where('active', true)
            ->orderBy('branch_name');

        $branch = $branchId
            ? (clone $query)->where('branch_id', $branchId)->first()
            : $query->first();

        if (!$branch) {
            return null;
        }

        return [
            'id' => (int) $branch->branch_id,
            'code' => (string) $branch->branch_code,
            'name' => (string) $branch->branch_name,
        ];
    }

    protected function resolveActivePeriod(?string $periodCode): ?array
    {
        $query = PayrollPeriod::query()->orderByDesc('period_start_date');

        $period = null;

        if ($periodCode) {
            $period = (clone $query)
                ->where('period_code', $periodCode)
                ->first();
        }

        if (!$period) {
            $period = PayrollPeriod::query()
                ->where('payroll_period_status_code', 'OPEN')
                ->orderByDesc('period_start_date')
                ->first();
        }

        if (!$period) {
            $period = $query->first();
        }

        if (!$period) {
            return null;
        }

        return [
            'id' => (int) $period->payroll_period_id,
            'code' => (string) $period->period_code,
            'date_from' => Carbon::parse($period->period_start_date)->format('Y-m-d'),
            'date_to' => Carbon::parse($period->period_end_date)->format('Y-m-d'),
            'payroll_year' => (int) $period->payroll_year,
            'payroll_month' => (int) $period->payroll_month,
        ];
    }

    protected function buildBranchOptions(): array
    {
        return Branch::query()
            ->where('active', true)
            ->orderBy('branch_name')
            ->get(['branch_id', 'branch_name'])
            ->map(fn ($branch) => [
                'value' => (int) $branch->branch_id,
                'label' => (string) $branch->branch_name,
            ])
            ->all();
    }

    protected function buildPeriodOptions(): array
    {
        return PayrollPeriod::query()
            ->orderByDesc('period_start_date')
            ->limit(12)
            ->get(['period_code', 'period_start_date', 'period_end_date'])
            ->map(function ($period) {
                return [
                    'value' => (string) $period->period_code,
                    'label' => sprintf(
                        '%s (%s - %s)',
                        $period->period_code,
                        Carbon::parse($period->period_start_date)->format('d M Y'),
                        Carbon::parse($period->period_end_date)->format('d M Y'),
                    ),
                ];
            })
            ->all();
    }

    protected function buildDayTypeOptions(): array
    {
        return DayType::query()
            ->orderBy('day_type_name')
            ->get(['day_type_code', 'day_type_name', 'is_workday_default'])
            ->map(function ($dayType) {
                return [
                    'value' => (string) $dayType->day_type_code,
                    'label' => (string) $dayType->day_type_name,
                    'is_workday_default' => (bool) $dayType->is_workday_default,
                ];
            })
            ->all();
    }

    protected function buildCalendarRows(iterable $rows): array
    {
        return collect($rows)
            ->map(function ($row) {
                $workDate = Carbon::parse($row->work_date);

                return [
                    'id' => (int) $row->branch_calendar_id,
                    'work_date' => $workDate->format('Y-m-d'),
                    'day_name' => $workDate->format('l'),
                    'day_name_short' => $workDate->format('D'),
                    'day_number' => (int) $workDate->format('j'),
                    'day_type_code' => (string) $row->day_type_code,
                    'day_type_label' => $this->mapDayTypeLabel((string) $row->day_type_code),
                    'is_workday' => (bool) $row->is_workday,
                    'notes' => $row->notes,
                    'is_override' => $this->detectOverride($row),
                    'updated_at' => $row->updated_at
                        ? Carbon::parse($row->updated_at)->format('Y-m-d H:i')
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    protected function applyUiFilters(
        array $calendarRows,
        bool $showNotesOnly,
        bool $showNonWorkdayOnly
    ): array {
        return collect($calendarRows)
            ->when($showNotesOnly, function (Collection $collection) {
                return $collection->filter(fn ($row) => filled($row['notes']));
            })
            ->when($showNonWorkdayOnly, function (Collection $collection) {
                return $collection->filter(fn ($row) => $row['is_workday'] === false);
            })
            ->values()
            ->all();
    }

    protected function buildSummary(array $calendarRows): array
    {
        $collection = collect($calendarRows);

        return [
            'total_days' => $collection->count(),
            'workdays' => $collection->where('is_workday', true)->count(),
            'holidays' => $collection->filter(function ($row) {
                return in_array($row['day_type_code'], ['HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY'], true);
            })->count(),
            'weekoff' => $collection->where('day_type_code', 'WEEKOFF')->count(),
            'special_days' => $collection->filter(function ($row) {
                return in_array($row['day_type_code'], ['SPECIAL', 'HALF_DAY'], true);
            })->count(),
            'overrides' => $collection->where('is_override', true)->count(),
        ];
    }

    protected function buildPeriodHolidayEvents(
        int $branchId,
        string $dateFrom,
        string $dateTo
    ): array {
        return HolidayEvent::query()
            ->with(['scopes.branch', 'dayType'])
            ->where('active', true)
            ->whereDate('holiday_date', '>=', $dateFrom)
            ->whereDate('holiday_date', '<=', $dateTo)
            ->where(function ($query) use ($branchId): void {
                $query
                    ->whereHas('scopes', function ($scopeQuery): void {
                        $scopeQuery->where('applies_to_all_branches', true);
                    })
                    ->orWhereHas('scopes', function ($scopeQuery) use ($branchId): void {
                        $scopeQuery
                            ->where('applies_to_all_branches', false)
                            ->where('branch_id', $branchId);
                    });
            })
            ->orderBy('holiday_date')
            ->orderBy('holiday_name')
            ->get()
            ->map(function (HolidayEvent $event): array {
                $scopeLabel = $event->scopes
                    ->contains('applies_to_all_branches', true)
                        ? 'All branches'
                        : $event->scopes
                            ->pluck('branch.branch_name')
                            ->filter()
                            ->implode(', ');

                return [
                    'id' => (int) $event->holiday_event_id,
                    'code' => (string) $event->holiday_code,
                    'name' => (string) $event->holiday_name,
                    'date' => $event->holiday_date->format('Y-m-d'),
                    'day_type_code' => (string) $event->day_type_code,
                    'day_type_label' => $event->dayType?->day_type_name
                        ?? str_replace('_', ' ', (string) $event->day_type_code),
                    'scope_label' => $scopeLabel ?: 'No scope',
                    'notes' => $event->notes,
                ];
            })
            ->values()
            ->all();
    }

    protected function buildCalendarMatrix(
        array $calendarRows,
        string $periodStartDate,
        string $periodEndDate,
        ?string $selectedDate = null
    ): array {
        $rowsByDate = collect($calendarRows)->keyBy('work_date');

        $start = Carbon::parse($periodStartDate);
        $end = Carbon::parse($periodEndDate);

        $matrix = [];
        $week = [];

        $startDayOfWeek = (int) $start->isoWeekday();

        for ($i = 1; $i < $startDayOfWeek; $i++) {
            $week[] = null;
        }

        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dateKey = $cursor->format('Y-m-d');
            $row = $rowsByDate->get($dateKey);

            if ($row) {
                $week[] = [
                    ...$row,
                    'date' => $row['work_date'],
                    'is_selected' => $selectedDate === $row['work_date'],
                    'is_today' => $dateKey === now()->format('Y-m-d'),
                    'has_override' => $row['is_override'],
                ];
            } else {
                $week[] = [
                    'id' => null,
                    'date' => $dateKey,
                    'work_date' => $dateKey,
                    'day_name' => $cursor->format('l'),
                    'day_name_short' => $cursor->format('D'),
                    'day_number' => (int) $cursor->format('j'),
                    'day_type_code' => 'UNGENERATED',
                    'day_type_label' => 'Ungenerated',
                    'is_workday' => false,
                    'notes' => null,
                    'is_override' => false,
                    'updated_at' => null,
                    'is_selected' => $selectedDate === $dateKey,
                    'is_today' => $dateKey === now()->format('Y-m-d'),
                    'has_override' => false,
                ];
            }

            if (count($week) === 7) {
                $matrix[] = $week;
                $week = [];
            }

            $cursor->addDay();
        }

        if (!empty($week)) {
            while (count($week) < 7) {
                $week[] = null;
            }
            $matrix[] = $week;
        }

        return $matrix;
    }

    protected function resolveSelectedDay(
        array $calendarRows,
        ?string $selectedDate,
        string $periodStartDate,
        string $periodEndDate
    ): ?array {
        if (empty($calendarRows)) {
            return null;
        }

        $rowsByDate = collect($calendarRows)->keyBy('work_date');

        if ($selectedDate && $rowsByDate->has($selectedDate)) {
            return $rowsByDate->get($selectedDate);
        }

        $today = now()->format('Y-m-d');

        if ($today >= $periodStartDate && $today <= $periodEndDate && $rowsByDate->has($today)) {
            return $rowsByDate->get($today);
        }

        return collect($calendarRows)->first();
    }

    protected function mapDayTypeLabel(string $dayTypeCode): string
    {
        return match ($dayTypeCode) {
            'WORKDAY' => 'Workday',
            'HOLIDAY_NATIONAL' => 'Holiday National',
            'HOLIDAY_COMPANY' => 'Holiday Company',
            'WEEKOFF' => 'Weekoff',
            'HALF_DAY' => 'Half Day',
            'SPECIAL' => 'Special',
            'UNGENERATED' => 'Ungenerated',
            default => ucwords(strtolower(str_replace('_', ' ', $dayTypeCode))),
        };
    }

    protected function detectOverride(object $row): bool
    {
        return false;
    }
}