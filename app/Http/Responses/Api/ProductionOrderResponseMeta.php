<?php

namespace App\Http\Responses\Api;

use App\Domains\Production\DTOs\ProductionOrderFiltersData;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProductionOrderResponseMeta
{
    public static function build(
        ProductionOrderFiltersData $filters,
        LengthAwarePaginator $paginator,
    ): array {
        return [
            'filter' => array_filter([
                'search' => $filters->search,
                'product' => $filters->product,
                'machine' => $filters->machine,
                'status' => $filters->status?->value,
                'date' => $filters->date,
                'date_from' => $filters->dateFrom,
                'date_to' => $filters->dateTo,
            ], fn ($value): bool => $value !== null && $value !== ''),
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
        ];
    }
}
