<?php

namespace App\Domains\Production\Actions;

use App\Domains\Production\DTOs\StoredProductionResultData;
use App\Domains\Production\DTOs\StoreProductionResultData;
use App\Domains\Production\Enums\WorkOrderStatus;
use App\Domains\Production\Repositories\ProductionOrderRepository;
use App\Domains\Production\Repositories\ProductionResultRepository;
use App\Domains\Production\Services\ProductionAchievementCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreProductionResultAction
{
    public function __construct(
        protected ProductionOrderRepository $orderRepository,
        protected ProductionResultRepository $resultRepository,
        protected ProductionAchievementCalculator $achievementCalculator,
    ) {}

    public function execute(StoreProductionResultData $data): StoredProductionResultData
    {
        return DB::transaction(function () use ($data): StoredProductionResultData {
            $workOrder = $this->orderRepository->findForUpdate($data->workOrderNumber);
            
            if (!$workOrder) {
                throw ValidationException::withMessages([
                    'wo_number' => [
                        __('production.validation.work_order_not_found'),
                    ],
                ]);
            }

            if (WorkOrderStatus::tryFrom(strtoupper((string) $workOrder->status)) !== WorkOrderStatus::Running) {
                throw ValidationException::withMessages([
                    'wo_number' => [
                        __('production.validation.work_order_must_be_running', ['status' => WorkOrderStatus::Running->value]),
                    ],
                ]);
            }

            $target = (int) $workOrder->target_qty;
            $achievement = $this->achievementCalculator->calculate($data->quantityGood, $target);
            
            $actualStart = CarbonImmutable::parse($data->productionDate);
            if ($data->productionFinish) {
                $actualFinish = CarbonImmutable::parse($data->productionFinish);
            } else {
                $actualFinish = $actualStart->addMinutes($data->runtimeMinutes);
            }

            $result = $this->resultRepository->create([
                'wo_number' => $workOrder->wo_number,
                'actual_start' => $actualStart->toDateTimeString(),
                'actual_finish' => $actualFinish->toDateTimeString(),
                'runtime_minutes' => $data->runtimeMinutes,
                'good_qty' => $data->quantityGood,
                'reject_qty' => $data->quantityReject,
                'achievement' => $achievement,
            ]);

            return new StoredProductionResultData(
                id: (int) $result->getKey(),
                workOrderNumber: $workOrder->wo_number,
                productionDate: $actualStart->toDateTimeString(),
                productionFinish: $actualFinish->toDateTimeString(),
                quantityGood: $data->quantityGood,
                quantityReject: $data->quantityReject,
                runtimeMinutes: $data->runtimeMinutes,
                achievement: $achievement,
            );
        });
    }
}
