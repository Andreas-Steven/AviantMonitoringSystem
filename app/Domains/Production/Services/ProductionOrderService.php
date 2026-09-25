<?php

namespace App\Domains\Production\Services;

use App\Domains\Production\DTOs\ProductionOrderData;
use App\Domains\Production\DTOs\ProductionOrderFiltersData;
use App\Domains\Production\Models\WorkOrder;
use App\Domains\Production\Repositories\ProductionOrderRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductionOrderService
{
    public function __construct(
        protected ProductionOrderRepository $orderRepository,
    ) {
    }

    public function paginate(ProductionOrderFiltersData $filters): LengthAwarePaginator
    {
        $orders = $this->orderRepository->paginate($filters);
        $orders->setCollection($orders->getCollection()->map(
            fn (WorkOrder $workOrder): array => ProductionOrderData::fromModel($workOrder)->toArray(),
        ));

        return $orders;
    }
}
