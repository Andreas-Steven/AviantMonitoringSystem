<?php

namespace App\Http\Controllers\Api;

use App\Domains\Production\Actions\StoreProductionResultAction;

use App\Domains\Production\DTOs\ProductionOrderFiltersData;
use App\Domains\Production\DTOs\StoreProductionResultData;

use App\Domains\Production\Enums\ProductionOrderSortField;
use App\Domains\Production\Enums\SortDirection;
use App\Domains\Production\Enums\WorkOrderStatus;

use App\Domains\Production\Services\ProductionDashboardService;
use App\Domains\Production\Services\ProductionOrderService;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use Carbon\CarbonImmutable;
use Closure;

class ProductionApiController extends Controller
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
            message: 'Production dashboard retrieved successfully.',
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
                message: 'Machine not found.',
                code: 404,
            );
        }

        $machineData = $machine->toArray();

        return ApiResponse::success(
            data: $machineData,
            message: 'Machine performance retrieved successfully.',
            meta: $this->singleResultMeta($machineData, ['machine_code' => $id]),
        );
    }

    public function productionOrders(
        Request $request,
        ProductionDashboardService $dashboardService,
        ProductionOrderService $orderService,
    ): JsonResponse {
        if ($unavailable = $this->datasetUnavailable($dashboardService)) {
            return $unavailable;
        }

        $status = $request->input('status');
        if (is_string($status)) {
            $request->merge(['status' => strtoupper(trim($status))]);
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'product' => ['nullable', 'string', 'max:150'],
            'machine' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(WorkOrderStatus::values())],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', Rule::in(ProductionOrderSortField::values())],
            'sort_by' => ['nullable', Rule::in(ProductionOrderSortField::values())],
            'direction' => ['nullable', Rule::in(SortDirection::values())],
            'sort_direction' => ['nullable', Rule::in(SortDirection::values())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $filters = ProductionOrderFiltersData::fromValidated($validated);
        $paginator = $orderService->paginate($filters);
        $filterMeta = array_filter([
            'search' => $filters->search,
            'product' => $filters->product,
            'machine' => $filters->machine,
            'status' => $filters->status?->value,
            'date' => $filters->date,
            'date_from' => $filters->dateFrom,
            'date_to' => $filters->dateTo,
        ], fn ($value): bool => $value !== null && $value !== '');

        return ApiResponse::success(
            data: $paginator->items(),
            message: 'Production order list retrieved successfully.',
            meta: [
                'filter' => $filterMeta,
                'sort' => [
                    'by' => $filters->sortBy->value,
                    'dir' => $filters->sortDirection->value,
                ],
                'pagination' => [
                    'total' => $paginator->total(),
                    'display' => $paginator->count(),
                    'page' => $paginator->currentPage(),
                    'page_size' => $paginator->perPage(),
                ],
            ],
        );
    }

    public function storeProductionResult(
        Request $request,
        ProductionDashboardService $dashboardService,
        StoreProductionResultAction $action,
    ): JsonResponse {
        if ($unavailable = $this->datasetUnavailable($dashboardService)) {
            return $unavailable;
        }

        $validated = $request->validate([
            'wo_number' => ['required', 'string', 'max:20', 'exists:work_order,wo_number'],
            'production_date' => [
                'required',
                'bail',
                'date_format:Y-m-d H:i:s',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (CarbonImmutable::parse($value)->toDateString() > today()->toDateString()) {
                        $fail('The production date field must be a date before or equal to today.');
                    }
                },
            ],
            'production_finish' => [
                'nullable',
                'bail',
                'date_format:Y-m-d H:i:s',
                'after_or_equal:production_date',
            ],
            'qty_good' => ['required', 'integer', 'min:0'],
            'qty_reject' => ['required', 'integer', 'min:0'],
            'runtime_minutes' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = $action->execute(StoreProductionResultData::fromValidated($validated));

        return ApiResponse::success(
            data: $result->toArray(),
            message: 'Production result created successfully.',
            code: 201,
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
            message: 'Production dataset has not been imported into manufacturing_test.',
            code: 503,
            data: ['missing_tables' => $missingTables],
        );
    }
}
