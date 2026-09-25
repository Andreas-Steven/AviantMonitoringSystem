<?php

namespace App\Domains\Production\Repositories;

use App\Domains\Production\Enums\ProductionOrderSortField;
use App\Domains\Production\DTOs\ProductionOrderFiltersData;

use App\Domains\Production\Models\ProductionResult;
use App\Domains\Production\Models\WorkOrder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductionOrderRepository
{
    public function paginate(ProductionOrderFiltersData $filters): LengthAwarePaginator
    {
        $productionTotals = ProductionResult::query()
            ->select('wo_number')
            ->selectRaw('SUM(good_qty) AS good_qty, SUM(reject_qty) AS reject_qty')
            ->groupBy('wo_number');

        $query = WorkOrder::query()
            ->join('product as p', 'p.product_code', '=', 'work_order.product_code')
            ->join('machine as m', 'm.machine_code', '=', 'work_order.machine_code')
            ->join('employee as e', 'e.employee_no', '=', 'work_order.employee_no')
            ->leftJoinSub($productionTotals, 'pr', 'pr.wo_number', '=', 'work_order.wo_number')
            ->select([
                'work_order.wo_number',
                'work_order.product_code',
                'p.product_name',
                'work_order.machine_code',
                'm.machine_name',
                'work_order.employee_no',
                'e.full_name AS employee_name',
                'work_order.shift',
                'work_order.target_qty',
                'work_order.plan_start',
                'work_order.plan_finish',
                'work_order.status',
            ])
            ->selectRaw('COALESCE(pr.good_qty, 0) AS good_qty, COALESCE(pr.reject_qty, 0) AS reject_qty');

        if ($filters->search) {
            $query->where(function (Builder $query) use ($filters): void {
                $query->where('work_order.wo_number', 'like', "%{$filters->search}%")
                    ->orWhere('p.product_name', 'like', "%{$filters->search}%")
                    ->orWhere('m.machine_name', 'like', "%{$filters->search}%")
                    ->orWhere('e.full_name', 'like', "%{$filters->search}%");
            });
        }

        if ($filters->product) {
            $query->where(function (Builder $query) use ($filters): void {
                $query->where('work_order.product_code', $filters->product)
                    ->orWhere('p.product_name', 'like', "%{$filters->product}%");
            });
        }

        if ($filters->machine) {
            $query->where(function (Builder $query) use ($filters): void {
                $query->where('work_order.machine_code', $filters->machine)
                    ->orWhere('m.machine_name', 'like', "%{$filters->machine}%");
            });
        }

        if ($filters->status) {
            $query->where('work_order.status', $filters->status->value);
        }

        if ($filters->date) {
            $query->whereDate('work_order.plan_start', $filters->date);
        }

        if ($filters->dateFrom) {
            $query->whereDate('work_order.plan_start', '>=', $filters->dateFrom);
        }

        if ($filters->dateTo) {
            $query->whereDate('work_order.plan_start', '<=', $filters->dateTo);
        }

        $sortColumns = [
            ProductionOrderSortField::WorkOrderNumber->value => 'work_order.wo_number',
            ProductionOrderSortField::Product->value => 'p.product_name',
            ProductionOrderSortField::Machine->value => 'm.machine_name',
            ProductionOrderSortField::Status->value => 'work_order.status',
            ProductionOrderSortField::PlanStart->value => 'work_order.plan_start',
            ProductionOrderSortField::TargetQuantity->value => 'work_order.target_qty',
            ProductionOrderSortField::GoodQuantity->value => 'good_qty',
            ProductionOrderSortField::RejectQuantity->value => 'reject_qty',
        ];

        return $query
            ->orderBy($sortColumns[$filters->sortBy->value], $filters->sortDirection->value)
            ->orderBy('work_order.wo_number')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function findForUpdate(string $workOrderNumber): ?WorkOrder
    {
        return WorkOrder::query()
            ->where('wo_number', $workOrderNumber)
            ->lockForUpdate()
            ->first();
    }
}
