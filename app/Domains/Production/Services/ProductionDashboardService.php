<?php

namespace App\Domains\Production\Services;

use App\Domains\Production\Enums\WorkOrderStatus;

use App\Domains\Production\DTOs\MachinePerformanceData;
use App\Domains\Production\DTOs\ProductionDashboardData;

use App\Domains\Production\Repositories\ProductionDashboardRepository;
use App\Domains\Production\Repositories\ProductionSchemaRepository;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

class ProductionDashboardService
{
    public function __construct(
        protected ProductionDashboardRepository $dashboardRepository,
        protected ProductionSchemaRepository $schemaRepository,
    ) {
    }

    public function missingTables(): array
    {
        return $this->schemaRepository->missingTables();
    }

    public function dashboard(): ProductionDashboardData
    {
        $latestTimestamp = $this->dashboardRepository->latestActualStart();
        $latestDate = $latestTimestamp
            ? CarbonImmutable::parse($latestTimestamp)->toDateString()
            : null;
        $statusCounts = [];

        foreach ($this->dashboardRepository->workOrderStatusCounts() as $row) {
            $status = strtoupper(trim((string) $row->status));
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + (int) $row->total;
        }

        $statusBreakdown = [];

        foreach (WorkOrderStatus::cases() as $status) {
            $statusValue = $status->value;
            $statusBreakdown[] = ['status' => $statusValue, 'total' => $statusCounts[$statusValue] ?? 0];
            unset($statusCounts[$statusValue]);
        }

        foreach ($statusCounts as $status => $total) {
            $statusBreakdown[] = ['status' => $status, 'total' => $total];
        }

        $trend = [];
        $todayGood = 0;
        $todayReject = 0;
        $todayTarget = 0;

        if ($latestDate) {
            $startDate = CarbonImmutable::parse($latestDate)->subDays(6)->startOfDay();
            $endDate = CarbonImmutable::parse($latestDate)->addDay()->startOfDay();
            $productionByDate = $this->dashboardRepository
                ->productionTotalsBetween($startDate->toDateTimeString(), $endDate->toDateTimeString())
                ->keyBy('production_date');
            $targetsByDate = $this->dashboardRepository
                ->targetTotalsByDateBetween($startDate->toDateTimeString(), $endDate->toDateTimeString())
                ->groupBy('production_date')
                ->map(fn ($orders): int => (int) $orders->sum('target_qty'));

            foreach (CarbonPeriod::create($startDate->toDateString(), $latestDate) as $date) {
                $dateKey = $date->toDateString();
                $production = $productionByDate->get($dateKey);
                $good = (int) ($production->good_qty ?? 0);
                $reject = (int) ($production->reject_qty ?? 0);
                $target = (int) ($targetsByDate->get($dateKey) ?? 0);

                $trend[] = [
                    'date' => $dateKey,
                    'good_qty' => $good,
                    'reject_qty' => $reject,
                    'achievement' => $this->achievement($good, $target),
                ];
            }

            $latestProduction = $productionByDate->get($latestDate);
            $todayGood = (int) ($latestProduction->good_qty ?? 0);
            $todayReject = (int) ($latestProduction->reject_qty ?? 0);
            $todayTarget = (int) ($targetsByDate->get($latestDate) ?? 0);
        }

        return new ProductionDashboardData(
            summary: [
                'total_machine' => $this->dashboardRepository->machineCount(),
                'running_order' => $this->statusCount($statusBreakdown, WorkOrderStatus::Running),
                'finished_order' => $this->statusCount($statusBreakdown, WorkOrderStatus::Finished),
                'today_target' => $todayTarget,
                'today_good' => $todayGood,
                'today_reject' => $todayReject,
                'achievement' => $this->achievement($todayGood, $todayTarget),
            ],
            trend7Days: $trend,
            statusBreakdown: $statusBreakdown,
            topMachines: $this->topMachines(),
        );
    }

    public function machine(string $machineCode): ?MachinePerformanceData
    {
        $machine = $this->dashboardRepository->findMachine($machineCode);

        if (! $machine) {
            return null;
        }

        $production = $this->dashboardRepository->machineProductionTotals($machineCode);
        $good = (int) ($production?->good_qty ?? 0);
        $target = $this->dashboardRepository->targetForMachineWithResults($machineCode);

        return new MachinePerformanceData(
            machineCode: $machine->machine_code,
            machineName: $machine->machine_name,
            totalOrder: $this->dashboardRepository->workOrderCountForMachine($machineCode),
            goodQty: $good,
            rejectQty: (int) ($production?->reject_qty ?? 0),
            downtimeMinutes: $this->dashboardRepository->downtimeMinutesForMachine($machineCode),
            achievement: $this->achievement($good, $target),
        );
    }

    private function topMachines(): array
    {
        $targets = $this->dashboardRepository
            ->targetTotalsForOrdersWithResultsByMachine()
            ->keyBy('machine_code');

        return $this->dashboardRepository->topMachineProductionTotals()
            ->map(function ($row) use ($targets): array {
                $good = (int) $row->good_qty;
                $target = (int) ($targets->get($row->machine_code)->target_qty ?? 0);

                return [
                    'machine_code' => $row->machine_code,
                    'machine_name' => $row->machine_name,
                    'good_qty' => $good,
                    'reject_qty' => (int) $row->reject_qty,
                    'target_qty' => $target,
                    'achievement' => $this->achievement($good, $target),
                ];
            })
            ->all();
    }

    private function statusCount(array $statuses, WorkOrderStatus $status): int
    {
        foreach ($statuses as $item) {
            if ($item['status'] === $status->value) {
                return (int) $item['total'];
            }
        }

        return 0;
    }

    private function achievement(int $good, int $target): float
    {
        return $target > 0 ? round(($good / $target) * 100, 2) : 0.0;
    }
}
