<?php

namespace App\Http\Controllers\Api\Controller;

use App\Domains\Production\Actions\StoreProductionResultAction;
use App\Domains\Production\DTOs\ProductionOrderFiltersData;
use App\Domains\Production\DTOs\StoreProductionResultData;
use App\Domains\Production\Services\ProductionDashboardService;
use App\Domains\Production\Services\ProductionOrderService;
use App\Http\Controllers\Api\Validation\ProductionInputValidator;
use App\Http\Controllers\Controller;
use App\Http\Responses\Api\ProductionOrderResponseMeta;
use App\Http\Responses\ApiResponse;
use App\Shared\Enums\HttpStatusCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function dashboard(ProductionDashboardService $service): JsonResponse
    {
        if ($unavailable = $this->datasetUnavailable(
            service: $service,
        )) {
            return $unavailable;
        }

        return ApiResponse::success(
            data: $service->dashboard()->toArray(),
            message: __('production.dashboard_retrieved'),
        );
    }

    public function machine(string $id, ProductionDashboardService $service): JsonResponse
    {
        if ($unavailable = $this->datasetUnavailable(
            service: $service,
        )) {
            return $unavailable;
        }

        $machine = $service->machine($id);

        if (!$machine) {
            return ApiResponse::failure(
                message: __('production.machine_not_found'),
                code: HttpStatusCode::NotFound->value,
            );
        }

        return ApiResponse::success(
            data: $machine->toArray(),
            message: __('production.machine_performance_retrieved'),
            meta: $this->singleResultMeta($machineData, ['machine_code' => $id]),
        );
    }

    public function productionOrders(
        Request $request,
        ProductionInputValidator $validator,
        ProductionDashboardService $dashboardService,
        ProductionOrderService $orderService,
    ): JsonResponse {
        if ($unavailable = $this->datasetUnavailable($dashboardService)) {
            return $unavailable;
        }

        $validated = $validator->validateProductionOrders($request);

        $filters = ProductionOrderFiltersData::fromValidated($validated);
        $paginator = $orderService->paginate($filters);

        return ApiResponse::success(
            data: $paginator->items(),
            message: __('production.orders_retrieved'),
            meta: ProductionOrderResponseMeta::build($filters, $paginator),
        );
    }

    public function storeProductionResult(
        Request $request,
        ProductionInputValidator $validator,
        ProductionDashboardService $dashboardService,
        StoreProductionResultAction $action,
    ): JsonResponse {
        if ($unavailable = $this->datasetUnavailable($dashboardService)) {
            return $unavailable;
        }

        $validated = $validator->validateStoreProductionResult($request);
        $result = $action->execute(StoreProductionResultData::fromValidated($validated));

        return ApiResponse::success(
            data: $result->toArray(),
            message: __('production.result_created'),
            code: HttpStatusCode::Created->value,
        );
    }

    private function singleResultMeta(array $data, array $filter = []): array
    {
        $count = $data === [] ? 0 : 1;

        return [
            'filter' => $filter,
            'sort' => [
                'by' => 'id',
                'dir' => 'desc',
            ],
            'pagination' => [
                'total' => $count,
                'display' => $count,
                'page' => 1,
                'page_size' => 10,
            ],
        ];
    }

    private function datasetUnavailable(ProductionDashboardService $service): ?JsonResponse
    {
        $missingTables = $service->missingTables();

        if ($missingTables === []) {
            return null;
        }

        return ApiResponse::failure(
            message: __('production.dataset_unavailable'),
            code: HttpStatusCode::ServiceUnavailable->value,
            data: ['missing_tables' => $missingTables],
        );
    }
}
