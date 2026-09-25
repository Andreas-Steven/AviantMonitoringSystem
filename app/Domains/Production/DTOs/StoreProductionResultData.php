<?php

namespace App\Domains\Production\DTOs;

final readonly class StoreProductionResultData
{
    public function __construct(
        public string $workOrderNumber,
        public string $productionDate,
        public int $quantityGood,
        public int $quantityReject,
        public int $runtimeMinutes = 0,
        public ?string $productionFinish = null,
    ) {
    }

    public static function fromValidated(array $data): self
    {
        return new self(
            workOrderNumber: $data['wo_number'],
            productionDate: $data['production_date'],
            quantityGood: (int) $data['qty_good'],
            quantityReject: (int) $data['qty_reject'],
            runtimeMinutes: (int) ($data['runtime_minutes'] ?? 0),
            productionFinish: $data['production_finish'] ?? null,
        );
    }
}
