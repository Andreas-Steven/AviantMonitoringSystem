<?php

namespace App\Domains\Production\DTOs;

use App\Domains\Production\Enums\ProductionOrderSortField;
use App\Domains\Production\Enums\SortDirection;
use App\Domains\Production\Enums\WorkOrderStatus;

final readonly class ProductionOrderFiltersData
{
    public function __construct(
        public ?string $search = null,
        public ?string $product = null,
        public ?string $machine = null,
        public ?WorkOrderStatus $status = null,
        public ?string $date = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ProductionOrderSortField $sortBy = ProductionOrderSortField::PlanStart,
        public SortDirection $sortDirection = SortDirection::Descending,
        public int $page = 1,
        public int $perPage = 10,
    ) {
    }

    public static function fromValidated(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            product: $data['product'] ?? null,
            machine: $data['machine'] ?? null,
            status: isset($data['status']) ? WorkOrderStatus::from(strtoupper($data['status'])) : null,
            date: $data['date'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            sortBy: ProductionOrderSortField::from($data['sort_by'] ?? $data['sort'] ?? ProductionOrderSortField::PlanStart->value),
            sortDirection: SortDirection::from(strtolower($data['sort_direction'] ?? $data['direction'] ?? SortDirection::Descending->value)),
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
        );
    }
}
