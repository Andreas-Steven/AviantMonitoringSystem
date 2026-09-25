<?php

namespace App\Domains\Production\Repositories;

use App\Domains\Production\Models\Downtime;
use App\Domains\Production\Models\Machine;
use App\Domains\Production\Models\ProductionResult;
use App\Domains\Production\Models\WorkOrder;
use Illuminate\Support\Collection;

class ProductionDashboardRepository
{
    public function latestActualStart(): ?string
    {
        $latest = ProductionResult::query()->max('actual_start');

        return $latest === null ? null : (string) $latest;
    }

    public function machineCount(): int
    {
        return Machine::query()->count();
    }

    public function workOrderStatusCounts(): Collection
    {
        return WorkOrder::query()
            ->select('status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('status')
            ->get();
    }

    public function productionTotalsBetween(string $start, string $end): Collection
    {
        return ProductionResult::query()
            ->where('actual_start', '>=', $start)
            ->where('actual_start', '<', $end)
            ->selectRaw('DATE(actual_start) AS production_date, SUM(good_qty) AS good_qty, SUM(reject_qty) AS reject_qty')
            ->groupByRaw('DATE(actual_start)')
            ->get();
    }

    public function targetTotalsByDateBetween(string $start, string $end): Collection
    {
        return ProductionResult::query()
            ->from('production_result as pr')
            ->join('work_order as wo', 'wo.wo_number', '=', 'pr.wo_number')
            ->where('pr.actual_start', '>=', $start)
            ->where('pr.actual_start', '<', $end)
            ->selectRaw('DATE(pr.actual_start) AS production_date, wo.wo_number, MAX(wo.target_qty) AS target_qty')
            ->groupByRaw('DATE(pr.actual_start), wo.wo_number')
            ->get();
    }

    public function targetTotalsForOrdersWithResultsByMachine(): Collection
    {
        $workOrderNumbers = ProductionResult::query()
            ->select('wo_number')
            ->distinct();

        return WorkOrder::query()
            ->whereIn('wo_number', $workOrderNumbers)
            ->select('machine_code')
            ->selectRaw('SUM(target_qty) AS target_qty')
            ->groupBy('machine_code')
            ->get();
    }

    public function topMachineProductionTotals(): Collection
    {
        return ProductionResult::query()
            ->from('production_result as pr')
            ->join('work_order as wo', 'wo.wo_number', '=', 'pr.wo_number')
            ->join('machine as m', 'm.machine_code', '=', 'wo.machine_code')
            ->select(['m.machine_code', 'm.machine_name'])
            ->selectRaw('SUM(pr.good_qty) AS good_qty, SUM(pr.reject_qty) AS reject_qty')
            ->groupBy('m.machine_code', 'm.machine_name')
            ->orderByDesc('good_qty')
            ->orderBy('m.machine_name')
            ->limit(10)
            ->get();
    }

    public function findMachine(string $machineCode): ?Machine
    {
        return Machine::query()->where('machine_code', $machineCode)->first();
    }

    public function machineProductionTotals(string $machineCode): ?ProductionResult
    {
        return ProductionResult::query()
            ->from('production_result as pr')
            ->join('work_order as wo', 'wo.wo_number', '=', 'pr.wo_number')
            ->where('wo.machine_code', $machineCode)
            ->selectRaw('COALESCE(SUM(pr.good_qty), 0) AS good_qty, COALESCE(SUM(pr.reject_qty), 0) AS reject_qty')
            ->first();
    }

    public function targetForMachineWithResults(string $machineCode): int
    {
        $workOrderNumbers = ProductionResult::query()
            ->select('wo_number')
            ->distinct();

        return (int) WorkOrder::query()
            ->where('machine_code', $machineCode)
            ->whereIn('wo_number', $workOrderNumbers)
            ->sum('target_qty');
    }

    public function workOrderCountForMachine(string $machineCode): int
    {
        return WorkOrder::query()->where('machine_code', $machineCode)->count();
    }

    public function downtimeMinutesForMachine(string $machineCode): int
    {
        return (int) Downtime::query()
            ->from('downtime as d')
            ->join('work_order as wo', 'wo.wo_number', '=', 'd.wo_number')
            ->where('wo.machine_code', $machineCode)
            ->sum('d.duration_minutes');
    }
}
