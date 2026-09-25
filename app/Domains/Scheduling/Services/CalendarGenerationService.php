<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\DTOs\GenerateBranchCalendarData;
use App\Domains\Scheduling\Repositories\BranchCalendarRepository;
use App\Domains\Scheduling\Repositories\PayrollPeriodRepository;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CalendarGenerationService
{
    public function __construct(
        protected PayrollPeriodRepository $payrollPeriodRepository,
        protected BranchCalendarRepository $branchCalendarRepository,
        protected CalendarDayTypeResolverService $calendarDayTypeResolverService,
    ) {}

    public function generate(GenerateBranchCalendarData $data): void
    {
        $payrollPeriod = $this->payrollPeriodRepository->findOrFail($data->payrollPeriodId);
        $branch = Branch::query()->findOrFail($data->branchId);

        if ($payrollPeriod->period_end_date->lt($payrollPeriod->period_start_date)) {
            throw new InvalidArgumentException('Payroll period date range is invalid.');
        }

        $rows = $this->buildRows(
            branch: $branch,
            startDate: $payrollPeriod->period_start_date,
            endDate: $payrollPeriod->period_end_date,
        );

        DB::transaction(function () use ($data, $payrollPeriod, $branch, $rows): void {
            if ($data->overwriteExisting) {
                $this->branchCalendarRepository->deleteByBranchAndDateRange(
                    $branch->branch_id,
                    $payrollPeriod->period_start_date,
                    $payrollPeriod->period_end_date
                );
            }

            $this->branchCalendarRepository->upsertRows($rows);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildRows(Branch $branch, $startDate, $endDate): array
    {
        $period = CarbonPeriod::create(
            $startDate->copy()->startOfDay(),
            $endDate->copy()->startOfDay()
        );

        $now = now();
        $rows = [];

        foreach ($period as $date) {
            $resolved = $this->calendarDayTypeResolverService->resolve($branch, $date);

            $rows[] = [
                'branch_id' => $branch->branch_id,
                'work_date' => $date->toDateString(),
                'day_type_code' => $resolved['day_type_code'],
                'day_name' => $resolved['day_name'],
                'is_workday' => $resolved['is_workday'],
                'notes' => $resolved['notes'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }
}