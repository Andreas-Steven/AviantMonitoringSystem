<?php

namespace App\Http\Controllers\Api\Validation;

use App\Domains\Production\Enums\ProductionOrderSortField;
use App\Domains\Production\Enums\SortDirection;
use App\Domains\Production\Enums\WorkOrderStatus;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductionInputValidator
{
    public function validateProductionOrders(Request $request): array
    {
        $status = $request->input('status');
        if (is_string($status)) {
            $request->merge(['status' => strtoupper(trim($status))]);
        }

        return $request->validate([
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
    }

    public function validateStoreProductionResult(Request $request): array
    {
        return $request->validate([
            'wo_number' => ['required', 'string', 'max:20', 'exists:work_order,wo_number'],
            'production_date' => [
                'required',
                'bail',
                'date_format:Y-m-d H:i:s',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (CarbonImmutable::parse($value)->toDateString() > today()->toDateString()) {
                        $fail(__('production.validation.production_date_not_after_today'));
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
        ], __('production.validation.messages'));
    }
}
